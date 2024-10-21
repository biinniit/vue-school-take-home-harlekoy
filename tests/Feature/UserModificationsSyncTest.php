<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use App\Events\UserModified;
use App\Listeners\CacheModifiedUser;

class UserModificationsSyncTest extends TestCase
{
    use RefreshDatabase;

    // test data
    private static $cache_key = 'user_modifications';
    private static $user1 = ['email' => 'alex@acme.com'];
    private static $modification1 = ['time_zone' => 'Europe/Amsterdam'];
    private static $modification2 = ['time_zone' => 'America/New_York'];
    private static $modification1_cached =
        '{"alex@acme.com":{"time_zone":"Europe\/Amsterdam","email":"alex@acme.com"}}';
    private static $modification2_cached =
        '{"alex@acme.com":{"time_zone":"America\/New_York","email":"alex@acme.com"}}';

    private CacheModifiedUser $listener;

    public function setUp(): void
    {
        parent::setUp();
        $this->listener = new CacheModifiedUser();
    }

    public function test_event_is_dispatched_on_single_user_update()
    {
        Event::fake();
        $user = User::factory()->create(static::$user1);
        $user->update(static::$modification1);
        event(new UserModified($user));

        Event::assertDispatched(UserModified::class, function ($event) use ($user) {
            return $event->user->id === $user->id;
        });
    }

    public function test_modification_is_cached_on_single_user_update()
    {
        Cache::spy();
        $user = User::factory()->create(static::$user1);
        $user->update(static::$modification1);
        $this->listener->handle(new UserModified($user));

        Cache::shouldHaveReceived('get')->with(static::$cache_key, '[]');
        Cache::shouldHaveReceived('forever')
            ->with(static::$cache_key, static::$modification1_cached);
    }
}
