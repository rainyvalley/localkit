<?php

namespace App\Http\Resources;

use App\Localkit\OTA;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class HeartbeatOtaResource extends PetkitHttpResource
{
    public static $wrap = 'result';

    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {

        $ts = time();

        $firmware = app(OTA::class)->getAvailable($this->resource);

        if (is_null($firmware)) {
            // No offer for this device right now (unknown type, disabled
            // type, or no newer version): answer with the plain heartbeat
            // shape instead of pointing the device at a firmware id that
            // may belong to a different product entirely.
            return (new HeartbeatResource($this->resource))->toArray($request);
        }

        $data = [
            'content' => json_encode([
                "msgType" => 0,
                "payload" => [
                    "firmwareId" => $firmware['id']
                ],
                "type" => "ota",
                "timestamp" => $ts
            ]),
            'time' => (time() * 1000),
            'timestamp' => $ts
        ];

        return [
            $data
        ];
    }
}
