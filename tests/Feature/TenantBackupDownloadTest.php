<?php

use App\Models\SuperAdmin;
use App\Models\Tenant;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');
});

it('shows an error instead of a broken download when mysqldump is not available', function () {
    config(['services.mysqldump.path' => 'this-binary-does-not-exist']);

    $tenant = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'backuptenant',
        'name' => 'Backup Test',
        'email' => 'backup@example.com',
        'status' => 'active',
        'plan' => 'pro',
    ]));

    Volt::test('admin.tenants.index')
        ->call('downloadBackup', $tenant->id)
        ->assertSet('backupError', 'No se encontró la herramienta mysqldump en el servidor.');

    Tenant::withoutEvents(fn () => $tenant->delete());
});
