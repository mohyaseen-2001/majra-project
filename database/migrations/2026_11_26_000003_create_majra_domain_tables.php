<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('registration_number')->nullable()->unique();
            $table->string('business_type', 80)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('website')->nullable();
            $table->string('status', 24)->default('pending')->index();
            $table->text('admin_notes')->nullable();
            $table->timestamps();
        });

        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->uuid('public_id')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 140);
            $table->text('description');
            $table->string('hazard_type', 40)->default('flood')->index();
            $table->string('risk_level', 24)->default('unknown')->index();
            $table->string('status', 24)->default('pending')->index();
            $table->string('source', 24)->default('citizen')->index();
            $table->string('area_name', 120)->nullable()->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->timestamp('ai_checked_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'risk_level', 'created_at']);
            $table->index(['latitude', 'longitude']);
        });

        Schema::create('report_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->string('disk', 40)->default('public');
            $table->string('path');
            $table->string('mime_type', 120)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->char('sha256', 64)->nullable()->index();
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('ai_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('duplicate_of_report_id')->nullable()->constrained('reports')->nullOnDelete();
            $table->string('provider', 40)->default('gemini');
            $table->string('status', 24)->default('pending')->index();
            $table->boolean('is_flood')->nullable();
            $table->boolean('is_duplicate')->default(false);
            $table->string('hazard_type', 40)->nullable();
            $table->string('risk_level', 24)->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->json('observations')->nullable();
            $table->text('admin_notes')->nullable();
            $table->longText('raw_response')->nullable();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
            $table->index(['report_id', 'created_at']);
        });

        Schema::create('reliability_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('report_id')->nullable()->constrained()->nullOnDelete();
            $table->integer('points');
            $table->string('reason', 40)->index();
            $table->string('description')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('badges', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->string('icon', 60)->nullable();
            $table->unsignedInteger('points_required')->default(0);
            $table->timestamps();
        });

        Schema::create('user_badges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('badge_id')->constrained()->cascadeOnDelete();
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamps();
            $table->unique(['user_id', 'badge_id']);
        });

        Schema::create('road_closures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 140);
            $table->text('description')->nullable();
            $table->string('area_name', 120)->index();
            $table->string('status', 24)->default('active')->index();
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);
            $table->string('diversion')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('title', 140);
            $table->text('message');
            $table->string('severity', 24)->default('warning')->index();
            $table->string('area_name', 120)->nullable()->index();
            $table->string('source', 24)->default('admin');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('published_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->timestamps();
        });

        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 60)->unique();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->char('currency', 3)->default('JOD');
            $table->string('interval', 20)->default('month');
            $table->json('features')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subscription_plan_id')->constrained()->restrictOnDelete();
            $table->string('status', 24)->default('pending')->index();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_subscription_id')->nullable()->unique();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['company_id', 'status']);
        });

        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 10, 2);
            $table->char('currency', 3)->default('JOD');
            $table->string('status', 24)->default('pending')->index();
            $table->string('provider', 24)->default('stripe');
            $table->string('checkout_session_id')->nullable()->unique();
            $table->string('payment_intent_id')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('company_areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('area_name', 120);
            $table->decimal('center_latitude', 10, 7)->nullable();
            $table->decimal('center_longitude', 10, 7)->nullable();
            $table->unsignedSmallInteger('radius_km')->default(10);
            $table->boolean('alerts_enabled')->default(true);
            $table->timestamps();
            $table->unique(['company_id', 'area_name']);
        });

        Schema::create('contacts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->string('email')->index();
            $table->string('subject', 160);
            $table->text('message');
            $table->string('status', 24)->default('new')->index();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contacts');
        Schema::dropIfExists('company_areas');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('subscription_plans');
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('road_closures');
        Schema::dropIfExists('user_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('reliability_transactions');
        Schema::dropIfExists('ai_analyses');
        Schema::dropIfExists('report_media');
        Schema::dropIfExists('reports');
        Schema::dropIfExists('companies');
    }
};
