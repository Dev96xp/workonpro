<?php

use App\Models\BusinessProfile;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->where('id', 'setupnotice')->get()->each->delete();
});

function initializeSetupNoticeTenant(): Tenant
{
    $tenant = Tenant::create([
        'id' => 'setupnotice',
        'name' => 'Setup Notice Co',
        'email' => 'setupnotice@example.com',
        'status' => 'active',
        'plan' => 'basic',
    ]);
    $tenant->domains()->create(['domain' => 'setupnotice']);
    tenancy()->initialize($tenant);

    return $tenant;
}

it('invites a guest to log in while the new page has no profile', function () {
    initializeSetupNoticeTenant();

    Volt::test('tenant.welcome')
        ->assertSee('data-setup-notice', false)
        ->assertSee(__('tenant.setup_notice.body_guest'))
        ->assertSee(__('tenant.setup_notice.cta_guest'));
});

it('sends the logged-in owner to complete the profile', function () {
    initializeSetupNoticeTenant();
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.welcome')
        ->assertSee(__('tenant.setup_notice.body_owner'))
        ->assertSee(__('tenant.setup_notice.cta_owner'));
});

it('shows the login modal in the visitor language', function (string $locale, string $title, string $submit) {
    initializeSetupNoticeTenant();
    app()->setLocale($locale);

    Volt::test('tenant.welcome')
        ->set('showLogin', true)
        ->assertSee($title)
        ->assertSee($submit);
})->with([
    'spanish' => ['es', 'Acceso al panel', 'Iniciar sesión'],
    'english' => ['en', 'Dashboard access', 'Log in'],
]);

it('reports wrong credentials in spanish', function () {
    initializeSetupNoticeTenant();
    app()->setLocale('es');

    Volt::test('tenant.welcome')
        ->set('showLogin', true)
        ->set('email', 'nobody@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email')
        ->assertSee('Estas credenciales no coinciden con nuestros registros.');
});

it('stops showing the notice once the profile has been saved', function () {
    initializeSetupNoticeTenant();
    BusinessProfile::create(['business_name' => 'Setup Notice Co']);

    Volt::test('tenant.welcome')->assertDontSee('data-setup-notice', false);
});
