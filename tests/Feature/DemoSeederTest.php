<?php

namespace Tests\Feature;

use App\Models\Alert;
use App\Models\Company;
use App\Models\Report;
use App\Models\RoadClosure;
use App\Models\Subscription;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_populates_majra_examples_and_is_safe_to_rerun(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertSame(2, Company::count());
        $this->assertSame(4, Report::count());
        $this->assertSame(2, Alert::count());
        $this->assertSame(2, RoadClosure::count());
        $this->assertSame(2, Subscription::count());
    }
}
