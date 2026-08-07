<?php

use App\Mail\TenantNotification;
use App\Models\Setting;
use App\Models\SuperAdmin;
use App\Models\Tenant;
use Illuminate\Support\Facades\Mail;
use Livewire\Volt\Volt;

beforeEach(function () {
    $this->actingAs(SuperAdmin::factory()->create(), 'super_admin');
});

it('saves the notifications from-email setting', function () {
    Volt::test('admin.settings.notifications')
        ->set('fromEmail', 'notificaciones@workonpro.com')
        ->call('saveFromEmail');

    expect(Setting::get('notifications_from_email'))->toBe('notificaciones@workonpro.com');
});

it('sends a notification email only to the selected tenants', function () {
    Mail::fake();

    $tenantA = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'notifya', 'name' => 'Notify A', 'email' => 'a@example.com', 'status' => 'active', 'plan' => 'pro',
    ]));
    $tenantB = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'notifyb', 'name' => 'Notify B', 'email' => 'b@example.com', 'status' => 'active', 'plan' => 'pro',
    ]));

    Volt::test('admin.settings.notifications')
        ->set('selected', [$tenantA->id])
        ->set('subject', 'Nueva función')
        ->set('body', 'Hola, tenemos algo nuevo.')
        ->call('send')
        ->assertSet('sentMessage', 'Mensaje enviado a 1 negocio(s).');

    Mail::assertQueued(TenantNotification::class, fn ($mail) => $mail->hasTo($tenantA->email));
    Mail::assertNotQueued(TenantNotification::class, fn ($mail) => $mail->hasTo($tenantB->email));

    Tenant::withoutEvents(fn () => $tenantA->delete());
    Tenant::withoutEvents(fn () => $tenantB->delete());
});

it('sends a notification to every active tenant when "todos" is selected', function () {
    Mail::fake();

    $tenantA = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'notifyall1', 'name' => 'All 1', 'email' => 'all1@example.com', 'status' => 'active', 'plan' => 'pro',
    ]));
    $tenantB = Tenant::withoutEvents(fn () => Tenant::create([
        'id' => 'notifyall2', 'name' => 'All 2', 'email' => 'all2@example.com', 'status' => 'active', 'plan' => 'pro',
    ]));

    Volt::test('admin.settings.notifications')
        ->set('sendToAll', true)
        ->set('subject', 'Aviso general')
        ->set('body', 'Mensaje para todos.')
        ->call('send');

    Mail::assertQueued(TenantNotification::class, 2);

    Tenant::withoutEvents(fn () => $tenantA->delete());
    Tenant::withoutEvents(fn () => $tenantB->delete());
});

it('requires at least one recipient before sending', function () {
    Volt::test('admin.settings.notifications')
        ->set('subject', 'Sin destinatarios')
        ->set('body', 'Este no debería enviarse.')
        ->call('send')
        ->assertHasErrors('selected');
});
