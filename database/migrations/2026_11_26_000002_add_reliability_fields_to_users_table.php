<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedInteger('reliability_points')->default(0)->after('role');
            $table->unsignedSmallInteger('false_report_count')->default(0)->after('reliability_points');
            $table->boolean('is_suspended')->default(false)->after('false_report_count');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['reliability_points', 'false_report_count', 'is_suspended']);
        });
    }
};
