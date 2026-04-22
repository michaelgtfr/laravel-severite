<?php

use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Severite\Models\XhprofReport;
use Severite\Services\XhprofNormalizerService;
use Severite\Services\XhprofReportService;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// ── Dataset helper ─────────────────────────────────────────────────────────────

function makeXhprofReport(array $overrides = []): XhprofReport
{
    return XhprofReport::create(array_merge([
        'title'                   => 'Test report',
        'report'                  => ['main()' => ['ct' => 1, 'wt' => 100, 'cpu' => 90, 'mu' => 1000, 'pmu' => 900]],
        'tag'                     => 'test',
        'wall_time'               => 100,
        'memory_usage'            => 1000,
        'peak_memory_usage'       => 900,
        'central_processing_unit' => 90,
    ], $overrides));
}

// ─────────────────────────────────────────────────────────────────────────────

describe('XhprofReportService', function () {

    // ── deleteReport ──────────────────────────────────────────────────────────
    describe('deleteReport', function () {

        it('returns 1 when the report exists', function () {
            $report  = makeXhprofReport();
            $service = new XhprofReportService(new XhprofNormalizerService());

            expect($service->deleteReport($report->id))->toBe(1);
        });

        it('removes the record from the database', function () {
            $report  = makeXhprofReport();
            $service = new XhprofReportService(new XhprofNormalizerService());
            $service->deleteReport($report->id);

            expect(XhprofReport::find($report->id))->toBeNull();
        });

        it('returns 0 when the report does not exist', function () {
            $service = new XhprofReportService(new XhprofNormalizerService());

            expect($service->deleteReport('00000000-0000-0000-0000-000000000000'))->toBe(0);
        });

        it('does not affect other reports in the database', function () {
            $keep   = makeXhprofReport(['title' => 'keep']);
            $delete = makeXhprofReport(['title' => 'delete']);
            $service = new XhprofReportService(new XhprofNormalizerService());
            $service->deleteReport($delete->id);

            expect(XhprofReport::find($keep->id))->not->toBeNull();
        });
    });

    // ── normalizeXhprofData ───────────────────────────────────────────────────
    describe('normalizeXhprofData', function () {

        it('delegates to the normalizer with the stored report array', function () {
            $rawReport = ['main()' => ['ct' => 1, 'wt' => 100, 'cpu' => 90, 'mu' => 1000, 'pmu' => 900]];
            $report    = makeXhprofReport(['report' => $rawReport]);

            $normalizer = mock(XhprofNormalizerService::class);
            $normalizer->shouldReceive('normalize')
                ->once()
                ->with($rawReport)
                ->andReturn(['main()' => ['mocked' => true]]);

            $service = new XhprofReportService($normalizer);

            expect($service->normalizeXhprofData($report->id))
                ->toBe(['main()' => ['mocked' => true]]);
        });

        it('returns a normalized structure for a real report', function () {
            $report  = makeXhprofReport();
            $service = new XhprofReportService(new XhprofNormalizerService());
            $result  = $service->normalizeXhprofData($report->id);

            expect($result)->toHaveKey('main()')
                ->and($result['main()'])->toHaveKeys(['globalMetrics', 'childFunction', 'parentFunction']);
        });

        it('places main() first in the returned array', function () {
            $report  = makeXhprofReport();
            $service = new XhprofReportService(new XhprofNormalizerService());

            expect(array_key_first($service->normalizeXhprofData($report->id)))->toBe('main()');
        });

        it('throws ModelNotFoundException when the report id does not exist', function () {
            $service = new XhprofReportService(new XhprofNormalizerService());

            expect(fn () => $service->normalizeXhprofData('00000000-0000-0000-0000-000000000000'))
                ->toThrow(ModelNotFoundException::class);
        });
    });
});
