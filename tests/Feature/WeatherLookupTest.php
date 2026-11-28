<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WeatherLookupTest extends TestCase
{
    public function test_weather_matches_the_current_hour_and_preserves_last_update_on_outage(): void
    {
        $endpoint = '/api/weather?latitude=31.95&longitude=35.91';
        Http::fake(['https://api.open-meteo.com/*' => Http::sequence()
            ->push([
                'timezone' => 'Asia/Amman',
                'current' => ['time' => '2026-11-28T10:15', 'temperature_2m' => 18, 'weather_code' => 61],
                'hourly' => ['time' => ['2026-11-28T10:00', '2026-11-28T11:00'], 'precipitation_probability' => [75, 20]],
            ])
            ->push([], 503)]);

        $this->getJson($endpoint)->assertOk()->assertJsonPath('precipitation_probability', 75)->assertJsonPath('stale', false);
        $this->travel(13)->minutes();
        $this->getJson($endpoint)->assertOk()->assertJsonPath('stale', true)->assertJsonPath('updated_at', '2026-11-28T10:15');
        Http::assertSentCount(2);
    }

    public function test_weather_rejects_invalid_coordinates_and_handles_unavailable_data(): void
    {
        Http::fake(['https://api.open-meteo.com/*' => Http::response(['unexpected' => true])]);
        $this->getJson('/api/weather?latitude=0&longitude=0')->assertUnprocessable();
        $this->getJson('/api/weather?latitude=29.55&longitude=35.03')->assertStatus(503)->assertJsonPath('available', false);
        Http::assertSentCount(1);
    }
}
