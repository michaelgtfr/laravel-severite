<?php

use Severite\Database\Seeders\XhprofReportSeeder;
use Severite\Models\XhprofReport;

beforeEach(function () {
    $this->seed(XhprofReportSeeder::class);
});

describe('ReportController', function () {
    describe('index', function () {
        it('returns 200 with the HomeView Inertia component', function () {
            $this->withHeaders(['X-Inertia' => 'true'])
                ->get(route('home'))
                ->assertStatus(200)
                ->assertJson(['component' => 'HomeView']);
        });

        it('returns a non-empty reportList in props', function () {
            $response = $this->withHeaders(['X-Inertia' => 'true'])
                ->get(route('home'));

            $response->assertJsonPath('props.reportList', fn ($list) => count($list) > 0);
        });

        it('includes the baseUrl in props', function () {
            $response = $this->withHeaders(['X-Inertia' => 'true'])
                ->get(route('home'));

            $response->assertJsonPath('props.baseUrl', fn ($url) => str_contains($url, 'severite'));
        });

        it('returns only the allowed fields in each report entry', function () {
            $response = $this->withHeaders(['X-Inertia' => 'true'])
                ->get(route('home'));

            $firstReport = $response->json('props.reportList.0');

            expect($firstReport)->toHaveKeys([
                'id',
                'title',
                'tag',
                'wall_time',
                'memory_usage',
                'peak_memory_usage',
                'central_processing_unit',
            ]);
        });

        it('does not expose the raw report payload in the list', function () {
            $response = $this->withHeaders(['X-Inertia' => 'true'])
                ->get(route('home'));

            $firstReport = $response->json('props.reportList.0');

            expect($firstReport)->not->toHaveKey('report');
        });
    });

    describe('show', function () {
        it('returns 200 with normalized xhprof data', function () {
            $report = XhprofReport::first();

            $this->get(route('detailReport', $report->id))
                ->assertStatus(200);
        });

        it('returns a normalized dataset with main() as root function', function () {
            $report = XhprofReport::first();

            $data = $this->get(route('detailReport', $report->id))->json();

            expect($data)->toHaveKey('main()');
        });

        it('returns functions with the expected normalized structure', function () {
            $report = XhprofReport::first();

            $data = $this->get(route('detailReport', $report->id))->json();

            expect($data['main()'])->toHaveKeys([
                'metrics',
                'globalMetrics',
                'parentFunction',
                'childFunction',
            ]);
        });

        it('returns an empty parentFunction array for the main() function', function () {
            $report = XhprofReport::first();

            $data = $this->get(route('detailReport', $report->id))->json();

            expect($data['main()']['parentFunction'])->toBeEmpty();
        });

        it('returns globalMetrics with total-percentage keys for each principal metric', function () {
            $report = XhprofReport::first();

            $globalMetrics = $this->get(route('detailReport', $report->id))
                ->json('main().globalMetrics');

            expect($globalMetrics)->toHaveKeys([
                'wt-total-percentage',
                'mu-total-percentage',
                'cpu-total-percentage',
                'pmu-total-percentage',
            ]);
        });

        it('returns globalMetrics with excl-percentage keys for each principal metric', function () {
            $report = XhprofReport::first();

            $globalMetrics = $this->get(route('detailReport', $report->id))
                ->json('main().globalMetrics');

            expect($globalMetrics)->toHaveKeys([
                'wt-excl-percentage',
                'mu-excl-percentage',
                'cpu-excl-percentage',
                'pmu-excl-percentage',
            ]);
        });

        it('returns main() with a 100% total wall-time percentage', function () {
            $report = XhprofReport::first();

            $wtPercentage = $this->get(route('detailReport', $report->id))
                ->json('main().globalMetrics.wt-total-percentage');

            expect($wtPercentage)->toEqual(100);
        });

        it('returns main() with child functions', function () {
            $report = XhprofReport::first();

            $data = $this->get(route('detailReport', $report->id))->json();

            expect($data['main()']['childFunction'])->not->toBeEmpty();
        });

        it('returns normalized data sorted with main() as first entry', function () {
            $report = XhprofReport::first();

            $data = $this->get(route('detailReport', $report->id))->json();

            expect(array_key_first($data))->toBe('main()');
        });
    });

    describe('destroy', function () {
        it('returns 204 when deleting an existing report', function () {
            $report = XhprofReport::first();

            $this->delete(route('deleteReport', $report->id))
                ->assertNoContent();
        });

        it('returns 404 when deleting a non-existent report', function () {
            $this->delete(route('deleteReport', 'non-existent-id'))
                ->assertNotFound();
        });

        it('removes the report from the database', function () {
            $report = XhprofReport::first();
            $id = $report->id;

            $this->delete(route('deleteReport', $id));

            expect(XhprofReport::find($id))->toBeNull();
        });

        it('decrements the total report count by one', function () {
            $countBefore = XhprofReport::count();
            $report = XhprofReport::first();

            $this->delete(route('deleteReport', $report->id));

            expect(XhprofReport::count())->toBe($countBefore - 1);
        });
    });
});
