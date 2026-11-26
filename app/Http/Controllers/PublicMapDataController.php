<?php

namespace App\Http\Controllers;

use App\Services\HospitalFinderService;
use App\Services\OpenMeteoService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PublicMapDataController extends Controller
{
    public function weather(Request $request, OpenMeteoService $weather): JsonResponse
    {
        $location = $request->validate([
            'latitude' => ['required', 'numeric', 'between:29,33.5'],
            'longitude' => ['required', 'numeric', 'between:34.8,39.4'],
        ]);
        $data = $weather->current((float) $location['latitude'], (float) $location['longitude']);
        if ($data['available'] ?? false) {
            $data['condition'] = $weather->conditionLabel(isset($data['current']['weather_code']) ? (int) $data['current']['weather_code'] : null);
        }

        return response()->json($data, ($data['available'] ?? false) ? 200 : 503);
    }

    public function hospitals(Request $request, HospitalFinderService $hospitals): JsonResponse
    {
        $location = $request->validate([
            'latitude' => ['required', 'numeric', 'between:29,33.5'],
            'longitude' => ['required', 'numeric', 'between:34.8,39.4'],
            'radius_km' => ['sometimes', 'integer', 'between:1,25'],
        ]);
        $data = $hospitals->nearby((float) $location['latitude'], (float) $location['longitude'], (int) ($location['radius_km'] ?? 15));

        return response()->json($data, ($data['available'] ?? false) ? 200 : 503);
    }
}
