<?php

it('shows the welcome page in spanish by default', function () {
    $this->get('/')->assertSee('Comenzar ahora');
});

it('switches to english after visiting the language switch route', function () {
    $this->get('/lang/en');

    $this->get('/')->assertSee('Get started')->assertDontSee('Comenzar ahora');
});

it('ignores an invalid locale and keeps spanish', function () {
    $this->get('/lang/fr');

    $this->get('/')->assertSee('Comenzar ahora');
});

it('shows the search page in spanish by default', function () {
    $this->get('/buscar')->assertSee('Buscar servicios');
});

it('shows the search page in english after switching locale', function () {
    $this->get('/lang/en');

    $this->get('/buscar')->assertSee('Search services')->assertDontSee('Buscar servicios');
});
