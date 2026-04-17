<?php

namespace Database\Seeders;

use App\Models\XhprofReport;
use Illuminate\Database\Seeder;

class XhprofReportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Le champ `report` est casté en `array` dans le modèle, donc on passe
     * un tableau PHP. Laravel se charge du json_encode/json_decode.
     *
     * Format xhprof : chaque entrée est soit "main()" (racine) soit
     * "parent==>enfant" (appel de parent vers enfant).
     * Métriques : ct (nb appels), wt (wall time µs), cpu (CPU µs),
     *             mu (mémoire octets), pmu (pic mémoire octets).
     */
    public function run(): void
    {
        XhprofReport::create([
            'title' => 'Laravel – traitement rapport XHProf',
            'tag'   => 'service seeder',
            'wall_time' => 300000,
            'central_processing_unit' => 280000,
            'memory_usage' => 5000000,
            'peak_memory_usage' => 4500000,
            'report' => [
                // ── Racine ──────────────────────────────────────────────────────────
                'main()' => [
                    'ct' => 1, 'wt' => 300000, 'cpu' => 280000,
                    'mu' => 5000000, 'pmu' => 4500000,
                ],

                // ── Niveau 1 : bootstrap HTTP ────────────────────────────────────
                'main()==>App\Http\Kernel::handle' => [
                    'ct' => 1, 'wt' => 290000, 'cpu' => 270000,
                    'mu' => 4800000, 'pmu' => 4400000,
                ],

                // ── Niveau 2 : pipeline Laravel ──────────────────────────────────
                'App\Http\Kernel::handle==>Illuminate\Pipeline\Pipeline::Illuminate\Pipeline\{closure}' => [
                    'ct' => 1, 'wt' => 285000, 'cpu' => 265000,
                    'mu' => 4600000, 'pmu' => 4200000,
                ],

                // ── Niveau 3 : middlewares + routeur ────────────────────────────
                'Illuminate\Pipeline\Pipeline::Illuminate\Pipeline\{closure}==>App\Http\Middleware\Authenticate' => [
                    'ct' => 1, 'wt' => 4000, 'cpu' => 3500,
                    'mu' => 50000, 'pmu' => 0,
                ],
                'Illuminate\Pipeline\Pipeline::Illuminate\Pipeline\{closure}==>Illuminate\Session\Middleware\StartSession' => [
                    'ct' => 1, 'wt' => 5000, 'cpu' => 4500,
                    'mu' => 60000, 'pmu' => 0,
                ],
                'Illuminate\Pipeline\Pipeline::Illuminate\Pipeline\{closure}==>Illuminate\Routing\Router::Illuminate\Routing\{closure}' => [
                    'ct' => 1, 'wt' => 274000, 'cpu' => 255000,
                    'mu' => 4450000, 'pmu' => 4100000,
                ],

                // ── Niveau 4 : auth ──────────────────────────────────────────────
                'App\Http\Middleware\Authenticate==>Illuminate\Auth\SessionGuard::check' => [
                    'ct' => 1, 'wt' => 3500, 'cpu' => 3000,
                    'mu' => 45000, 'pmu' => 0,
                ],

                // ── Niveau 4 : dispatch de la route ─────────────────────────────
                'Illuminate\Routing\Router::Illuminate\Routing\{closure}==>Illuminate\Routing\Route::runWithoutMiddleware' => [
                    'ct' => 1, 'wt' => 270000, 'cpu' => 252000,
                    'mu' => 4400000, 'pmu' => 4050000,
                ],

                // ── Niveau 5 : controller dispatcher ────────────────────────────
                'Illuminate\Routing\Route::runWithoutMiddleware==>Illuminate\Routing\ControllerDispatcher::dispatch' => [
                    'ct' => 1, 'wt' => 265000, 'cpu' => 248000,
                    'mu' => 4350000, 'pmu' => 4000000,
                ],

                // ── Niveau 6 : controller ────────────────────────────────────────
                'Illuminate\Routing\ControllerDispatcher::dispatch==>App\Http\Controllers\ReportController::show' => [
                    'ct' => 1, 'wt' => 260000, 'cpu' => 244000,
                    'mu' => 4300000, 'pmu' => 3950000,
                ],

                // ── Niveau 7 : lecture modèle + normalisation + réponse ──────────
                'App\Http\Controllers\ReportController::show==>App\Models\XhprofReport' => [
                    'ct' => 1, 'wt' => 15000, 'cpu' => 10000,
                    'mu' => 200000, 'pmu' => 0,
                ],
                'App\Http\Controllers\ReportController::show==>App\Services\XhprofReportService::normalizeXhprofData' => [
                    'ct' => 1, 'wt' => 230000, 'cpu' => 225000,
                    'mu' => 3800000, 'pmu' => 3600000,
                ],
                'App\Http\Controllers\ReportController::show==>Illuminate\Http\Response' => [
                    'ct' => 1, 'wt' => 8000, 'cpu' => 7000,
                    'mu' => 100000, 'pmu' => 0,
                ],

                // ── Niveau 8 : accès DB ──────────────────────────────────────────
                'App\Models\XhprofReport==>Illuminate\Database\Eloquent\Builder::find' => [
                    'ct' => 1, 'wt' => 12000, 'cpu' => 8000,
                    'mu' => 180000, 'pmu' => 0,
                ],

                // ── Niveau 8 : étapes de normalisation ──────────────────────────
                'App\Services\XhprofReportService::normalizeXhprofData==>App\Services\XhprofReportService::normalizeFunction' => [
                    'ct' => 1, 'wt' => 60000, 'cpu' => 58000,
                    'mu' => 900000, 'pmu' => 800000,
                ],
                'App\Services\XhprofReportService::normalizeXhprofData==>App\Services\XhprofReportService::createGlobalMetricByFunction' => [
                    'ct' => 1, 'wt' => 55000, 'cpu' => 53000,
                    'mu' => 800000, 'pmu' => 700000,
                ],
                'App\Services\XhprofReportService::normalizeXhprofData==>App\Services\XhprofReportService::setChildrenFunctionInFunction' => [
                    'ct' => 1, 'wt' => 50000, 'cpu' => 48000,
                    'mu' => 700000, 'pmu' => 600000,
                ],
                'App\Services\XhprofReportService::normalizeXhprofData==>App\Services\XhprofReportService::setExcludeMetricsAndPercentageMetrics' => [
                    'ct' => 1, 'wt' => 45000, 'cpu' => 43000,
                    'mu' => 600000, 'pmu' => 500000,
                ],
                'App\Services\XhprofReportService::normalizeXhprofData==>App\Services\XhprofReportService::sortParentChildren' => [
                    'ct' => 1, 'wt' => 10000, 'cpu' => 9000,
                    'mu' => 150000, 'pmu' => 0,
                ],

                // ── Niveau 8 : réponse JSON ──────────────────────────────────────
                'Illuminate\Http\Response==>json_encode' => [
                    'ct' => 1, 'wt' => 6000, 'cpu' => 5500,
                    'mu' => 80000, 'pmu' => 0,
                ],

                // ── Niveau 9 : DB query ──────────────────────────────────────────
                'Illuminate\Database\Eloquent\Builder::find==>Illuminate\Database\Query\Builder::first' => [
                    'ct' => 1, 'wt' => 10000, 'cpu' => 6000,
                    'mu' => 160000, 'pmu' => 0,
                ],

                // ── Niveau 9 : sous-étapes normalisation ────────────────────────
                'App\Services\XhprofReportService::normalizeFunction==>App\Services\XhprofReportService::getCurrentAndParentFunctionOnKey' => [
                    'ct' => 500, 'wt' => 25000, 'cpu' => 22000,
                    'mu' => 520000, 'pmu' => 0,
                ],
                'App\Services\XhprofReportService::normalizeFunction==>App\Services\XhprofReportService::setFunctionAndMetricsInReportNormalized' => [
                    'ct' => 500, 'wt' => 30000, 'cpu' => 28000,
                    'mu' => 350000, 'pmu' => 0,
                ],
                'App\Services\XhprofReportService::sortParentChildren==>App\Services\XhprofReportService::sortParentChildrenRecursive' => [
                    'ct' => 100, 'wt' => 8000, 'cpu' => 7500,
                    'mu' => 120000, 'pmu' => 0,
                ],

                // ── Niveau 10 : SQL ──────────────────────────────────────────────
                'Illuminate\Database\Query\Builder::first==>PDO::query' => [
                    'ct' => 1, 'wt' => 8000, 'cpu' => 4000,
                    'mu' => 50000, 'pmu' => 0,
                ],

                // ── Niveau 10 : parsing des clés xhprof ─────────────────────────
                'App\Services\XhprofReportService::getCurrentAndParentFunctionOnKey==>explode' => [
                    'ct' => 500, 'wt' => 5000, 'cpu' => 4000,
                    'mu' => 52000, 'pmu' => 0,
                ],

                // ── Niveau 11 : exécution de la requête ─────────────────────────
                'PDO::query==>PDOStatement::execute' => [
                    'ct' => 1, 'wt' => 6000, 'cpu' => 2000,
                    'mu' => 40000, 'pmu' => 0,
                ],
            ],
        ]);
    }
}
