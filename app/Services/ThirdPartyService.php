<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * This is a dummy service to interface with the third-party provider.
 */
class ThirdPartyService
{
    public function updateSubscriber($subscriber)
    {
        $api_payload = json_encode([
            'subscriber' => $subscriber
        ]);

        // Http::post('API_URL', $api_payload);

        $responseBody = '';
        return $responseBody;
    }

    /**
     * @param array $requests An array of strings for each request operation.
     * @param array $payloads An array of arrays for each request payload.
     */
    public function batchRequest(array $requests, array $payloads)
    {
        if (count($requests) != count($payloads))
            throw new \Exception('Request names and payloads must have the same count.', 1);

        $num_records = array_reduce($payloads, fn(int $carry, array $item) =>
            $carry + count($item),
        0);
        if ($num_records > 1000)
            throw new \Exception('Batch size is above 1000.', 1);

        $api_payload = ['batches' => []];

        $valid_request_names = ['subscribers', 'channels', 'communities'];
        foreach ($requests as $index => $request_name) {
            if (! in_array($request_name, $valid_request_names, true)) continue;

            array_push($api_payload['batches'], [
                $request_name => $payloads[$index]
            ]);
        }

        $api_payload = json_encode($api_payload);

        // Http::post('API_URL', $api_payload);

        $responseBody = '';
        return $responseBody;
    }
}
