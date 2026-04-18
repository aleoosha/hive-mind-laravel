<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hive_snapshots', function (Blueprint $table) {
            $table->id();

            // Swarm Health & PID
            $table->float('avg_health');
            $table->float('shedding_rate')->default(0);

            // Resource Metrics
            $table->float('avg_cpu');
            $table->float('max_cpu');
            $table->float('avg_db_latency');
            $table->float('max_db_latency');
            $table->float('avg_api_latency');
            $table->float('max_api_latency');

            // Capacity & Scale
            $table->integer('sample_count');
            $table->integer('node_count');

            // System Context
            $table->json('thresholds_snapshot');
            $table->integer('cpu_cores')->nullable();
            $table->float('ram_total_gb')->nullable();
            $table->string('server_os')->nullable();
            $table->string('php_version')->nullable();

            // Time-series indexing
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_snapshots');
    }
};
