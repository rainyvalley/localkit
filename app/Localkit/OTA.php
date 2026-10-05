<?php

namespace App\Localkit;

use Exception;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OTA
{

    public function __construct()
    {
    }

    public function getAvailable(Device $device): ?array
    {
        if (in_array($device->device_type, $this->disabledDeviceTypes(), true)) {
            return null;
        }

        $currentFirmware = $device->firmware;
        $available = $this->firmwareByDevice($device);
        if (is_null($available)) {
            return null;
        }

        // Strictly newer only: an equal version must never re-offer, or the
        // device repeats the same internal reflash forever (a d3 was lost to
        // exactly this - it re-wrote its active slot with the same image and
        // the slot copy aborted mid-write).
        return version_compare((string) $available['version'], (string) $currentFirmware, '>') ? $available : null;
    }

    public function isAvailable(Device $device): bool
    {
        return $this->getAvailable($device) !== null;
    }

    public function loadRepository(): Collection
    {
        $repository = Http::get(sprintf('%s/%s', config('localkit.ota_repository'), 'repository.json'));

        if ($repository->successful()) {
            return collect($repository->json());
        }
        Log::error('Failed to load OTA repository');
        return collect([]);
    }

    public function toOTA($device): array
    {
        $detail = $this->firmwareByDevice($device)['detail'];
        $detail = Http::get($detail);
        if ($detail->successful()) {
            $result = $detail->json('result');

            if (config('localkit.firmware_proxy')) {
                $upstreamHost = parse_url(config('localkit.ota_repository'), PHP_URL_HOST);
                $result = json_decode(
                    str_replace($upstreamHost, 'api.eu-pet.com', json_encode($result)),
                    true
                );
            }

            return (array) $result;
        }
        throw new Exception('No valid data');
    }

    private function firmwareByDevice(Device $device): array
    {
        $repository = $this->loadRepository();
        return $repository->filter(fn($item) => $item['device_type'] === $device->device_type)->first();
    }

    /**
     * Device types whose OTA offers are suppressed entirely
     * (LOCALKIT_OTA_DISABLED_TYPES). Comma separated; empty string disables
     * nothing. Meant for device types whose repository entry has never been
     * validated on hardware - a failed internal reflash can leave the
     * device unbootable, with no recovery but serial access.
     */
    private function disabledDeviceTypes(): array
    {
        $raw = trim((string) config('localkit.ota_disabled_types', ''));

        if ($raw === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $raw)));
    }
}
