<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rich_addons', function (Blueprint $table): void {
            $table->id();
            $table->string('addon_id', 100)->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('version', 50)->default('1.0.0');
            $table->string('author')->nullable();
            $table->string('repository')->nullable();
            $table->string('tier', 30)->default('free');
            $table->string('status', 30)->default('inactive');
            $table->string('license_key')->nullable();
            $table->json('manifest')->nullable();
            $table->string('installed_path')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_addons');
    }
};
