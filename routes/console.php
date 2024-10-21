<?php

use App\Models\User;
use App\Services\ThirdPartyService;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

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

Artisan::command('app:sync-user-modifications {--delay=0 : Number of seconds to delay command}',
    function(ThirdPartyService $sync_service) {
        // Duplicate invocations of the command will be delayed for half the scheduled time.
        // This is a cron syntax workaround.
        sleep(intval($this->option('delay')));
        
        // get the set of pending modifications from the cache
        $cache_key = config('constants.user_sync.cache_key');
        $cached_value = cache()->get($cache_key, '[]');
        $user_modifications = json_decode($cached_value, true);
        Log::channel('scheduled')
            ->info(sprintf('Found %d pending user modifications.', count($user_modifications)));

        // call third-party API
        try {
            $sync_modifications = array_slice($user_modifications, 0, 1000);
            if (count($sync_modifications) > 30) {
                $sync_service->batchRequest(['subscribers'], [array_values($sync_modifications)]);
            } else foreach ($sync_modifications as $modification) {
                // send requests individually (limit 3,600/hr)
                $sync_service->updateSubscriber($modification);
            }

            Log::channel('scheduled')->info('Modifications synced successfully.');
            $this->info('Modifications synced successfully.');
        } catch (\Exception $e) {
            Log::channel('scheduled')->error('{code}: {message}', [
                'code' => $e->getCode(),
                'message' => $e->getMessage()
            ]);
            $this->error(sprintf('Could not sync modifications. See log file: %s.',
                config('logging.scheduled.path')));
        }

        // remove synced modifications from the cache
        $user_modifications = array_diff_key($user_modifications, $sync_modifications);
        Log::channel('scheduled')->info(sprintf('Completed with %d pending user modifications.',
            count($user_modifications)));
        cache()->forever($cache_key, json_encode($user_modifications));
    }
)->purpose('Sync cached user modifications with third-party API');
