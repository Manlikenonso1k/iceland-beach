<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * The scan page used to throw MultipleRootElementsDetectedException on every
 * load whenever APP_DEBUG was true — so it was broken for local development,
 * though production (APP_DEBUG=false) never ran Livewire's root-element check
 * and so was unaffected.
 *
 * Cause: libxml, which Livewire uses to count root elements, mis-parsed the
 * long inline <script> and closed the .fi-page div early. Filament's three
 * action-modal <form>s then became sibling root elements. Moving the scripts
 * into the layout's scripts stack leaves the component with a single root.
 */
class ScanTicketPageTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $role): User
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
        Role::findOrCreate($role, 'web');

        $user = User::factory()->create();
        $user->assignRole($role);

        return $user;
    }

    public function test_the_page_renders_with_debug_on_and_keeps_its_scanner_assets(): void
    {
        // The check Livewire performs only runs when app.debug is true, which is
        // precisely the configuration this page used to fail under.
        config(['app.debug' => true]);

        $response = $this->actingAs($this->userWithRole('Super Admin'))->get('/admin/scan-ticket');

        $response->assertOk();

        // Pushed to a stack, so still on the page — just not inside the
        // component's root element.
        $response->assertSee('html5-qrcode.min.js', escape: false);
        $response->assertSee('startScanner', escape: false);
        $response->assertSee('scanner-container', escape: false);
    }

    public function test_the_component_renders_exactly_one_root_element(): void
    {
        $this->actingAs($this->userWithRole('Super Admin'));
        \Filament\Facades\Filament::setCurrentPanel(\Filament\Facades\Filament::getPanel('admin'));

        $html = \Livewire\Livewire::test(\App\Filament\Pages\ScanTicket::class)->html();

        $dom = new \DOMDocument();
        @$dom->loadHTML($html);

        $roots = 0;
        foreach ($dom->getElementsByTagName('body')->item(0)->childNodes as $child) {
            // Livewire's own counter ignores <script>; mirror that exactly.
            if ($child->nodeType === XML_ELEMENT_NODE && $child->tagName !== 'script') {
                $roots++;
            }
        }

        $this->assertSame(1, $roots, 'Livewire requires exactly one root element per component.');
    }
}
