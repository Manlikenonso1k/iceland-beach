<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/** THROWAWAY audit harness. Delete after use. */
class TmpAuditMatrixTest extends TestCase
{
    use RefreshDatabase;

    private const ROLES = [
        'Super Admin', 'Admin', 'Manager', 'Front Desk', 'Space Host',
        'Host', 'Waiter', 'Scanner', 'Referral', '(no role)',
        'super_admin', 'admin', 'frontdesk',
    ];

    private function out(string $line): void
    {
        fwrite(STDERR, $line . "\n");
    }

    public function test_dump_matrix(): void
    {
        foreach (['Super Admin', 'Admin', 'Manager', 'Front Desk', 'Space Host', 'Waiter', 'Scanner', 'Referral', 'Host', 'super_admin', 'admin', 'frontdesk'] as $r) {
            Role::findOrCreate($r, 'web');
        }

        Filament::setCurrentPanel(Filament::getPanel('admin'));
        $panel = Filament::getPanel('admin');

        // ---- enumerate resources + their list URLs ----
        $resources = $panel->getResources();
        $pages = $panel->getPages();

        $this->out('=== REGISTERED RESOURCES ===');
        $targets = [];
        foreach ($resources as $resource) {
            $slug = $resource::getSlug();
            $targets[$slug] = ['type' => 'resource', 'class' => $resource, 'url' => '/admin/' . $slug];
            $this->out(sprintf('%-28s %s  model=%s', $slug, $resource, $resource::getModel()));
        }
        $this->out('=== REGISTERED PAGES ===');
        foreach ($pages as $page) {
            $slug = $page::getSlug();
            $targets['page:' . $slug] = ['type' => 'page', 'class' => $page, 'url' => '/admin/' . ltrim($slug, '/')];
            $this->out(sprintf('%-28s %s', $slug, $page));
        }

        // ---- VIEW: GET each target index for each role ----
        $this->out('');
        $this->out('=== VIEW (HTTP status on GET) ===');
        $viewMatrix = [];
        foreach (self::ROLES as $roleName) {
            $user = User::forceCreate([
                'name' => 'T ' . $roleName,
                'email' => 'tmp' . md5($roleName) . '@audit.test',
                'password' => bcrypt('x'),
            ]);
            if ($roleName !== '(no role)') {
                $user->assignRole($roleName);
            }

            foreach ($targets as $key => $t) {
                $this->refreshApplication();
                Filament::setCurrentPanel(Filament::getPanel('admin'));
                $resp = $this->actingAs(User::find($user->id), 'web')->get($t['url']);
                $viewMatrix[$roleName][$key] = $resp->getStatusCode();
            }
        }

        // print table
        $cols = array_keys($targets);
        $this->out('ROLE | ' . implode(' | ', $cols));
        foreach ($viewMatrix as $role => $row) {
            $cells = [];
            foreach ($cols as $c) {
                $cells[] = $row[$c];
            }
            $this->out($role . ' | ' . implode(' | ', $cells));
        }

        // ---- CREATE / EDIT page URLs ----
        $this->out('');
        $this->out('=== CREATE PAGE (HTTP status on GET /admin/<slug>/create) ===');
        $createTargets = [];
        foreach ($resources as $resource) {
            $slug = $resource::getSlug();
            foreach ($resource::getPages() as $name => $registration) {
                if ($name === 'create') {
                    $createTargets[$slug] = '/admin/' . $slug . '/create';
                }
            }
        }
        $createMatrix = [];
        foreach (self::ROLES as $roleName) {
            $user = User::where('email', 'tmp' . md5($roleName) . '@audit.test')->first();
            foreach ($createTargets as $slug => $url) {
                $this->refreshApplication();
                Filament::setCurrentPanel(Filament::getPanel('admin'));
                $resp = $this->actingAs(User::find($user->id), 'web')->get($url);
                $createMatrix[$roleName][$slug] = $resp->getStatusCode();
            }
        }
        $cCols = array_keys($createTargets);
        $this->out('ROLE | ' . implode(' | ', $cCols));
        foreach ($createMatrix as $role => $row) {
            $cells = [];
            foreach ($cCols as $c) {
                $cells[] = $row[$c];
            }
            $this->out($role . ' | ' . implode(' | ', $cells));
        }

        // ---- WRITE flags via static calls ----
        $this->out('');
        $this->out('=== WRITE FLAGS (canCreate / canEdit / canDelete / canViewAny / canAccess) ===');
        foreach (self::ROLES as $roleName) {
            $this->refreshApplication();
            Filament::setCurrentPanel(Filament::getPanel('admin'));
            $user = User::where('email', 'tmp' . md5($roleName) . '@audit.test')->first();
            $this->actingAs(User::find($user->id), 'web');
            $this->out('-- ROLE: ' . $roleName);
            foreach ($resources as $resource) {
                $model = $resource::getModel();
                $rec = new $model;
                $f = function (callable $cb) {
                    try {
                        return $cb() ? 'Y' : 'n';
                    } catch (\Throwable $e) {
                        return 'ERR:' . class_basename($e);
                    }
                };
                $this->out(sprintf(
                    '   %-24s access=%s viewAny=%s create=%s edit=%s delete=%s deleteAny=%s',
                    $resource::getSlug(),
                    $f(fn () => $resource::canAccess()),
                    $f(fn () => $resource::canViewAny()),
                    $f(fn () => $resource::canCreate()),
                    $f(fn () => $resource::canEdit($rec)),
                    $f(fn () => $resource::canDelete($rec)),
                    $f(fn () => $resource::canDeleteAny()),
                ));
            }
            foreach ($pages as $page) {
                $this->out(sprintf('   PAGE %-19s access=%s', $page::getSlug(), $page::canAccess() ? 'Y' : 'n'));
            }
        }

        $this->assertTrue(true);
    }
}
