<?php

use App\AccountType;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Models\User;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Volt\Volt;

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->whereIn('id', ['personalsignup', 'businesssignup', 'accounttypetenant'])->get()->each->delete();
});

function signUpWithAccountType(string $subdomain, ?string $accountType = null): Testable
{
    $component = Volt::test('register.create')
        ->set('plan', 'basic')
        ->set('business_name', 'María López')
        ->set('subdomain', $subdomain)
        ->set('email', "{$subdomain}@example.com")
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', true);

    if ($accountType !== null) {
        $component->set('account_type', $accountType);
    }

    return $component->call('checkout');
}

it('registers a business account by default', function () {
    signUpWithAccountType('businesssignup')->assertHasNoErrors();

    expect(Tenant::find('businesssignup')->account_type)->toBe(AccountType::Business);
});

it('registers a personal account when chosen at signup', function () {
    signUpWithAccountType('personalsignup', 'personal')->assertHasNoErrors();

    expect(Tenant::find('personalsignup')->isPersonal())->toBeTrue();
});

it('rejects an unknown account type at signup', function () {
    signUpWithAccountType('personalsignup', 'company')->assertHasErrors(['account_type' => 'in']);

    expect(Tenant::find('personalsignup'))->toBeNull();
});

it('shows the account type of each tenant in the admin list', function () {
    Tenant::withoutEvents(function () {
        Tenant::create(['id' => 'businesssignup', 'name' => 'Build Co', 'email' => 'build@example.com', 'status' => 'active', 'plan' => 'basic', 'created_at' => now()->subDay()]);
        Tenant::create(['id' => 'personalsignup', 'name' => 'María López', 'email' => 'maria@example.com', 'status' => 'active', 'plan' => 'basic', 'account_type' => 'personal']);
    });
    Tenant::query()->whereKey('businesssignup')->update(['created_at' => now()->subDay()]);

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    Volt::test('admin.tenants.index')
        ->assertSeeInOrder(['María López', AccountType::Personal->label(), 'Build Co', AccountType::Business->label()]);
});

it('lets a tenant switch to a personal account from settings and adapts the form', function () {
    $tenant = Tenant::create([
        'id' => 'accounttypetenant',
        'name' => 'Account Type Co',
        'email' => 'accounttype@example.com',
        'status' => 'active',
        'plan' => 'basic',
    ]);
    $tenant->domains()->create(['domain' => 'accounttypetenant']);

    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.settings')
        ->assertSet('account_type', 'business')
        ->assertSee(__('tenant.settings.has_license_switch'))
        ->set('account_type', 'personal')
        ->assertSee(__('tenant.settings.personal_name_label'))
        ->assertDontSee(__('tenant.settings.has_license_switch'))
        ->assertDontSee(__('tenant.settings.policy_label'))
        ->set('business_name', 'María López')
        ->call('saveProfile')
        ->assertHasNoErrors();

    expect($tenant->fresh()->account_type)->toBe(AccountType::Personal);
});
