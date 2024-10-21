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

    public function test_1000_users_are_synced_on_multiple_user_updates()
    {
        // generate and cache 1001 modifications
        $modifications = [];
        for ($i = 0 ; $i < 1001 ; ++$i) {
            $email = fake()->unique()->email();
            $modifications[$email] = [
                'email' => $email,
                'time_zone' => fake()->randomElement(User::factory()->getTimeZones()),
            ];
        }
        Cache::forever(static::$cache_key, json_encode($modifications));

        $this->artisan('app:sync-user-modifications')->assertSuccessful();
        $pending_modifications = Cache::get(static::$cache_key);
        // only 1 user left, out of 1001
        $this->assertEquals(1, count(json_decode($pending_modifications, true)));
    }

    public function test_all_users_are_synced_on_few_user_updates()
    {
        // generate and cache 101 modifications
        $modifications = [];
        for ($i = 0 ; $i < 101 ; ++$i) {
            $email = fake()->unique()->email();
            $modifications[$email] = [
                'email' => $email,
                'time_zone' => fake()->randomElement(User::factory()->getTimeZones()),
            ];
        }
        Cache::forever(static::$cache_key, json_encode($modifications));

        $this->artisan('app:sync-user-modifications')->assertSuccessful();
        $pending_modifications = Cache::get(static::$cache_key);
        // no user left
        $this->assertEquals(0, count(json_decode($pending_modifications, true)));
    }
}
