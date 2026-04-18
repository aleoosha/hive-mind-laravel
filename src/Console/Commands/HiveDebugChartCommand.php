<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

final class HiveDebugChartCommand extends Command
{
    protected $signature = 'hive:debug-chart {--width=60} {--height=15}';
    protected $description = 'Визуализация фазового портрета и переходных процессов Роя';

    public function handle(): int
    {
        $width = (int) $this->option('width');
        $height = (int) $this->option('height');

        $data = DB::table('hive_snapshots')
            ->orderBy('id', 'desc')
            ->limit($width)
            ->get()
            ->reverse();

        if ($data->count() < 2) {
            $this->error('Недостаточно данных. Запустите нагрузку на пару минут.');
            return 1;
        }

        $this->renderTransitionProcess($data, $width, $height);
        $this->renderHysteresis($data, $height);

        return 0;
    }

    private function renderTransitionProcess($data, $w, $h): void
    {
        $this->info("\n--- Переходный процесс (Нагрузка [█] vs Отсечение [░]) ---");
        
        // Сетка + место под ось Y (4 символа слева)
        $yAxisWidth = 5;
        $grid = array_fill(0, $h, array_fill(0, $w, ' '));
        $data = $data->values();

        foreach ($data as $x => $point) {
            $yHealth = (int)(($point->avg_health ?? 0) / 100 * ($h - 1));
            $yPid = (int)(($point->shedding_rate ?? 0) / 100 * ($h - 1));

            $grid[$h - 1 - $yHealth][$x] = '█';
            
            // Рисуем сигнал ПИД, если место не занято нагрузкой
            if ($grid[$h - 1 - $yPid][$x] === ' ') {
                $grid[$h - 1 - $yPid][$x] = '░';
            }
        }

        // Отрисовка с осью Y
        foreach ($grid as $y => $row) {
            $label = match($y) {
                0 => '100%',
                (int)($h/2) => ' 50%',
                $h-1 => '  0%',
                default => '    '
            };
            $this->line("<fg=gray>{$label} ┨</>" . implode('', $row));
        }

        // Отрисовка оси X
        $xAxis = str_repeat('━', $w);
        $this->line(str_repeat(' ', $yAxisWidth) . "<fg=gray>┗{$xAxis}▶ Time</>");
    }

    private function renderHysteresis($data, $h): void
    {
        $this->info("\n--- Фазовый портрет / Гистерезис (X: Нагрузка -> Y: Сигнал) ---");
        $canvasSize = 40;
        $grid = array_fill(0, $h, array_fill(0, $canvasSize, ' '));

        foreach ($data as $point) {
            $x = (int)(($point->avg_health ?? 0) / 100 * ($canvasSize - 1));
            $y = (int)(($point->shedding_rate ?? 0) / 100 * ($h - 1));
            $grid[max(0, $h - 1 - $y)][max(0, $x)] = '•';
        }

        foreach ($grid as $y => $row) {
            $label = match($y) {
                0 => '100',
                (int)($h/2) => ' 50',
                $h-1 => '  0',
                default => '   '
            };
            $this->line("<fg=gray>{$label} ┨</>" . implode('', $row));
        }
        $this->line(str_repeat(' ', 4) . "┗" . str_repeat('━', $canvasSize) . "▶ Load %");
    }

}
