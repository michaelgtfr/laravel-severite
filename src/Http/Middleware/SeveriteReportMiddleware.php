<?php

namespace Severite\Http\Middleware;

use Closure;
use Severite\Models\XhprofReport;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use ReflectionExtension;
use Symfony\Component\HttpFoundation\Response;

class SeveriteReportMiddleware
{
    private Request $request;

    /**
    * Handle an incoming request.
    *
    * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
    */
    public function handle(Request $request, Closure $next): Response
    {
        $this->request = $request;

        if ($this->isActive()) {
            $this->xhprofDependency();
            $this->xhprofActivate();
        }

        $response = $next($request);

        if ($this->isActive()) {
            $xhprofData = $this->xhprofDeactivate();
            $this->generateXhprofReport($xhprofData);
        }

        return $response;
    }

    /**
     * @return void
     *
     */
    private function xhprofDependency(): void
    {
        $XHPROF_ROOT = config('severite.xhprof-lib-url');

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

    private function isActive(): bool
    {
        return config('severite.activate', false) === true || $this->request->hasHeader(config('header-severite-activation', 'X-trace-severite'));
    }

    private function generateXhprofReport($report)
    {
        //todo: attention pour le moment on enregistre en bdd
        $title = $this->title();
        $mainFunction = $this->manageFistData($report);

        XhprofReport::create([
            'title' => $title,
            'tag' => $this->tag(),
            'report' => $report,
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
        return $this->request->path() ?? Str::uuid();
    }

    protected function tag(): ?string
    {
        return config('severite.tag', null);
    }
}
