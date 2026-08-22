<?php

it('shows the business card page', function () {
    $this->get('/businesscard')
        ->assertOk()
        ->assertSee('Ricardo Ramirez')
        ->assertSee('Fundador')
        ->assertSee('(770) 412-2535');
});

it('links the name to the website', function () {
    $this->get('/businesscard')
        ->assertSee('href="'.route('home').'"', false);
});

it('downloads a vcard with the contact info', function () {
    $response = $this->get('/businesscard/vcard');

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/vcard; charset=utf-8')
        ->assertSee('FN:Ricardo Ramirez')
        ->assertSee('ORG:Workon')
        ->assertSee('TEL;TYPE=CELL,VOICE:+17704122535');
});
