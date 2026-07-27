<?php

use App\Jobs\FetchExchangeRates;
use App\Jobs\PostRecurringTransactions;
use App\Jobs\SendBillReminders;
use App\Jobs\TrainCategorizationModel;
use App\Models\Team;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::job(new PostRecurringTransactions)->dailyAt('00:05');
Schedule::job(new FetchExchangeRates)->dailyAt('01:00');
Schedule::job(new SendBillReminders)->dailyAt('08:00');
Schedule::call(function () {
    Team::query()->pluck('id')->each(fn (int $teamId) => TrainCategorizationModel::dispatch($teamId));
})->weeklyOn(0, '03:00');
