<?php

it('shows the welcome page in english by default', function () {
    $this->get('/welcome-v1')->assertSee('Get started')->assertDontSee('Comenzar ahora');
});

it('shows the welcome page in spanish when the locale cookie is spanish', function () {
    $this->withCookie('locale', 'es')
        ->get('/welcome-v1')
        ->assertSee('Comenzar ahora');
});

it('stores the chosen locale in a cookie from the language switch route', function () {
    $this->get('/lang/es')->assertCookie('locale', 'es');
});

it('ignores an invalid locale and keeps english', function () {
    $this->withCookie('locale', 'fr')
        ->get('/welcome-v1')
        ->assertSee('Get started');
});

it('shows the search page in english by default', function () {
    $this->get('/buscar')->assertSee('Search services')->assertDontSee('Buscar servicios');
});

it('shows the search page in spanish when the locale cookie is spanish', function () {
    $this->withCookie('locale', 'es')
        ->get('/buscar')
        ->assertSee('Buscar servicios');
});

it('shows the signup plans page in english by default', function () {
    $this->get('/signup')->assertSee('Choose your plan')->assertDontSee('Elige tu plan');
});

it('shows the signup plans page in spanish when the locale cookie is spanish', function () {
    $this->withCookie('locale', 'es')
        ->get('/signup')
        ->assertSee('Elige tu plan');
});

it('shows the signup create page in spanish when the locale cookie is spanish', function () {
    $this->withCookie('locale', 'es')
        ->get('/signup/start')
        ->assertSee('Crear tu negocio')
        ->assertDontSee('Create your business');
});
