<?php

use App\Models\SuperAdmin;

test('admin login screen can be rendered', function () {
    $response = $this->get('/admin/login');

    $response->assertStatus(200);
});

test('super admins can authenticate and are redirected to the home page', function () {
    $admin = SuperAdmin::factory()->create();

    $response = $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('home'));
    $this->assertAuthenticatedAs($admin, 'super_admin');
});

test('super admins can not authenticate with invalid password', function () {
    $admin = SuperAdmin::factory()->create();

    $response = $this->post('/admin/login', [
        'email' => $admin->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('email');
    $this->assertGuest('super_admin');
});

test('super admins are redirected to the home page after logout', function () {
    $admin = SuperAdmin::factory()->create();

    $response = $this->actingAs($admin, 'super_admin')->post('/admin/logout');

    $response->assertRedirect(route('home'));
    $this->assertGuest('super_admin');
});
