<?php

use App\Models\Homeowner;
use App\Models\Project;
use App\Models\ProjectInterest;
use App\Models\Subcategory;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use App\ProjectInterestStatus;
use App\ProjectStatus;
use App\ProjectUrgency;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Http;
use Livewire\Volt\Volt;

beforeEach(function () {
    Http::fake();
});

afterEach(function () {
    Tenant::query()->where('id', 'like', 'contractor%')->get()->each(fn (Tenant $tenant) => Tenant::withoutEvents(fn () => $tenant->delete()));
});

it('registers a homeowner, logs them in and sends them to post a project', function () {
    Volt::test('homeowner.register')
        ->set('name', 'Ana Torres')
        ->set('email', 'ana@example.com')
        ->set('phone', '(402) 555-0101')
        ->set('city', 'Omaha')
        ->set('state', 'Nebraska')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', true)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('homeowner.projects.create'));

    $homeowner = Homeowner::firstWhere('email', 'ana@example.com');

    expect($homeowner)->not->toBeNull()
        ->and($homeowner->city)->toBe('Omaha')
        ->and(auth('homeowner')->id())->toBe($homeowner->id)
        ->and(auth('web')->check())->toBeFalse();
});

it('requires the terms and a unique email to register', function () {
    Homeowner::factory()->create(['email' => 'taken@example.com']);

    Volt::test('homeowner.register')
        ->set('name', 'Ana')
        ->set('email', 'taken@example.com')
        ->set('city', 'Omaha')
        ->set('state', 'Nebraska')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasErrors(['email' => 'unique', 'accepted_terms' => 'accepted']);
});

it('logs a homeowner in with the homeowner guard and rejects a wrong password', function () {
    $homeowner = Homeowner::factory()->create(['email' => 'ana@example.com']);

    Volt::test('homeowner.login')
        ->set('email', 'ana@example.com')
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    Volt::test('homeowner.login')
        ->set('email', 'ana@example.com')
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('homeowner.dashboard'));

    expect(auth('homeowner')->id())->toBe($homeowner->id);
});

it('sends guests to the homeowner login instead of the dashboard', function () {
    $this->get(route('homeowner.dashboard'))->assertRedirect(route('homeowner.login'));
    $this->get(route('homeowner.projects.create'))->assertRedirect(route('homeowner.login'));
});

it('does not let a super admin into the homeowner dashboard', function () {
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin')
        ->get(route('homeowner.dashboard'))
        ->assertRedirect(route('homeowner.login'));
});

it('shows the homeowner only their own projects with the interested count', function () {
    $homeowner = Homeowner::factory()->create();
    $mine = Project::factory()->for($homeowner)->create(['title' => 'Replace my roof']);
    ProjectInterest::factory()->for($mine)->create();
    Project::factory()->create(['title' => 'Someone else project']);

    $this->actingAs($homeowner, 'homeowner')
        ->get(route('homeowner.dashboard'))
        ->assertOk()
        ->assertSee('Replace my roof')
        ->assertSee('1 interested contractor')
        ->assertDontSee('Someone else project');
});

it('publishes a project with the homeowner location prefilled', function () {
    $homeowner = Homeowner::factory()->create(['city' => 'Council Bluffs', 'state' => 'Iowa']);
    $subcategory = Subcategory::factory()->create();

    $this->actingAs($homeowner, 'homeowner');

    Volt::test('homeowner.projects.form')
        ->assertSet('city', 'Council Bluffs')
        ->assertSet('state', 'Iowa')
        ->set('title', 'Replace my roof')
        ->set('description', 'The roof is 20 years old and leaks when it rains hard.')
        ->set('subcategory_id', (string) $subcategory->id)
        ->set('urgency', ProjectUrgency::ThisMonth->value)
        ->call('save')
        ->assertHasNoErrors();

    $project = $homeowner->projects()->sole();

    expect($project->title)->toBe('Replace my roof')
        ->and($project->status)->toBe(ProjectStatus::Open)
        ->and($project->urgency)->toBe(ProjectUrgency::ThisMonth)
        ->and($project->budget)->toBeNull()
        ->and($project->city)->toBe('Council Bluffs');
});

