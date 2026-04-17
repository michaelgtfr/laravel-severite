<?php

namespace Severite\Http\Middleware;

use Severite\Models\XhprofReport;
use Closure;
use Illuminate\Support\Str;

class SeveriteReportJobMiddleware
{
    /**
     *
     * @param  \Closure(object): void  $next
     */
    public function handle(object $job, Closure $next): void
    {
        //todo: encore a gerer le tag, l'enregistrement du rapport

        $this->xhprofDependency();
        $this->xhprofActivate();

        //? a voir si c'est bon
        $next($job);

        $xhprofData = $this->xhprofDeactivate();
        $this->generateXhprofReport($xhprofData);
    }

    /**
     * @return void
     *
     */
    private function xhprofDependency(): void
    {
        $XHPROF_ROOT = '/usr/share/php/xhprof_lib/utils';

        require_once $XHPROF_ROOT.'/xhprof_lib.php';
        require_once $XHPROF_ROOT.'/xhprof_runs.php';
    }

    private function xhprofActivate(): void
    {
        xhprof_enable(XHPROF_FLAGS_CPU + XHPROF_FLAGS_MEMORY);
    }

    private function xhprofDeactivate()
    {
        return xhprof_disable();
    }

    private function generateXhprofReport($report)
    {
        //todo: attention pour le moment on enregistre en bdd
        $title = $this->title();
        $mainFunction = $this->manageFistData($report);

        XhprofReport::create([
            'title' => $title,
            'tag' => $this->tag(),
            'report' => json_encode($report),
            'wall_time' => $mainFunction['wt'],
            'memory_usage' => $mainFunction['mu'],
            'peak_memory_usage' => $mainFunction['pmu'],
            'central_processing_unit' => $mainFunction['cpu'],
        ]);
    }

    private function manageFistData($report)
    {
        return array_first($report);
    }

    protected function title(): string
    {
        //todo: voir pour prendre l'unique id du job, sinon le nom de la queue
        return Str::uuid();
    }

    protected function tag(): ?string
    {
        return config('severite.tag', null);
    }
}
