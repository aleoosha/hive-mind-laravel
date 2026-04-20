<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    
    /**
     * Run the migrations.
     * 
     * All floating point metrics are stored as integers (value * 1000)
     * to prevent precision loss and optimize database indexing.
     */
    public function up(): void
    {
        Schema::create('hive_snapshots', function (Blueprint $table) {
            $table->id();

            // Swarm Health & PID (Integer milli-points: 100% = 100000)
            $table->bigInteger('avg_health');
            $table->bigInteger('shedding_rate')->default(0);

            // Resource Metrics (Integer milli-points: e.g., 50.5% = 50500)
            $table->bigInteger('avg_cpu');
            $table->bigInteger('max_cpu');
            $table->bigInteger('avg_db_latency');
            $table->bigInteger('max_db_latency');
            $table->bigInteger('avg_api_latency');
            $table->bigInteger('max_api_latency');

            // Capacity & Scale
            $table->integer('sample_count');
            $table->integer('node_count');

            // System Context
            $table->json('thresholds_snapshot');
            $table->integer('cpu_cores')->nullable();
            $table->bigInteger('ram_total_gb')->nullable()->comment('Stored as MB * 1000');
            $table->string('server_os')->nullable();
            $table->string('php_version')->nullable();

            // Time-series indexing
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hive_snapshots');
    }
};
