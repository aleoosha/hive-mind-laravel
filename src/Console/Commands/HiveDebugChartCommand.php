<?php

declare(strict_types=1);

namespace Aleoosha\HiveMind\Console\Commands;

use Aleoosha\Support\Types\FixedPoint;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Visualization command for system stability analysis.
 * Renders transition processes and phase portraits directly in the console.
 */
final class HiveDebugChartCommand extends Command
{
    protected $signature = 'hive:debug-chart {--width=60} {--height=15}';

    protected $description = 'Visualizing swarm phase portrait and transition processes (FixedPoint support)';

    private const CANVAS_X = 40;

    public function handle(): int
    {
        $width = (int) $this->option('width');
        $height = (int) $this->option('height');

        // Fetch historical snapshots from the database
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

    /**
     * Render the transition process: System Load vs PID Output over time.
     */
    private function renderTransitionProcess(Collection $data, int $w, int $h): void
    {
        $this->info("\n--- Transition Process (Load [█] vs Shedding [░]) ---");

        $grid = array_fill(0, $h, array_fill(0, $w, ' '));
        $data = $data->values();

        foreach ($data as $x => $point) {
            // Using the new FixedPoint library to interpret stored integers
            $health = (new FixedPoint((int) $point->avg_health))->toFloat();
            $pid = (new FixedPoint((int) $point->shedding_rate))->toFloat(); // updated column name if changed

            $yHealth = (int) ($health * ($h - 1));
            $yPid = (int) ($pid * ($h - 1));

            $grid[$h - 1 - $this->clamp($yHealth, 0, $h - 1)][$x] = '█';

            $targetY = $h - 1 - $this->clamp($yPid, 0, $h - 1);
            if ($grid[$targetY][$x] === ' ') {
                $grid[$targetY][$x] = '░';
            }
        }

        $this->drawGrid($grid, $h, $w, 'Time');
    }

    /**
     * Render the phase portrait (Hysteresis): Load vs Rejection Signal.
     */
    private function renderHysteresis(Collection $data, int $h): void
    {
        $this->info("\n--- Phase Portrait / Hysteresis (X: Load -> Y: Signal) ---");

        $grid = array_fill(0, $h, array_fill(0, self::CANVAS_X, ' '));

        foreach ($data as $point) {
            $health = (new FixedPoint((int) $point->avg_health))->toFloat();
            $pid = (new FixedPoint((int) $point->shedding_rate))->toFloat();

            $x = (int) ($health * (self::CANVAS_X - 1));
            $y = (int) ($pid * ($h - 1));

            $grid[$h - 1 - $this->clamp($y, 0, $h - 1)][$this->clamp($x, 0, self::CANVAS_X - 1)] = '•';
        }

        $this->drawGrid($grid, $h, self::CANVAS_X, 'Load %');
    }

    /**
     * Draw the ASCII grid to the console.
     */
    private function drawGrid(array $grid, int $h, int $w, string $xLabel): void
    {
        foreach ($grid as $y => $row) {
            $label = match ($y) {
                0 => '1.0',
                (int) ($h / 2) => '0.5',
                $h - 1 => '0.0',
                default => '   '
            };
            $this->line("<fg=gray>{$label} ┨</>".implode('', $row));
        }

        $xAxis = str_repeat('━', $w);
        $this->line("    <fg=gray>┗{$xAxis}▶ {$xLabel}</>");
    }

    private function clamp(int $val, int $min, int $max): int
    {
        return max($min, min($max, $val));
    }
}
