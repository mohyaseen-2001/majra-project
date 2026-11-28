<?php

namespace App\Services;

class HospitalFinderService
{
    public function nearby(float $latitude, float $longitude, int $radiusKm = 15): array
    {
        $radiusKm = min(max($radiusKm, 1), 25);
        $path = resource_path('data/jordan-hospitals.json');
        $snapshot = is_readable($path) ? json_decode(file_get_contents($path), true) : null;
        if (empty($snapshot['hospitals'])) {
            return ['available' => false, 'source' => 'OpenStreetMap / HOT', 'updated_at' => null, 'data' => [], 'stale' => false];
        }

        $nearby = [];
        foreach ($snapshot['hospitals'] as $hospital) {
            $distance = $this->distanceKm($latitude, $longitude, $hospital['latitude'], $hospital['longitude']);
            if ($distance <= $radiusKm) {
                $nearby[] = $hospital + ['distance_km' => $distance, 'phone' => null, 'emergency' => false];
            }
        }

        usort($nearby, fn (array $left, array $right) => $left['distance_km'] <=> $right['distance_km']);

        return [
            'available' => true,
            'source' => $snapshot['source'],
            'source_url' => $snapshot['source_url'],
            'updated_at' => $snapshot['updated_at'],
            'data' => $nearby,
            'stale' => true,
        ];
    }

    private function distanceKm(float $latitude, float $longitude, float $hospitalLatitude, float $hospitalLongitude): float
    {
        $latitudeDelta = deg2rad($hospitalLatitude - $latitude);
        $longitudeDelta = deg2rad($hospitalLongitude - $longitude);
        $a = sin($latitudeDelta / 2) ** 2
            + cos(deg2rad($latitude)) * cos(deg2rad($hospitalLatitude)) * sin($longitudeDelta / 2) ** 2;

        return 6371.0088 * 2 * asin(sqrt(min(1, $a)));
    }
}
