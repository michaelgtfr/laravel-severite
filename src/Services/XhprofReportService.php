<?php

namespace Severite\Services;

use Severite\Models\XhprofReport;

class XhprofReportService
{
    public function __construct(
        private readonly XhprofNormalizerService $normalizer
    ) {}

    public function deleteReport(string $reportId): int
    {
        return XhprofReport::destroy($reportId);
    }

    /**
     * Fetches the stored report and delegates normalization to XhprofNormalizerService.
     */
    public function normalizeXhprofData(string $xhprofReportId): array
    {
        $report = XhprofReport::findOrFail($xhprofReportId);

        return $this->normalizer->normalize($report->report);
    }
}
