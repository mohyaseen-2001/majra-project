<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class HospitalFinderService
{
    public function nearby(float $latitude, float $longitude, int $radiusKm = 15): array
    {
        $radiusKm = min(max($radiusKm, 1), 25);
        $key = sprintf('majra:hospitals:%s:%s:%d', round($latitude, 2), round($longitude, 2), $radiusKm);
        if ($cached = Cache::get($key)) {
            return $cached + ['stale' => false];
        }

        $query = '[out:json][timeout:15];(node["amenity"="hospital"](around:'.($radiusKm * 1000).','.$latitude.','.$longitude.');way["amenity"="hospital"](around:'.($radiusKm * 1000).','.$latitude.','.$longitude.');relation["amenity"="hospital"](around:'.($radiusKm * 1000).','.$latitude.','.$longitude.'););out center tags;';

        try {
            $data = Http::asForm()->acceptJson()->connectTimeout(4)->timeout(18)
                ->post('https://overpass-api.de/api/interpreter', ['data' => $query])->throw()->json();

            $hospitals = collect($data['elements'] ?? [])->map(function (array $place) {
                $lat = $place['lat'] ?? $place['center']['lat'] ?? null;
                $lon = $place['lon'] ?? $place['center']['lon'] ?? null;
                if ($lat === null || $lon === null) {
                    return null;
                }

                return [
                    'id' => $place['id'] ?? null,
                    'name' => $place['tags']['name'] ?? $place['tags']['name:ar'] ?? 'مستشفى غير مسمّى',
                    'latitude' => (float) $lat,
                    'longitude' => (float) $lon,
                    'phone' => $place['tags']['phone'] ?? $place['tags']['contact:phone'] ?? null,
                    'emergency' => ($place['tags']['emergency'] ?? '') === 'yes',
                ];
            })->filter()->unique('id')->values()->all();

            $payload = ['available' => true, 'source' => 'OpenStreetMap / Overpass', 'updated_at' => now()->toIso8601String(), 'data' => $hospitals, 'stale' => false];
            Cache::put($key, $payload, now()->addMinutes(30));
            Cache::put($key.':last', $payload, now()->addDays(2));

            return $payload;
        } catch (\Throwable) {
            $last = Cache::get($key.':last');
            if (is_array($last)) {
                return $last + ['stale' => true];
            }

            return ['available' => false, 'source' => 'OpenStreetMap / Overpass', 'message' => 'تعذر جلب المستشفيات القريبة. تحقق من المصادر الرسمية عند الحاجة.', 'updated_at' => null, 'data' => [], 'stale' => false];
        }
    }
}
