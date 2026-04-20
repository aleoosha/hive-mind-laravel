<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Aleoosha\HiveMind\Support\FixedPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

final class HiveDebugChartCommand extends Command
{
    protected $signature = 'hive:debug-chart {--width=60} {--height=15}';
    protected $description = 'Visualizing swarm phase portrait and transition processes (FixedPoint support)';

    private const CANVAS_X = 40;

    public function handle(): int
    {
        $width = (int)$this->option('width');
        $height = (int)$this->option('height');

        $data = DB::table('hive_snapshots')
            ->orderBy('id', 'desc')
            ->limit($width)
            ->get()
            ->reverse();

        if ($data->count() < 2) {
            $this->error('Not enough data. Run hive:pulse and apply some load for 1-2 minutes.');
            return self::FAILURE;
        }

        $this->renderTransitionProcess($data, $width, $height);
        $this->renderHysteresis($data, $height);

        return self::SUCCESS;
    }

    private function renderTransitionProcess(Collection $data, int $w, int $h): void
    {
        $this->info("\n--- Transition Process (Load [█] vs Shedding [░]) ---");
        
        $grid = array_fill(0, $h, array_fill(0, $w, ' '));
        $data = $data->values();

        foreach ($data as $x => $point) {
            $health = (new FixedPoint((int)$point->avg_health, true))->toFloat();
            $pid = (new FixedPoint((int)$point->shedding_rate, true))->toFloat();

            $yHealth = (int)($health / 100 * ($h - 1));
            $yPid = (int)($pid / 100 * ($h - 1));

            $grid[$h - 1 - $this->clamp($yHealth, 0, $h - 1)][$x] = '█';
            
            $targetY = $h - 1 - $this->clamp($yPid, 0, $h - 1);
            if ($grid[$targetY][$x] === ' ') {
                $grid[$targetY][$x] = '░';
            }
        }

        $this->drawGrid($grid, $h, $w, 'Time');
    }

    private function renderHysteresis(Collection $data, int $h): void
    {
        $this->info("\n--- Phase Portrait / Hysteresis (X: Load -> Y: Signal) ---");
        
        $grid = array_fill(0, $h, array_fill(0, self::CANVAS_X, ' '));

        foreach ($data as $point) {
            $health = (new FixedPoint((int)$point->avg_health, true))->toFloat();
            $pid = (new FixedPoint((int)$point->shedding_rate, true))->toFloat();

            $x = (int)($health / 100 * (self::CANVAS_X - 1));
            $y = (int)($pid / 100 * ($h - 1));

            $grid[$h - 1 - $this->clamp($y, 0, $h - 1)][$this->clamp($x, 0, self::CANVAS_X - 1)] = '•';
        }

        $this->drawGrid($grid, $h, self::CANVAS_X, 'Load %');
    }

    private function drawGrid(array $grid, int $h, int $w, string $xLabel): void
    {
        $yAxisLabelWidth = 6;

        foreach ($grid as $y => $row) {
            $label = match($y) {
                0 => '100%',
                (int)($h / 2) => ' 50%',
                $h - 1 => '  0%',
                default => '    '
            };
            
            $this->line("<fg=gray>{$label} ┨</>" . implode('', $row));
        }

        $padding = str_repeat(' ', $yAxisLabelWidth); 
        $xAxis = str_repeat('━', $w);
        
        $this->line("{$padding}<fg=gray>┗{$xAxis}▶ {$xLabel}</>");
    }

    private function clamp(int $val, int $min, int $max): int
    {
        return max($min, min($max, $val));
    }
}
