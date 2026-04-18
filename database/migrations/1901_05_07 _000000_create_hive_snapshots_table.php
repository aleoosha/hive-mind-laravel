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

            // Общие показатели здоровья
            $table->float('avg_health')->comment('Среднее здоровье роя за период');
            
            // Нагрузка CPU
            $table->float('avg_cpu');
            $table->float('max_cpu');

            // Задержки БД (самый частый Bottleneck)
            $table->float('avg_db_latency');
            $table->float('max_db_latency');

            // Задержки внешних API
            $table->float('avg_api_latency');
            $table->float('max_api_latency');

            // Масштабируемость
            $table->integer('sample_count')->comment('Количество замеров в этом снимке');
            $table->integer('node_count')->comment('Количество активных нод в рою');
            $table->float('shedding_rate')->default(0)->comment('Итоговый сигнал отсечения ПИД');

            // Конфигурационные пороги на момент записи
            $table->json('thresholds_snapshot')->comment('Пороги CPU, DB, API на момент записи');
            
            // Характеристики железа (Hardware Context)
            $table->integer('cpu_cores')->nullable();
            $table->string('ram_total')->nullable();
            $table->string('server_os')->nullable();
            
            // Индекс для построения временных рядов (Time-series)
            $table->timestamp('created_at')->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hive_snapshots');
    }
};
