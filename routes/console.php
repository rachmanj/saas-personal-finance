<?php

use App\Jobs\FetchExchangeRates;
use App\Jobs\PostRecurringTransactions;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PostRecurringTransactions)->dailyAt('00:05');
Schedule::job(new FetchExchangeRates)->dailyAt('01:00');
