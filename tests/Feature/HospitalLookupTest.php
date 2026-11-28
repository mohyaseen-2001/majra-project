<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HospitalLookupTest extends TestCase
{
    public function test_nearby_hospitals_are_loaded_locally_and_sorted_by_distance(): void
    {
        Http::preventStrayRequests();

        $response = $this->getJson('/api/hospitals?latitude=31.9539&longitude=35.9106&radius_km=15')
            ->assertOk()->assertJsonPath('source', 'OpenStreetMap / HOT')->assertJsonPath('stale', true);
        $distances = array_column($response->json('data'), 'distance_km');
        $this->assertNotEmpty($distances);
        $this->assertLessThanOrEqual(15, max($distances));
        $sorted = $distances;
        sort($sorted, SORT_NUMERIC);
        $this->assertSame($sorted, $distances);

        Http::assertNothingSent();
    }
}
