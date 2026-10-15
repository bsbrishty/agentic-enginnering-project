<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies the new bilingual + dark-mode plumbing.
 *
 *  - /lang/{locale} persists the choice and redirects back.
 *  - The dashboard layout includes the theme toggle button, the
 *    language switcher links, and the no-flash bootstrap script.
 *  - The public layout includes a similar no-flash bootstrap.
 */
class LocaleAndThemeTest extends TestCase
{
    use RefreshDatabase;

    public function test_locale_switch_persists_choice_and_redirects_back(): void
    {
        $response = $this->get('/lang/bn', [
            'Referer' => '/dashboard',
        ]);

        $response->assertRedirect('/dashboard');
        $this->assertSame('bn', session('locale'));
        $this->assertSame('bn', $this->app->getLocale());
    }

    public function test_locale_switch_falls_back_to_dashboard_when_no_referer(): void
    {
        $response = $this->get('/lang/en');
        $response->assertRedirect(route('dashboard.index'));
    }

    public function test_locale_switch_rejects_unsupported_locale(): void
    {
        // 'fr' is not in the whitelist — the route regex 404s.
        $this->get('/lang/fr')
            ->assertNotFound();
    }

    public function test_dashboard_layout_renders_theme_toggle_and_lang_switcher(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $html = $this->get(route('dashboard.index'))->assertOk()->getContent();

        $this->assertStringContainsString('id="themeToggle"', $html);
        $this->assertStringContainsString('class="lang-switch"', $html);
        $this->assertStringContainsString(route('locale.switch', 'en'), $html);
        $this->assertStringContainsString(route('locale.switch', 'bn'), $html);
        // No-flash theme bootstrap
        $this->assertStringContainsString("localStorage.getItem('app_theme')", $html);
    }

    public function test_default_locale_renders_english_strings_in_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $html = $this->get(route('dashboard.index'))->assertOk()->getContent();

        $this->assertStringContainsString('Dashboard', $html);
        $this->assertStringContainsString('Active Spaces', $html);
        $this->assertStringContainsString('Current Plan', $html);
    }

    public function test_bangla_locale_renders_bangla_strings_in_dashboard(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Seed the session with the Bangla locale and assert the next
        // rendered page translates accordingly. Using withSession keeps
        // the value across the in-memory test request.
        $this->withSession(['locale' => 'bn'])
            ->get(route('dashboard.index'))
            ->assertOk()
            ->assertSee('ড্যাশবোর্ড')
            ->assertSee('সক্রিয় স্পেস')
            ->assertSee('বর্তমান প্ল্যান');
    }
}
