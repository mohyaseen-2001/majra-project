<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class OpenMeteoService
{
    public function current(float $latitude, float $longitude): array
    {
        $key = sprintf('majra:weather:%s:%s', round($latitude, 2), round($longitude, 2));
        if ($cached = Cache::get($key)) {
            return $cached + ['stale' => false];
        }

        try {
            $response = Http::acceptJson()->connectTimeout(4)->timeout(8)->get('https://api.open-meteo.com/v1/forecast', [
                'latitude' => $latitude,
                'longitude' => $longitude,
                'current' => 'temperature_2m,relative_humidity_2m,precipitation,rain,showers,weather_code,wind_speed_10m',
                'hourly' => 'precipitation_probability',
                'forecast_days' => 1,
                'timezone' => 'Asia/Amman',
            ])->throw()->json();

            $payload = [
                'available' => true,
                'source' => 'Open-Meteo',
                'updated_at' => $response['current']['time'] ?? now()->toIso8601String(),
                'timezone' => $response['timezone'] ?? 'Asia/Amman',
                'current' => $response['current'] ?? [],
                'precipitation_probability' => $this->currentHourProbability($response),
                'stale' => false,
            ];
            Cache::put($key, $payload, now()->addMinutes(12));
            Cache::put($key.':last', $payload, now()->addDays(3));

            return $payload;
        } catch (\Throwable) {
            $last = Cache::get($key.':last');
            if (is_array($last)) {
                return $last + ['stale' => true];
            }

            return ['available' => false, 'source' => 'Open-Meteo', 'message' => 'بيانات الطقس غير متاحة حالياً.', 'updated_at' => null, 'stale' => false];
        }
    }

    public function conditionLabel(?int $code): string
    {
        return match ($code) {
            0 => 'سماء صافية', 1, 2 => 'غائم جزئياً', 3 => 'غائم', 45, 48 => 'ضباب',
            51, 53, 55, 56, 57 => 'رذاذ', 61, 63, 65, 66, 67 => 'أمطار',
            71, 73, 75, 77 => 'تساقط ثلجي', 80, 81, 82 => 'زخات مطر', 85, 86 => 'زخات ثلج',
            95, 96, 99 => 'عواصف رعدية', default => 'حالة متغيرة',
        };
    }

    private function currentHourProbability(array $response): ?int
    {
        $times = $response['hourly']['time'] ?? [];
        $values = $response['hourly']['precipitation_probability'] ?? [];
        $currentTime = $response['current']['time'] ?? null;
        if (! $currentTime || ! count($times)) {
            return null;
        }

        $index = array_search($currentTime, $times, true);

        return $index === false || ! isset($values[$index]) ? null : (int) $values[$index];
    }
}
