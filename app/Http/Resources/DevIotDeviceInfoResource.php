<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DevIotDeviceInfoResource extends PetkitHttpResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $iotInstanceId = 'iot-600a5gmp';

        // The device reaches the Localkit broker via the per-device subdomain record
        // ({subdomain}.iot-as-mqtt.eu-central-1.aliyuncs.com -> broker IP in DNS), the same
        // host shape DevOnlyIotDeviceInfoResource serves. The old .mqtt.iothub. host has no
        // such record and routes MQTT to the unreachable Aliyun instance instead.
        $mqtt = sprintf('%s.iot-as-mqtt.eu-central-1.aliyuncs.com', $this->mqtt_subdomain ?? $iotInstanceId);
        $productKey = $this->mqtt_subdomain ?? Str::of(md5($this->petkit_id))->substr(0, 10);
        if ($this->mqttShouldFail()) {
            $mqtt = self::MQTT_NO_RESOLV;
            $productKey = self::MQTT_NO_RESOLV;
        }
        return [
            'id' => $this->petkit_id,
            'deviceName' => sprintf('d_%s_%s', $this->device_type, $this->serial_number),
            'deviceSecret' => $this->secret,
            'iotInstanceId' => $iotInstanceId,
            'productKey' => $productKey,
            'mqttHost' => $mqtt,
            'createdAt' => $this->created_at->timestamp * 1000,
            'type' => 1,
            'regionId' => 'eu-central-1',
        ];
    }

}
