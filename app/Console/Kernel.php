<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(Schedule $schedule): void
    {
        // Run health checks for each interval separately (and limit work per schedule)
        $schedule->command('health:check 1')->everyMinute();
        $schedule->command('health:check 5')->everyFiveMinutes();
        $schedule->command('health:check 15')->everyFifteenMinutes();
        $schedule->command('health:check 30')->everyThirtyMinutes();
        $schedule->command('health:check 60')->hourly();

        // Prune old check history daily
        $schedule->command('health:prune')->daily();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
