<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->boolean('is_false_report')->default(false)->after('status')->index();
        });

        Schema::create('report_confirmations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('report_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('choice', 24);
            $table->timestamps();
            $table->unique(['report_id', 'user_id']);
            $table->index(['report_id', 'choice']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_confirmations');
        Schema::table('reports', function (Blueprint $table) {
            $table->dropIndex(['is_false_report']);
            $table->dropColumn('is_false_report');
        });
    }
};
