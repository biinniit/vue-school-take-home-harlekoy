<?php

namespace App\Console\Commands;

use App\Http\Controllers\ThirdPartyController;
use App\Services\ThirdPartyService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SyncUserModifications extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:sync-user-modifications
                            {--delay=0 : Number of seconds to delay command}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sync cached user modifications with third-party API';

    protected $userSyncService;

    public function __construct()
    {
        parent::__construct();
        $this->userSyncService = new ThirdPartyService();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // Duplicate invocations of the command will be delayed for half the scheduled time.
        // This is a cron syntax workaround.
        sleep(intval($this->option('delay')));
        
        // get synced modifications from the cache
        $cache_key = config('constants.user_sync.cache_key');
        $cached_value = cache()->store('array')->get($cache_key, '[]');
        $user_modifications = json_decode($cached_value, true);
        Log::channel('scheduled')->info('Found {n} pending user modifications.', [
            'n' => count($user_modifications),
        ]);

        // call third-party API
        try {
            $sync_modifications = array_slice($user_modifications, 0, 1000);
            if (count($sync_modifications) > 30) {
                $this->userSyncService->batchRequest(['subscribers'], [$sync_modifications]);
            } else foreach ($sync_modifications as $modification) {
                // send requests individually (limit 3,600/hr)
                $this->userSyncService->updateSubscriber($modification);
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
        array_diff($user_modifications, $sync_modifications);
        Log::channel('scheduled')->info('Completed with {n} pending user modifications.', [
            'n' => count($user_modifications),
        ]);
        cache()->store('array')->forever($cache_key, json_encode($user_modifications));
    }
}
