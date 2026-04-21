<?php

namespace Severite\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use Severite\Http\Resources\XhprofReportResource;
use Severite\Services\ReportService;
use Severite\Services\XhprofReportService;

/**
 * @OA\Info(title="Severite API", version="1.0")
 */
class ReportController
{
    public function __construct(
        private readonly XhprofReportService $xhprofReportService
    ) {}

    /**
     * @OA\Get(
     *     path="/severite",
     *     summary="List all XHProf profiling reports",
     *     tags={"Reports"},
     *     @OA\Response(
     *         response=200,
     *         description="List of profiling reports"
     *     )
     * )
     */
    public function index(): InertiaResponse
    {
        $reportList = ReportService::getListOfReport();

        return Inertia::render('HomeView', [
            'reportList' => XhprofReportResource::collection($reportList)->toArray(request()),
            'baseUrl' => url('severite'),
        ]);
    }

    /**
     * @OA\Get(
     *     path="/severite/{reportId}",
     *     summary="Get normalized XHProf data for a report",
     *     tags={"Reports"},
     *     @OA\Parameter(
     *         name="reportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=200, description="Normalized profiling report data"),
     *     @OA\Response(response=404, description="Report not found")
     * )
     */
    public function show(string $reportId): JsonResponse
    {
        $xhprofReportNormalized = $this->xhprofReportService->normalizeXhprofData($reportId);

        return response()->json($xhprofReportNormalized);
    }

    /**
     * @OA\Delete(
     *     path="/severite/{reportId}",
     *     summary="Delete a profiling report",
     *     tags={"Reports"},
     *     @OA\Parameter(
     *         name="reportId",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(response=204, description="Report deleted successfully"),
     *     @OA\Response(response=404, description="Report not found")
     * )
     */
    public function destroy(string $reportId): Response
    {
        $deleted = $this->xhprofReportService->deleteReport($reportId);

        if (!$deleted) {
            abort(404, 'Report not found');
        }

        return response()->noContent();
    }
}
