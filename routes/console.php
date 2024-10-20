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
    $userObj = User::find($user);
    $userObj->name = fake()->name();
    $userObj->timezone = fake()->randomElement(UserFactory::getTimezones());
    $userObj->save();
})->purpose('Reset user\'s firstname, lastname, and timezone randomly');
