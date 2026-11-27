<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HospitalLookupTest extends TestCase
{
    public function test_secondary_overpass_instance_is_used_when_primary_fails(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'https://overpass-api.de/*' => Http::response(status: 406),
            'https://overpass.private.coffee/*' => Http::response(['elements' => [
                ['id' => 12, 'lat' => 31.95, 'lon' => 35.91, 'tags' => ['name' => 'Example Hospital']],
            ]]),
        ]);

        $this->getJson('/api/hospitals?latitude=31.95&longitude=35.91')
            ->assertOk()->assertJsonPath('data.0.name', 'Example Hospital');

        Http::assertSentCount(2);
    }
}
