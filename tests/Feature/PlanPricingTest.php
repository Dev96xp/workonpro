<?php

use App\Models\Plan;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\Services\StripePlanPricing;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedSignupPlans(basicPrice: 9.99);
});

afterEach(function () {
    Tenant::query()->where('id', 'pricingsignup')->get()->each->delete();
});

function editPlanAsAdmin(Plan $plan): Testable
{
    test()->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    return Volt::test('admin.plans.edit', ['plan' => $plan]);
}

it('formats plan prices for display', function () {
    $plan = new Plan(['price' => 9.99, 'old_price' => 29]);

    expect($plan->displayPrice())->toBe('$9.99')
        ->and($plan->displayOldPrice())->toBe('$29')
        ->and($plan->priceInCents())->toBe(999)
        ->and((new Plan(['price' => 0]))->isFree())->toBeTrue();
});

it('saves a new price and the stripe price that charges it', function () {
    $this->mock(StripePlanPricing::class)
        ->shouldReceive('sync')
        ->once()
        ->withArgs(fn (Plan $plan) => $plan->priceInCents() === 1499)
        ->andReturn('price_new_1499');

    editPlanAsAdmin(Plan::where('slug', 'basic')->first())
        ->set('price', '14.99')
        ->set('old_price', '29')
        ->call('save')
        ->assertHasNoErrors();

    $plan = Plan::where('slug', 'basic')->first();

    expect($plan->price)->toBe('14.99')
        ->and($plan->old_price)->toBe('29.00')
        ->and($plan->stripe_price_id)->toBe('price_new_1499');
});

it('keeps the old price when stripe rejects the new one', function () {
    $this->mock(StripePlanPricing::class)
        ->shouldReceive('sync')
        ->andThrow(new RuntimeException('Invalid API key'));

    editPlanAsAdmin(Plan::where('slug', 'pro')->first())
        ->set('price', '45')
        ->call('save')
        ->assertHasErrors('price');

    expect(Plan::where('slug', 'pro')->first()->price)->toBe('39.00');
});

it('never calls stripe for a free plan', function () {
    $plan = new Plan(['slug' => 'basic', 'price' => 0, 'stripe_price_id' => 'price_old']);

    expect(app(StripePlanPricing::class)->sync($plan))->toBe('price_old');
});

it('shows the prices from the database on the plans and landing pages', function () {
    $this->get('/signup')
        ->assertSee('$9.99')
        ->assertSee('$29')
        ->assertSee('$39');

    $this->get('/welcome-v1')
        ->assertSee('$9.99')
        ->assertSee('$79');
});

it('promotes a plan as free when its price is zero', function () {
    Plan::where('slug', 'basic')->update(['price' => 0]);

    $this->get('/signup')->assertSee(__('register.plans.free'))->assertSee(__('register.plans.basic_badge'));

    Volt::test('register.create', ['plan' => 'basic'])
        ->set('plan', 'basic')
        ->set('business_name', 'Pricing Co')
        ->set('subdomain', 'pricingsignup')
        ->set('email', 'pricing@example.com')
        ->set('city', 'Omaha')
        ->set('state', 'Nebraska')
        ->set('country', 'United States')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', true)
        ->call('checkout')
        ->assertHasNoErrors();

    expect(Tenant::find('pricingsignup')->status)->toBe('active');
});

it('refuses a paid plan that has no stripe price to charge', function () {
    config(['services.stripe.prices.basic' => null]);

    Volt::test('register.create')
        ->set('plan', 'basic')
        ->set('business_name', 'Pricing Co')
        ->set('subdomain', 'pricingsignup')
        ->set('email', 'pricing@example.com')
        ->set('city', 'Omaha')
        ->set('state', 'Nebraska')
        ->set('country', 'United States')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', true)
        ->call('checkout')
        ->assertHasErrors('plan');

    expect(Tenant::find('pricingsignup'))->toBeNull();
});

it('shows the signup plans with the landing pricing design and lets a plan be picked', function () {
    $this->get('/signup')
        ->assertOk()
        ->assertSee('bg-yellow-400', false)
        ->assertSee(__('register.plans.cta_start_pro'))
        ->assertSee(__('register.plans.item_businesscard'));

    Volt::test('register.plans')
        ->call('selectPlan', 'pro')
        ->assertRedirect(route('register.create', ['plan' => 'pro']));
});
