<?php

use App\Mail\ProjectInterestAccepted;
use App\Mail\ProjectInterestReceived;
use App\Models\Homeowner;
use App\Models\Project;
use App\Models\ProjectInterest;
use App\Models\Tenant;
use App\Models\User;
use App\ProjectInterestStatus;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

beforeEach(function () {
    seedSignupPlans();
    Mail::fake();
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }

    Tenant::query()->where('id', 'projectstenant')->get()->each->delete();
    Tenant::query()->where('id', 'like', 'contractor%')->get()->each(fn (Tenant $tenant) => Tenant::withoutEvents(fn () => $tenant->delete()));
});

function actAsContractor(string $plan, string $city = 'Omaha'): Tenant
{
    $tenant = Tenant::create([
        'id' => 'projectstenant',
        'name' => 'Concrete LLC',
        'email' => 'admin@concrete.com',
        'status' => 'active',
        'plan' => $plan,
        'city' => $city,
    ]);
    $tenant->domains()->create(['domain' => 'projectstenant']);

    tenancy()->initialize($tenant);
    test()->actingAs(User::factory()->create());

    return $tenant;
}

it('shows open projects to a basic plan without letting them respond', function () {
    $project = Project::factory()->create(['title' => 'Replace my roof']);
    actAsContractor('basic');

    Volt::test('tenant.projects')
        ->assertSee('Replace my roof')
        ->assertSee('Upgrade to Pro to respond')
        ->assertSeeHtml('data-upgrade-banner')
        ->assertDontSee($project->homeowner->email)
        ->assertDontSee($project->homeowner->name)
        ->call('openInterest', $project->id)
        ->assertForbidden();
});

it('blocks a basic plan from sending an interest directly', function () {
    $project = Project::factory()->create();
    actAsContractor('basic');

    Volt::test('tenant.projects')
        ->set('respondingProjectId', $project->id)
        ->set('message', 'I can do this next week, I have 10 years of experience.')
        ->call('sendInterest')
        ->assertForbidden();

    expect(ProjectInterest::count())->toBe(0);
});

it('lets a pro contractor send an interest and emails the homeowner', function () {
    $homeowner = Homeowner::factory()->create(['email' => 'ana@example.com']);
    $project = Project::factory()->for($homeowner)->create();
    actAsContractor('pro');

    Volt::test('tenant.projects')
        ->assertDontSeeHtml('data-upgrade-banner')
        ->call('openInterest', $project->id)
        ->assertSet('showInterestModal', true)
        ->set('message', 'Hi! I have done many roofs in Omaha and can start next week.')
        ->call('sendInterest')
        ->assertHasNoErrors()
        ->assertSet('showInterestModal', false);

    $interest = ProjectInterest::sole();

    expect($interest->tenant_id)->toBe('projectstenant')
        ->and($interest->status)->toBe(ProjectInterestStatus::Pending);

    Mail::assertSent(ProjectInterestReceived::class, fn (ProjectInterestReceived $mail) => $mail->hasTo('ana@example.com'));
});

it('requires a message and does not allow responding twice', function () {
    $project = Project::factory()->create();
    actAsContractor('pro');

    Volt::test('tenant.projects')
        ->call('openInterest', $project->id)
        ->set('message', 'short')
        ->call('sendInterest')
        ->assertHasErrors(['message' => 'min']);

    ProjectInterest::factory()->for($project)->create(['tenant_id' => 'projectstenant']);

    Volt::test('tenant.projects')
        ->assertSee('Already responded')
        ->set('respondingProjectId', $project->id)
        ->set('message', 'Trying to respond a second time to the same project.')
        ->call('sendInterest')
        ->assertHasErrors('message');

    expect(ProjectInterest::count())->toBe(1);
});

it('only lists open projects that the admin has not hidden', function () {
    Project::factory()->create(['title' => 'Open roof job']);
    Project::factory()->closed()->create(['title' => 'Closed fence job']);
    Project::factory()->hidden()->create(['title' => 'Hidden spam job']);
    actAsContractor('pro');

    Volt::test('tenant.projects')
        ->assertSee('Open roof job')
        ->assertDontSee('Closed fence job')
        ->assertDontSee('Hidden spam job');
});

it('shows projects in the contractor city first', function () {
    Project::factory()->create(['title' => 'Lincoln kitchen', 'city' => 'Lincoln']);
    Project::factory()->create(['title' => 'Omaha deck', 'city' => 'Omaha', 'created_at' => now()->subWeek()]);
    actAsContractor('pro', 'Omaha');

    Volt::test('tenant.projects')->assertSeeInOrder(['Omaha deck', 'Lincoln kitchen']);
});

it('reveals the homeowner contact only after the homeowner accepts', function () {
    actAsContractor('pro');
    $homeowner = Homeowner::factory()->create(['name' => 'Ana Torres', 'email' => 'ana@example.com', 'phone' => '(402) 555-0101']);
    $project = Project::factory()->for($homeowner)->create();
    $interest = ProjectInterest::factory()->for($project)->create(['tenant_id' => 'projectstenant']);

    Volt::test('tenant.projects')
        ->set('tab', 'mine')
        ->assertSee('Waiting for reply')
        ->assertDontSee('ana@example.com')
        ->assertDontSee('(402) 555-0101');

    $interest->update(['status' => ProjectInterestStatus::Accepted]);

    Volt::test('tenant.projects')
        ->set('tab', 'mine')
        ->assertSeeHtml('data-homeowner-contact')
        ->assertSee('Ana Torres')
        ->assertSee('ana@example.com')
        ->assertSee('(402) 555-0101');
});

it('emails the contractor when the homeowner accepts, but not when they decline', function () {
    $homeowner = Homeowner::factory()->create();
    $project = Project::factory()->for($homeowner)->create();
    $accepted = ProjectInterest::factory()->for($project)->create();
    $declined = ProjectInterest::factory()->for($project)->create();

    $this->actingAs($homeowner, 'homeowner');

    Volt::test('homeowner.projects.show', ['project' => $project])
        ->call('respond', $accepted->id, ProjectInterestStatus::Accepted->value)
        ->call('respond', $declined->id, ProjectInterestStatus::Declined->value);

    Mail::assertSent(ProjectInterestAccepted::class, 1);
    Mail::assertSent(ProjectInterestAccepted::class, fn (ProjectInterestAccepted $mail) => $mail->hasTo($accepted->tenant->email));
});

it('renders both emails with the right details', function () {
    $homeowner = Homeowner::factory()->create(['name' => 'Ana Torres', 'email' => 'ana@example.com', 'phone' => '(402) 555-0101']);
    $project = Project::factory()->for($homeowner)->create(['title' => 'Replace my roof']);
    $interest = ProjectInterest::factory()->for($project)->create(['message' => 'I can start on Monday.']);

    expect((new ProjectInterestReceived($interest))->render())
        ->toContain('Replace my roof')
        ->toContain('I can start on Monday.')
        ->toContain(route('homeowner.projects.show', $project));

    expect((new ProjectInterestAccepted($interest))->render())
        ->toContain('Ana Torres')
        ->toContain('ana@example.com')
        ->toContain('(402) 555-0101');
});

it('adds the projects module to the tenant menu and gives it to pro and enterprise', function () {
    actAsContractor('basic');

    Volt::test('tenant.projects')->assertSeeHtml('href="'.url('/projects').'"');

    expect(Tenant::hasFeature('basic', 'projects'))->toBeFalse()
        ->and(Tenant::hasFeature('pro', 'projects'))->toBeTrue()
        ->and(Tenant::hasFeature('enterprise', 'projects'))->toBeTrue();
});
