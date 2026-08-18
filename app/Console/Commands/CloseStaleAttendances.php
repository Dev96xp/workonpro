<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use Illuminate\Console\Command;

class CloseStaleAttendances extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:close-stale';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Closes attendance shifts left open from a previous day, treating them as a forgotten check-out.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $count = Attendance::whereNull('check_out')
            ->whereDate('check_in', '<', today())
            ->update(['check_out' => now()]);

        $this->info("Closed {$count} stale attendance shift(s).");

        return self::SUCCESS;
    }
}
