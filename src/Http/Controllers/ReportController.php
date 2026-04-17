<?php

namespace Severite\Http\Controllers;

use Severite\Services\XhprofReportService;
use Severite\Services\ReportService;
use Inertia\Inertia;

class ReportController
{
    public function index()
    {
        $reportList = ReportService::getListOfReport();

        return Inertia::render('HomeView', [
            'reportList' => $reportList->toArray(),
            'baseUrl' => url('severite'),
        ]);
    }

    public function show(string $reportId)
    {
        $xhprofReportService = new XhprofReportService();
        $xhprofReportNormalized = $xhprofReportService->normalizeXhprofData($reportId);

        return response($xhprofReportNormalized);
    }

    public function destroy(string $reportId)
    {
        $xhprofReportService = new XhprofReportService();
        $xhprofReportNormalized = $xhprofReportService->deleteReport($reportId);

        return response($xhprofReportNormalized);
    }
}
