<?php

namespace App\Listeners;

use App\Events\UserModified;
use Illuminate\Support\Facades\Log;

class CacheModifiedUser
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(UserModified $event): void
    {
        $modified_user = $event->user;
        $changed_fields = [];

        // TODO: add a check for the 'email' field, then implement calls to recreate the user in the
        // third-party provider
        if ($modified_user->wasChanged('name'))
            $changed_fields['name'] = $modified_user->name;
        if ($modified_user->wasChanged(['time_zone']))
            $changed_fields['time_zone'] = $modified_user->time_zone;
        if (count($changed_fields) == 0)
            return;
        $changed_fields['email'] = $modified_user->email;

        $cache_key = config('constants.user_sync.cache_key');
        $cached_value = cache()->get($cache_key, '[]');
        $user_modifications = json_decode($cached_value, true);
        $user_modifications[$modified_user->email] = $changed_fields;
        
        cache()->forever($cache_key, json_encode($user_modifications));
    }
}