it('validates the project and rejects inactive subcategories', function () {
    $this->actingAs(Homeowner::factory()->create(), 'homeowner');

    Volt::test('homeowner.projects.form')
        ->set('title', '')
        ->set('description', 'too short')
        ->set('subcategory_id', (string) Subcategory::factory()->inactive()->create()->id)
        ->set('urgency', 'whenever')
        ->set('budget', 'a-lot')
        ->call('save')
        ->assertHasErrors(['title', 'description', 'subcategory_id', 'urgency', 'budget']);
});

it('lets the homeowner edit their project but not someone else project', function () {
    $homeowner = Homeowner::factory()->create();
    $project = Project::factory()->for($homeowner)->create();
    $otherProject = Project::factory()->create();

    $this->actingAs($homeowner, 'homeowner');

    Volt::test('homeowner.projects.form', ['project' => $project])
        ->assertSet('title', $project->title)
        ->set('title', 'New title for my project')
        ->call('save')
        ->assertHasNoErrors();

    expect($project->fresh()->title)->toBe('New title for my project');

    $this->get(route('homeowner.projects.edit', $otherProject))->assertNotFound();
    $this->get(route('homeowner.projects.show', $otherProject))->assertNotFound();
});

it('lets the homeowner accept or decline interested contractors', function () {
    $homeowner = Homeowner::factory()->create();
    $project = Project::factory()->for($homeowner)->create();
    [$first, $second] = ProjectInterest::factory()->for($project)->count(2)->create();

    $this->actingAs($homeowner, 'homeowner');

    Volt::test('homeowner.projects.show', ['project' => $project])
        ->assertSee($first->tenant->name)
        ->assertSee($first->message)
        ->call('respond', $first->id, ProjectInterestStatus::Accepted->value)
        ->call('respond', $second->id, ProjectInterestStatus::Declined->value);

    expect($first->fresh()->status)->toBe(ProjectInterestStatus::Accepted)
        ->and($first->fresh()->responded_at)->not->toBeNull()
        ->and($second->fresh()->status)->toBe(ProjectInterestStatus::Declined);
});

it('cannot respond to interests from another project', function () {
    $homeowner = Homeowner::factory()->create();
    $project = Project::factory()->for($homeowner)->create();
    $foreignInterest = ProjectInterest::factory()->create();

    $this->actingAs($homeowner, 'homeowner');

    expect(fn () => Volt::test('homeowner.projects.show', ['project' => $project])
        ->call('respond', $foreignInterest->id, ProjectInterestStatus::Accepted->value))
        ->toThrow(ModelNotFoundException::class);

    expect($foreignInterest->fresh()->status)->toBe(ProjectInterestStatus::Pending);
});

it('lets the homeowner close and reopen a project', function () {
    $homeowner = Homeowner::factory()->create();
    $project = Project::factory()->for($homeowner)->create();

    $this->actingAs($homeowner, 'homeowner');

    $component = Volt::test('homeowner.projects.show', ['project' => $project])
        ->call('changeStatus', ProjectStatus::Closed->value);

    expect($project->fresh()->status)->toBe(ProjectStatus::Closed)
        ->and(Project::available()->count())->toBe(0);

    $component->call('changeStatus', ProjectStatus::Open->value);

    expect(Project::available()->pluck('id')->all())->toBe([$project->id]);
});

it('invites homeowners from the main page', function () {
    $this->get('/')
        ->assertSee('Need help at home? Post your project for free')
        ->assertSee(route('homeowner.register'), false);
});

it('keeps subcategories used by projects from being deleted', function () {
    $project = Project::factory()->create();

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    Volt::test('admin.categories.index')->call('deleteSubcategory', $project->subcategory_id);

    expect(Subcategory::find($project->subcategory_id))->not->toBeNull();
});

