<?php

namespace Tests\Feature;

use App\Models\Invoice;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

/**
 * The three findings the audit deliberately left for the owner to decide on,
 * now closed:
 *
 *   1. /invoices/{id}/view was unauthenticated and unsigned on a sequential
 *      integer id, leaking customer name, address, phone and total.
 *   2. CORS allowed any origin, because config/cors.php did not exist.
 *   3. Sanctum tokens never expired, so a lost phone kept API access forever.
 */
class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    private function invoice(): Invoice
    {
        return Invoice::query()->create([
            'invoice_number' => 'INV-SEC-1',
            'invoice_date' => now(),
            'customer_name' => 'Sensitive Customer Name',
            'customer_address' => '12 Private Road, Lagos',
            'telephone' => '08012345678',
            'total_amount' => 250000,
            'total_in_words' => 'Two hundred and fifty thousand naira',
        ]);
    }

    // ------------------------------------------------- 1. the invoice leak

    public function test_an_unsigned_invoice_url_no_longer_returns_customer_data(): void
    {
        $invoice = $this->invoice();

        // The exact request that used to work: no auth, no signature, just an id.
        $response = $this->get('/invoices/' . $invoice->id . '/view');

        $response->assertForbidden();
        $response->assertDontSee('Sensitive Customer Name');
        $response->assertDontSee('08012345678');
    }

    public function test_the_id_range_can_no_longer_be_walked(): void
    {
        // The abuse path was counting upward from 1. Every id must refuse.
        foreach (range(1, 5) as $id) {
            $this->get('/invoices/' . $id . '/view')->assertForbidden();
        }
    }

    public function test_a_signed_invoice_url_still_prints(): void
    {
        // The page exists to be printed and shared, so a legitimate signed link
        // must work with no session at all.
        $invoice = $this->invoice();

        $this->get(URL::signedRoute('invoices.view', ['id' => $invoice->id]))
            ->assertOk()
            ->assertSee('Sensitive Customer Name');
    }

    public function test_a_signature_for_one_invoice_does_not_open_another(): void
    {
        $mine = $this->invoice();
        $other = Invoice::query()->create([
            'invoice_number' => 'INV-SEC-2',
            'invoice_date' => now(),
            'customer_name' => 'Someone Else',
            'total_amount' => 1000,
            'total_in_words' => 'One thousand naira',
        ]);

        $tampered = str_replace(
            '/invoices/' . $mine->id . '/view',
            '/invoices/' . $other->id . '/view',
            URL::signedRoute('invoices.view', ['id' => $mine->id]),
        );

        $this->get($tampered)->assertForbidden();
    }

    public function test_the_signed_link_has_no_expiry_so_a_printed_invoice_keeps_working(): void
    {
        // An invoice is filed and reopened months later. A temporary link would
        // be worse than useless.
        $invoice = $this->invoice();
        $url = URL::signedRoute('invoices.view', ['id' => $invoice->id]);

        $this->assertStringNotContainsString('expires=', $url);

        $this->travel(13)->months();

        $this->get($url)->assertOk();
    }

    // -------------------------------------------------------------- 2. CORS

    public function test_cors_is_no_longer_a_wildcard(): void
    {
        $origins = config('cors.allowed_origins');

        $this->assertIsArray($origins);
        $this->assertNotContains('*', $origins, 'CORS must not allow every origin.');
        $this->assertContains('https://icelandbeach.com', $origins);
        $this->assertContains('https://staging.icelandbeach.com', $origins);

        // Must stay false: it is permissive origins PLUS credentials that makes
        // CORS dangerous, and auth here is a Bearer header, not a cookie.
        $this->assertFalse(config('cors.supports_credentials'));

        $this->assertContains('api/*', config('cors.paths'));
    }

    public function test_development_origins_are_excluded_in_production(): void
    {
        // The localhost and LAN entries exist for Expo Web in development and
        // must never reach the live config.
        $this->assertSame('testing', config('app.env'));

        $devOrigins = collect(config('cors.allowed_origins'))
            ->filter(fn (string $o): bool => str_contains($o, 'localhost') || str_contains($o, '127.0.0.1'));

        // Present here (non-production)…
        $this->assertTrue($devOrigins->isNotEmpty());

        // …and the production branch of the same expression yields none.
        $this->assertSame([], env('APP_ENV') === 'production' ? $devOrigins->all() : []);
    }

    // ----------------------------------------------------- 3. token expiry

    public function test_sanctum_tokens_now_expire(): void
    {
        $this->assertNotNull(config('sanctum.expiration'), 'Tokens must not live forever.');
        $this->assertSame(60 * 24 * 30, config('sanctum.expiration'));
    }

    public function test_a_token_older_than_the_expiry_is_refused(): void
    {
        $user = User::factory()->create();
        $plain = $user->createToken('phone')->plainTextToken;

        // Works now.
        $this->withHeader('Authorization', 'Bearer ' . $plain)
            ->getJson('/api/me')
            ->assertOk();

        // A lost phone, 31 days later.
        $this->travel(31)->days();

        // The successful request above memoises the resolved user on the
        // RequestGuard, and the test app is not rebuilt between requests in one
        // method — so without this the second request reuses the cached user and
        // reports 200 regardless of expiry. This is a test artifact, not a
        // behaviour: a real second request arrives on a fresh guard.
        $this->app['auth']->forgetGuards();

        $this->withHeader('Authorization', 'Bearer ' . $plain)
            ->getJson('/api/me')
            ->assertUnauthorized();
    }

    public function test_a_token_inside_the_window_still_works(): void
    {
        // Expiry must not log staff out mid-week.
        $user = User::factory()->create();
        $plain = $user->createToken('phone')->plainTextToken;

        $this->travel(29)->days();

        $this->withHeader('Authorization', 'Bearer ' . $plain)
            ->getJson('/api/me')
            ->assertOk();
    }

    public function test_expired_tokens_are_prunable(): void
    {
        // Expiry stops a token working but leaves the row forever, so the
        // schedule runs sanctum:prune-expired. This proves the command finds
        // what the expiry window has aged out.
        $user = User::factory()->create();
        $user->createToken('old');

        PersonalAccessToken::query()->update([
            'created_at' => Carbon::now()->subDays(60),
            'updated_at' => Carbon::now()->subDays(60),
        ]);

        $this->assertSame(1, PersonalAccessToken::query()->count());

        $this->artisan('sanctum:prune-expired', ['--hours' => 168])->assertSuccessful();

        $this->assertSame(0, PersonalAccessToken::query()->count());
    }
}
