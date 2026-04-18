<?

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
            $this->error('Недостаточно данных для анализа колебаний.');
            return 1;
        }

        $this->renderTransitionProcess($data, $width, $height);
        $this->renderHysteresis($data, $height);

        return 0;
    }

    /**
     * График переходного процесса (Время -> Значение)
     * Позволяет увидеть автоколебания (раскачку системы)
     */
    private function renderTransitionProcess($data, $w, $h): void
    {
        $this->info("\n--- Переходный процесс (Нагрузка [█] vs Отсечение [░]) ---");
        
        $grid = array_fill(0, $h, array_fill(0, $w, ' '));
        $healths = $data->pluck('avg_health')->toArray();
        $pids = $data->pluck('d_term')->toArray(); // Используем d_term как сигнал ПИД

        foreach ($healths as $x => $y) {
            $yPos = (int)($y / 100 * ($h - 1));
            $grid[$h - 1 - $yPos][$x] = '█';
        }

        foreach ($pids as $x => $y) {
            $yPos = (int)($y / 100 * ($h - 1));
            if ($grid[$h - 1 - $yPos][$x] === ' ') {
                $grid[$h - 1 - $yPos][$x] = '░';
            }
        }

        foreach ($grid as $row) {
            $this->line(implode('', $row));
        }
    }

    /**
     * Петля гистерезиса (Нагрузка -> Сигнал)
     * Показывает, насколько система "запаздывает" с ответом
     */
    private function renderHysteresis($data, $h): void
    {
        $this->info("\n--- Фазовый портрет / Гистерезис (X: Нагрузка -> Y: Сигнал) ---");
        $canvasSize = 40;
        $grid = array_fill(0, $h, array_fill(0, $canvasSize, ' '));

        foreach ($data as $point) {
            $x = (int)($point->avg_health / 100 * ($canvasSize - 1));
            $y = (int)($point->d_term / 100 * ($h - 1));
            $grid[$h - 1 - $y][$x] = '•';
        }

        foreach ($grid as $row) {
            $this->line(implode('', $row));
        }
    }
}
