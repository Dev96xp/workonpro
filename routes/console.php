<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// A las 2:15am cualquier turno todavía abierto de un día anterior se trata como un olvido de marcar salida.
Schedule::command('tenants:run attendance:close-stale')->dailyAt('02:15')->withoutOverlapping();
