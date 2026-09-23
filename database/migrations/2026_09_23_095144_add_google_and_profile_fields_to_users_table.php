<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id')->nullable()->unique()->after('id');
            $table->text('avatar')->nullable()->after('email');
            $table->string('headline', 255)->nullable()->after('avatar');
            $table->boolean('is_online')->default(false)->after('headline');
            $table->timestamp('last_active_at')->nullable()->after('is_online');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['google_id', 'avatar', 'headline', 'is_online', 'last_active_at']);
        });
    }
};
