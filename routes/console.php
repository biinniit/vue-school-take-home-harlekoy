<?php

use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

/*
|--------------------------------------------------------------------------
| Console Routes
|--------------------------------------------------------------------------
|
| This file is where you may define all of your Closure based console
| commands. Each Closure is bound to a command instance allowing a
| simple approach to interacting with each command's IO methods.
|
*/

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:reset-user {user : The ID of the user}', function (string $user) {
    try {
        $userObj = User::findOrFail($user);
        $userObj->name = fake()->name();
        $userObj->time_zone = fake()->randomElement(UserFactory::getTimeZones());
        $userObj->save();
        $this->info('User reset successfully.');
    } catch (\Exception $e) {
        $this->error('User could not be reset.');
        $this->error($e->getMessage());
    }
})->purpose('Reset user\'s name and time zone randomly');
