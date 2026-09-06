<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rich_addons', function (Blueprint $table): void {
            if (! Schema::hasColumn('rich_addons', 'source')) {
                $table->string('source', 30)->default('local')->after('status');
            }

            if (! Schema::hasColumn('rich_addons', 'staged_path')) {
                $table->string('staged_path')->nullable()->after('installed_path');
            }

            if (! Schema::hasColumn('rich_addons', 'release_checksum')) {
                $table->string('release_checksum', 128)->nullable()->after('staged_path');
            }

            if (! Schema::hasColumn('rich_addons', 'release_signature')) {
                $table->text('release_signature')->nullable()->after('release_checksum');
            }

            if (! Schema::hasColumn('rich_addons', 'license_payload')) {
                $table->json('license_payload')->nullable()->after('license_key');
            }

            if (! Schema::hasColumn('rich_addons', 'last_license_result')) {
                $table->json('last_license_result')->nullable()->after('license_payload');
            }

            if (! Schema::hasColumn('rich_addons', 'failure_reason')) {
                $table->text('failure_reason')->nullable()->after('last_license_result');
            }

            if (! Schema::hasColumn('rich_addons', 'installed_at')) {
                $table->timestamp('installed_at')->nullable()->after('activated_at');
            }

            if (! Schema::hasColumn('rich_addons', 'last_marketplace_sync_at')) {
                $table->timestamp('last_marketplace_sync_at')->nullable()->after('installed_at');
            }
        });

        Schema::create('rich_addon_marketplace_caches', function (Blueprint $table): void {
            $table->id();
            $table->string('system_key')->nullable()->index();
            $table->string('source_url')->nullable();
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->text('failure_reason')->nullable();
            $table->timestamp('fetched_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rich_addon_marketplace_caches');

        Schema::table('rich_addons', function (Blueprint $table): void {
            foreach ([
                'last_marketplace_sync_at',
                'installed_at',
                'failure_reason',
                'last_license_result',
                'license_payload',
                'release_signature',
                'release_checksum',
                'staged_path',
                'source',
            ] as $column) {
                if (Schema::hasColumn('rich_addons', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
