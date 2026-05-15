<?php

namespace App\Services;

use App\Models\Setting;

class DangerZoneService
{
    public function isInDangerZone(float $lat, float $lng): bool
    {
        $centerLat = (float) Setting::get('danger_zone_latitude', -8.7908);
        $centerLng = (float) Setting::get('danger_zone_longitude', 115.1553);
        $radius    = (int)   Setting::get('danger_zone_radius_meters', 500);

        return $this->haversineDistance($lat, $lng, $centerLat, $centerLng) <= $radius;
    }

    public function distanceFromCenter(float $lat, float $lng): float
    {
        $centerLat = (float) Setting::get('danger_zone_latitude', -8.7908);
        $centerLng = (float) Setting::get('danger_zone_longitude', 115.1553);
        return $this->haversineDistance($lat, $lng, $centerLat, $centerLng);
    }

    public function haversineDistance(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $R    = 6371000; // Earth radius in metres
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a    = sin($dLat / 2) ** 2
              + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon / 2) ** 2;
        return $R * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
