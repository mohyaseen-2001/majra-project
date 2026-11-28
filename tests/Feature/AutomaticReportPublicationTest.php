<?php

namespace Tests\Feature;

use App\Models\Report;
use App\Services\GeminiFloodAnalyzer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class AutomaticReportPublicationTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('analysisCases')]
    public function test_only_clear_flood_reports_are_published(string $scene, bool $flood, float $confidence, int $quality, string $expected): void
    {
        config(['services.gemini.key' => 'test-key']);
        Storage::fake('public')->put('reports/test.jpg', 'fixture');
        $report = Report::factory()->create(['status' => 'pending', 'expires_at' => now()->addDay()]);
        $report->media()->create(['disk' => 'public', 'path' => 'reports/test.jpg', 'mime_type' => 'image/jpeg', 'size_bytes' => 7]);
        $result = json_encode([
            'scene_decision' => $scene, 'is_flood' => $flood,
            'hazard_type' => $flood ? 'flood' : ($scene === 'no_visible_hazard' ? 'other' : 'unknown'),
            'risk_level' => $flood ? 'high' : 'unknown',
            'confidence' => $confidence, 'image_quality_score' => $quality, 'notes' => 'Test only',
        ], JSON_THROW_ON_ERROR);
        Http::fake(['https://generativelanguage.googleapis.com/*' => Http::response([
            'steps' => [['type' => 'model_output', 'content' => [['type' => 'text', 'text' => $result]]]],
        ])]);

        app(GeminiFloodAnalyzer::class)->analyze($report);

        $this->assertSame($expected, $report->fresh()->status);
        $this->assertSame($expected === 'approved', $report->fresh()->isAutomaticallyPublished());
    }

    public static function analysisCases(): array
    {
        return [
            'clear flood' => ['visible_flood', true, 0.95, 85, 'approved'],
            'low confidence' => ['visible_flood', true, 0.60, 85, 'ai_checked'],
            'poor photo' => ['visible_flood', true, 0.95, 40, 'ai_checked'],
            'uncertain scene' => ['uncertain', false, 0.95, 85, 'ai_checked'],
            'clear scene without a hazard' => ['no_visible_hazard', false, 0.95, 85, 'rejected'],
        ];
    }
}
