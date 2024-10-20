<?php

namespace App\Listeners;

use App\Events\UserModified;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

/**
 * We use the "array" cache store for fastest cache storage and retrieval. This doesn't pose a
 * memory balloon issue because we only expect to have about 1,000 user modifications (< 300KB)
 * cached at any given time.
 */
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

        // TODO: add 'email', then implement calls to recreate the user in third-party provider
        if (! $modified_user->wasChanged(['name', 'time_zone'])) {
            return;
        }
        
        cache()->store('array')->forever($modified_user->email, json_encode([
            'name' => $modified_user->name,
            'time_zone' => $modified_user->time_zone
        ]));
    }
}
