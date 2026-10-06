<?php

namespace App\Http\Controllers\Petkit;

use stdClass;
use App\Helpers\PetkitHeader;
use App\Http\Controllers\Controller;
use App\Http\Resources\DevOtaCheckResource;
use App\Http\Resources\DevOtaResource;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class DevOtaCheckController extends Controller
{

    public function __invoke(string $deviceType, Request $request)
    {

        $deviceId = PetkitHeader::petkitId($request->header('X-Device'));
        $device = Device::wherePetkitId($deviceId)->first();

        // Serve the offer on version-difference alone (the Gate), not the ota_state flag:
        // ota_state also forces the iot-info response to noresolv, which boot-loops devices
        // whose provisioning is already complete. Mirrors the Heartbeat path's gate logic.
        if ($device && app(\App\Localkit\OTA::class)->getAvailable($device)) {
            return new DevOtaResource($device);
        }

        if($device?->ota_state) {
            return new DevOtaResource($device);
        }


        $obj = new stdClass();
        $obj->result = new stdClass();
        return new DevOtaCheckResource($obj);
    }
}
