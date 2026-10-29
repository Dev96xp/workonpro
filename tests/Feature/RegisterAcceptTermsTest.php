<?php

use App\Models\Tenant;
use Livewire\Volt\Volt;

afterEach(function () {
    Tenant::query()->where('id', 'termscheck')->delete();
});

it('requires accepting the terms before creating a business', function () {
    Volt::test('register.create')
        ->set('plan', 'basic')
        ->set('business_name', 'Terms Check Co')
        ->set('subdomain', 'termscheck')
        ->set('email', 'termscheck@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', false)
        ->call('checkout')
        ->assertHasErrors(['accepted_terms' => 'accepted']);

    expect(Tenant::find('termscheck'))->toBeNull();
});

it('creates the business once the terms are accepted', function () {
    Volt::test('register.create')
        ->set('plan', 'basic')
        ->set('business_name', 'Terms Check Co')
        ->set('subdomain', 'termscheck')
        ->set('email', 'termscheck@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->set('accepted_terms', true)
        ->call('checkout')
        ->assertHasNoErrors();

    expect(Tenant::find('termscheck'))->not->toBeNull();
});
