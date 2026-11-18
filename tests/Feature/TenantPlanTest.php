<?php

use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantPlanChanger;
use Laravel\Cashier\Events\WebhookReceived;
use Livewire\Livewire;
use Livewire\Volt\Volt;
use Mockery\MockInterface;

beforeEach(function () {
    seedSignupPlans(9.99);
    config(['services.stripe.prices' => ['basic' => 'price_basic', 'pro' => 'price_pro', 'enterprise' => 'price_enterprise']]);
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->where('id', 'plantenant')->get()->each->delete();
});

function actAsPlanTenant(string $plan = 'basic', ?string $stripeId = 'cus_plan'): Tenant
{
    $tenant = Tenant::create([
        'id' => 'plantenant',
        'name' => 'Plan Co',
        'email' => 'plan@example.com',
        'status' => 'active',
        'plan' => $plan,
        'stripe_id' => $stripeId,
    ]);
    $tenant->domains()->create(['domain' => 'plantenant']);
    tenancy()->initialize($tenant);
    test()->actingAs(User::factory()->create());

    return $tenant;
}

function mockPlanChanger(bool $hasSubscription): MockInterface
{
    return test()->mock(TenantPlanChanger::class, function (MockInterface $mock) use ($hasSubscription) {
        $mock->shouldReceive('hasSubscription')->andReturn($hasSubscription);
    });
}

it('shows every plan with the current one marked and the others to switch to', function () {
    actAsPlanTenant('basic');
    mockPlanChanger(true);

    Volt::test('tenant.plan')
        ->assertSeeHtml('data-current-plan')
        ->assertSee('Current plan')
        ->assertSee('Switch to Pro')
        ->assertSee('Switch to Enterprise')
        ->assertDontSee('Switch to Basic')
        ->assertSee('Payment method and invoices');
});

it('swaps the stripe subscription when the tenant already pays one', function () {
    $tenant = actAsPlanTenant('basic');
    mockPlanChanger(true)
        ->shouldReceive('swap')
        ->once()
        ->withArgs(fn (Tenant $swapped, $plan) => $swapped->is($tenant) && $plan->slug === 'pro')
        ->andReturnUsing(fn (Tenant $swapped, $plan) => $swapped->update(['plan' => $plan->slug]));

    Volt::test('tenant.plan')
        ->call('confirm', 'pro')
        ->assertSet('showConfirm', true)
        ->assertSee('Switch to the Pro plan?')
        ->call('changePlan')
        ->assertSet('showConfirm', false)
        ->assertSee('Done! Your plan is now Pro.');

    expect($tenant->fresh()->plan)->toBe('pro');
});

it('sends a tenant without a subscription to stripe checkout', function () {
    actAsPlanTenant('basic', null);
    mockPlanChanger(false)
        ->shouldReceive('checkoutUrl')
        ->once()
        ->withArgs(fn ($tenant, $plan, string $returnUrl) => $plan->slug === 'enterprise' && $returnUrl === url('/plan'))
        ->andReturn('https://checkout.stripe.com/c/pay/test');

    Volt::test('tenant.plan')
        ->assertDontSee('Payment method and invoices')
        ->call('confirm', 'enterprise')
        ->call('changePlan')
        ->assertRedirect('https://checkout.stripe.com/c/pay/test');
});

it('applies the plan when coming back from a paid checkout', function () {
    $tenant = actAsPlanTenant('basic');
    mockPlanChanger(true)
        ->shouldReceive('confirmCheckout')
        ->once()
        ->with(Mockery::any(), 'cs_test_123')
        ->andReturnUsing(function (Tenant $paid) {
            $paid->update(['plan' => 'pro']);

            return 'pro';
        });

    Livewire::withQueryParams(['session_id' => 'cs_test_123'])
        ->test('tenant.plan')
        ->assertSee('Done! Your plan is now Pro.');

    expect($tenant->fresh()->plan)->toBe('pro');
});

it('does not switch to the current plan or to a plan that does not exist', function () {
    actAsPlanTenant('pro');
    mockPlanChanger(true)->shouldNotReceive('swap');

    Volt::test('tenant.plan')
        ->call('confirm', 'pro')
        ->assertSet('showConfirm', false)
        ->call('confirm', 'gold')
        ->assertSet('showConfirm', false)
        ->call('changePlan')
        ->assertSee('This plan is not available right now.');
});

it('shows a friendly error when stripe fails', function () {
    $tenant = actAsPlanTenant('basic');
    mockPlanChanger(true)->shouldReceive('swap')->andThrow(new RuntimeException('Stripe is down'));

    Volt::test('tenant.plan')
        ->call('confirm', 'pro')
        ->call('changePlan')
        ->assertSee('We could not change your plan right now.');

    expect($tenant->fresh()->plan)->toBe('basic');
});

it('links the plan page from the dashboard, the sidebar and the upgrade buttons', function () {
    actAsPlanTenant('basic');
    mockPlanChanger(true);

    Volt::test('tenant.dashboard')
        ->assertSeeHtml('data-plan-badge')
        ->assertSeeHtml('href="'.url('/plan').'"')
        ->assertSee('My plan');

    Volt::test('tenant.projects')->assertSeeHtml('href="'.url('/plan').'"');

    Volt::test('tenant.plan')->assertOk();
});

it('updates the tenant plan from the stripe webhook', function () {
    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'plantenant', 'name' => 'Plan Co', 'email' => 'plan@example.com',
        'status' => 'active', 'plan' => 'basic', 'stripe_id' => 'cus_webhookplan',
    ]));

    event(new WebhookReceived([
        'type' => 'customer.subscription.updated',
        'data' => ['object' => ['customer' => 'cus_webhookplan', 'status' => 'active', 'metadata' => ['plan' => 'pro']]],
    ]));

    expect($tenant->fresh()->plan)->toBe('pro');

    event(new WebhookReceived([
        'type' => 'customer.subscription.updated',
        'data' => ['object' => ['customer' => 'cus_webhookplan', 'status' => 'active', 'metadata' => [], 'items' => ['data' => [['price' => ['id' => 'price_enterprise']]]]]],
    ]));

    expect($tenant->fresh()->plan)->toBe('enterprise');
});