it('lists the registered homeowners with their project counts for the admin', function () {
    $ana = Homeowner::factory()->create(['name' => 'Ana Torres', 'email' => 'ana@example.com', 'city' => 'Omaha']);
    Project::factory()->for($ana)->count(2)->create();
    Project::factory()->for($ana)->closed()->create();
    Homeowner::factory()->create(['name' => 'Luis Perez', 'city' => 'Council Bluffs']);

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    $this->get(route('admin.homeowners.index'))
        ->assertOk()
        ->assertSee('Ana Torres')
        ->assertSee('ana@example.com')
        ->assertSee('2 abiertos')
        ->assertSee('Luis Perez');

    Volt::test('admin.homeowners.index')
        ->set('search', 'Council')
        ->assertSee('Luis Perez')
        ->assertDontSee('Ana Torres');
});

it('lets the admin delete a homeowner together with their projects', function () {
    $homeowner = Homeowner::factory()->create();
    Project::factory()->for($homeowner)->create();

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    Volt::test('admin.homeowners.index')->call('deleteHomeowner', $homeowner->id);

    expect(Homeowner::count())->toBe(0)
        ->and(Project::count())->toBe(0);
});

it('keeps the homeowners list away from guests', function () {
    $this->get(route('admin.homeowners.index'))->assertRedirect(route('admin.login'));
});

it('shows each homeowner projects in the admin list and lets the admin hide one', function () {
    $homeowner = Homeowner::factory()->create(['name' => 'Ana Torres']);
    $project = Project::factory()->for($homeowner)->create(['title' => 'Replace my roof']);
    ProjectInterest::factory()->for($project)->create();

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    $component = Volt::test('admin.homeowners.index')
        ->assertSee('Replace my roof')
        ->assertSee($project->subcategory->label())
        ->call('toggleProjectHidden', $project->id)
        ->assertSee('Oculto');

    expect($project->fresh()->is_hidden)->toBeTrue()
        ->and(Project::available()->count())->toBe(0);

    $component->call('toggleProjectHidden', $project->id);

    expect($project->fresh()->is_hidden)->toBeFalse();
});

it('lists every project in the admin projects module with filters and counters', function () {
    $roof = Project::factory()->create(['title' => 'Replace my roof', 'city' => 'Omaha']);
    Project::factory()->closed()->create(['title' => 'Build a fence', 'city' => 'Lincoln']);
    ProjectInterest::factory()->for($roof)->create(['message' => 'I can start on Monday.']);

    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    $this->get(route('admin.projects.index'))
        ->assertOk()
        ->assertSee('Replace my roof')
        ->assertSee('Build a fence')
        ->assertSee($roof->homeowner->name)
        ->assertSee('I can start on Monday.')
        ->assertSee('href="'.route('admin.projects.index').'"', false);

    Volt::test('admin.projects.index')
        ->set('status', ProjectStatus::Closed->value)
        ->assertSee('Build a fence')
        ->assertDontSee('Replace my roof')
        ->set('status', '')
        ->set('search', 'Omaha')
        ->assertSee('Replace my roof')
        ->assertDontSee('Build a fence');
});

it('lets the admin hide and delete a project from the projects module', function () {
    $project = Project::factory()->create();
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    Volt::test('admin.projects.index')->call('toggleProjectHidden', $project->id);
    expect($project->fresh()->is_hidden)->toBeTrue();

    Volt::test('admin.projects.index')->call('deleteProject', $project->id);
    expect(Project::find($project->id))->toBeNull();
});

it('keeps guests out of the admin projects module', function () {
    $this->get(route('admin.projects.index'))->assertRedirect(route('admin.login'));
});

it('links every admin dashboard card to its module', function () {
    Project::factory()->create();
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');

    $response = $this->get(route('admin.dashboard'))->assertOk();

    foreach (['admin.tenants.index', 'admin.homeowners.index', 'admin.projects.index', 'admin.categories.index', 'admin.plans.index', 'admin.settings.index'] as $routeName) {
        $response->assertSee('href="'.route($routeName).'"', false);
    }

    $response->assertSee('Dueños de casa')->assertSee('1 abiertos');
});
