<?php

use Severite\Services\XhprofNormalizerService;

// ── Dataset & helpers ─────────────────────────────────────────────────────────
// Prefixed to avoid collisions with helpers in XhprofReportServiceTest.php.

function normReport(): array
{
    return [
        'main()'         => ['ct' => 1, 'wt' => 100, 'cpu' => 90, 'mu' => 1000, 'pmu' => 900],
        'main()==>funcA' => ['ct' => 1, 'wt' => 60,  'cpu' => 55, 'mu' => 600,  'pmu' => 500],
        'main()==>funcB' => ['ct' => 2, 'wt' => 30,  'cpu' => 25, 'mu' => 300,  'pmu' => 200],
        'funcA==>funcC'  => ['ct' => 1, 'wt' => 40,  'cpu' => 35, 'mu' => 400,  'pmu' => 300],
    ];
}

function normPrivate(object $obj, string $prop): mixed
{
    return (new ReflectionProperty($obj, $prop))->getValue($obj);
}

function normServiceAfterStep3(): XhprofNormalizerService
{
    $svc = new XhprofNormalizerService();
    $svc->normalizeFunction(normReport());
    $svc->createGlobalMetricByFunction();
    $svc->setChildrenFunctionInFunction();

    return $svc;
}

function normGetExcl(): array
{
    $svc = normServiceAfterStep3();
    $svc->setExcludeMetricsAndPercentageMetrics();

    return normPrivate($svc, 'reportNormalized');
}

// ─────────────────────────────────────────────────────────────────────────────

describe('XhprofNormalizerService', function () {

    // ── getCurrentAndParentFunctionOnKey ──────────────────────────────────────
    describe('getCurrentAndParentFunctionOnKey', function () {

        it('returns current=main() and parent=null for the root entry', function () {
            $svc = new XhprofNormalizerService();
            [$current, $parent] = $svc->getCurrentAndParentFunctionOnKey('main()');

            expect($current)->toBe('main()')
                ->and($parent)->toBeNull();
        });

        it('splits a simple parent==>child edge correctly', function () {
            $svc = new XhprofNormalizerService();
            [$current, $parent] = $svc->getCurrentAndParentFunctionOnKey('main()==>funcA');

            expect($current)->toBe('funcA')
                ->and($parent)->toBe('main()');
        });

        it('handles fully-qualified class::method names on both sides', function () {
            $svc = new XhprofNormalizerService();
            [$current, $parent] = $svc->getCurrentAndParentFunctionOnKey(
                'App\\Http\\Kernel::handle==>Illuminate\\Pipeline\\Pipeline::run'
            );

            expect($current)->toBe('Illuminate\\Pipeline\\Pipeline::run')
                ->and($parent)->toBe('App\\Http\\Kernel::handle');
        });

        it('returns a two-element array in both cases', function () {
            $svc = new XhprofNormalizerService();

            expect($svc->getCurrentAndParentFunctionOnKey('main()'))->toHaveCount(2);
            expect($svc->getCurrentAndParentFunctionOnKey('a==>b'))->toHaveCount(2);
        });
    });

    // ── setFunctionAndMetricsInReportNormalized ───────────────────────────────
    describe('setFunctionAndMetricsInReportNormalized', function () {

        it('creates the function entry with the parent name as metrics key', function () {
            $svc = new XhprofNormalizerService();
            $svc->setFunctionAndMetricsInReportNormalized('funcA', 'main()', ['wt' => 50]);

            $normalized = normPrivate($svc, 'reportNormalized');

            expect($normalized)->toHaveKey('funcA')
                ->and($normalized['funcA']['metrics'])->toHaveKey('main()')
                ->and($normalized['funcA']['metrics']['main()']['wt'])->toBe(50);
        });

        it('uses the function name itself as the metrics key when parent is null (root)', function () {
            $svc = new XhprofNormalizerService();
            $svc->setFunctionAndMetricsInReportNormalized('main()', null, ['wt' => 100]);

            $normalized = normPrivate($svc, 'reportNormalized');

            expect($normalized['main()']['metrics'])->toHaveKey('main()');
        });

        it('appends a second parent entry without overwriting the first', function () {
            $svc = new XhprofNormalizerService();
            $svc->setFunctionAndMetricsInReportNormalized('funcShared', 'funcA', ['wt' => 10]);
            $svc->setFunctionAndMetricsInReportNormalized('funcShared', 'funcB', ['wt' => 20]);

            $metrics = normPrivate($svc, 'reportNormalized')['funcShared']['metrics'];

            expect($metrics)->toHaveKey('funcA')
                ->and($metrics)->toHaveKey('funcB')
                ->and($metrics['funcA']['wt'])->toBe(10)
                ->and($metrics['funcB']['wt'])->toBe(20);
        });
    });

    // ── normalizeFunction ─────────────────────────────────────────────────────
    describe('normalizeFunction', function () {

        it('creates one entry per unique function name', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(normReport());

            expect(normPrivate($svc, 'reportNormalized'))
                ->toHaveKeys(['main()', 'funcA', 'funcB', 'funcC']);
        });

        it('stores child metrics under the parent key', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(normReport());

            expect(normPrivate($svc, 'reportNormalized')['funcA']['metrics'])
                ->toHaveKey('main()');
        });

        it('stores root metrics under its own name as key', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(normReport());

            expect(normPrivate($svc, 'reportNormalized')['main()']['metrics'])
                ->toHaveKey('main()');
        });

        it('preserves the original metric values', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(normReport());
            $n = normPrivate($svc, 'reportNormalized');

            expect($n['funcA']['metrics']['main()']['wt'])->toBe(60)
                ->and($n['funcB']['metrics']['main()']['mu'])->toBe(300)
                ->and($n['funcC']['metrics']['funcA']['cpu'])->toBe(35);
        });
    });

    // ── createGlobalMetricByFunction ──────────────────────────────────────────
    describe('createGlobalMetricByFunction', function () {

        it('aggregates single-parent metrics into globalMetrics', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(normReport());
            $svc->createGlobalMetricByFunction();
            $n = normPrivate($svc, 'reportNormalized');

            expect($n['funcA']['globalMetrics']['wt'])->toBe(60)
                ->and($n['funcA']['globalMetrics']['mu'])->toBe(600);
        });

        it('sums globalMetrics when a function is called from multiple parents', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(array_merge(normReport(), [
                'funcA==>funcShared' => ['ct' => 1, 'wt' => 10, 'cpu' => 8,  'mu' => 100, 'pmu' => 0],
                'funcB==>funcShared' => ['ct' => 1, 'wt' => 15, 'cpu' => 12, 'mu' => 150, 'pmu' => 0],
            ]));
            $svc->createGlobalMetricByFunction();
            $n = normPrivate($svc, 'reportNormalized');

            expect($n['funcShared']['globalMetrics']['wt'])->toBe(25)
                ->and($n['funcShared']['globalMetrics']['mu'])->toBe(250);
        });

        it('sets an empty parentFunction for main()', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(normReport());
            $svc->createGlobalMetricByFunction();

            expect(normPrivate($svc, 'reportNormalized')['main()']['parentFunction'])->toBeEmpty();
        });

        it('correctly identifies direct parents of child functions', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(normReport());
            $svc->createGlobalMetricByFunction();
            $n = normPrivate($svc, 'reportNormalized');

            expect($n['funcA']['parentFunction'])->toContain('main()')
                ->and($n['funcC']['parentFunction'])->toContain('funcA');
        });

        it('lists all parents when a function has multiple callers', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(array_merge(normReport(), [
                'funcA==>funcShared' => ['ct' => 1, 'wt' => 10, 'cpu' => 8, 'mu' => 100, 'pmu' => 0],
                'funcB==>funcShared' => ['ct' => 1, 'wt' => 15, 'cpu' => 12, 'mu' => 150, 'pmu' => 0],
            ]));
            $svc->createGlobalMetricByFunction();
            $n = normPrivate($svc, 'reportNormalized');

            expect($n['funcShared']['parentFunction'])
                ->toContain('funcA')
                ->and($n['funcShared']['parentFunction'])->toContain('funcB');
        });
    });

    // ── setChildrenFunctionInFunction ─────────────────────────────────────────
    describe('setChildrenFunctionInFunction', function () {

        it('populates direct children of main()', function () {
            $n = normPrivate(normServiceAfterStep3(), 'reportNormalized');

            expect($n['main()']['childFunction'])
                ->toContain('funcA')
                ->and($n['main()']['childFunction'])->toContain('funcB');
        });

        it('populates the child of a non-root function', function () {
            expect(normPrivate(normServiceAfterStep3(), 'reportNormalized')['funcA']['childFunction'])
                ->toContain('funcC');
        });

        it('sets an empty childFunction for leaf functions', function () {
            $n = normPrivate(normServiceAfterStep3(), 'reportNormalized');

            expect($n['funcB']['childFunction'])->toBeEmpty()
                ->and($n['funcC']['childFunction'])->toBeEmpty();
        });

        it('gives main() no childFunction entry when the report has only main()', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(['main()' => ['ct' => 1, 'wt' => 10, 'cpu' => 8, 'mu' => 100, 'pmu' => 0]]);
            $svc->createGlobalMetricByFunction();
            $svc->setChildrenFunctionInFunction();

            expect(normPrivate($svc, 'reportNormalized')['main()']['childFunction'])->toBeEmpty();
        });
    });

    // ── setExcludeMetricsAndPercentageMetrics ─────────────────────────────────
    describe('setExcludeMetricsAndPercentageMetrics', function () {

        it('gives main() a 100% wt-total-percentage', function () {
            expect(normGetExcl()['main()']['globalMetrics']['wt-total-percentage'])->toEqual(100.0);
        });

        it('computes the correct wt-total-percentage for a direct child', function () {
            // funcA wt=60, main wt=100 → 60%
            expect(normGetExcl()['funcA']['globalMetrics']['wt-total-percentage'])->toEqual(60.0);
        });

        it('computes the correct wt-total-percentage for a nested child', function () {
            // funcC wt=40, main wt=100 → 40%
            expect(normGetExcl()['funcC']['globalMetrics']['wt-total-percentage'])->toEqual(40.0);
        });

        it('computes wt-excl for main() as the sum of its direct children contributions', function () {
            // funcA via main: wt=60 + funcB via main: wt=30 → 90
            expect(normGetExcl()['main()']['globalMetrics']['wt-excl'])->toBe(90);
        });

        it('computes wt-excl for funcA as the contribution of funcC', function () {
            // funcC via funcA: wt=40
            expect(normGetExcl()['funcA']['globalMetrics']['wt-excl'])->toBe(40);
        });

        it('sets wt-excl to 0 for leaf functions', function () {
            $n = normGetExcl();

            expect($n['funcB']['globalMetrics']['wt-excl'])->toBe(0)
                ->and($n['funcC']['globalMetrics']['wt-excl'])->toBe(0);
        });

        it('computes wt-excl-percentage for main()', function () {
            // 90 * 100 / 100 = 90
            expect(normGetExcl()['main()']['globalMetrics']['wt-excl-percentage'])->toEqual(90.0);
        });

        it('sets wt-excl-percentage to 0 for leaf functions', function () {
            $n = normGetExcl();

            expect($n['funcB']['globalMetrics']['wt-excl-percentage'])->toEqual(0.0)
                ->and($n['funcC']['globalMetrics']['wt-excl-percentage'])->toEqual(0.0);
        });

        it('exposes all four excl keys for every function', function () {
            $n    = normGetExcl();
            $keys = ['wt-excl', 'cpu-excl', 'mu-excl', 'pmu-excl'];

            foreach (['main()', 'funcA', 'funcB', 'funcC'] as $fn) {
                expect($n[$fn]['globalMetrics'])->toHaveKeys($keys);
            }
        });

        it('exposes all four total-percentage keys for every function', function () {
            $n    = normGetExcl();
            $keys = ['wt-total-percentage', 'cpu-total-percentage', 'mu-total-percentage', 'pmu-total-percentage'];

            foreach (['main()', 'funcA', 'funcB', 'funcC'] as $fn) {
                expect($n[$fn]['globalMetrics'])->toHaveKeys($keys);
            }
        });

        it('exposes all four excl-percentage keys for every function', function () {
            $n    = normGetExcl();
            $keys = ['wt-excl-percentage', 'cpu-excl-percentage', 'mu-excl-percentage', 'pmu-excl-percentage'];

            foreach (['main()', 'funcA', 'funcB', 'funcC'] as $fn) {
                expect($n[$fn]['globalMetrics'])->toHaveKeys($keys);
            }
        });

        it('returns 0 for every percentage metric when main() has zero values', function () {
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction([
                'main()'         => ['ct' => 1, 'wt' => 0, 'cpu' => 0, 'mu' => 0, 'pmu' => 0],
                'main()==>funcA' => ['ct' => 1, 'wt' => 0, 'cpu' => 0, 'mu' => 0, 'pmu' => 0],
            ]);
            $svc->createGlobalMetricByFunction();
            $svc->setChildrenFunctionInFunction();
            $svc->setExcludeMetricsAndPercentageMetrics();
            $n = normPrivate($svc, 'reportNormalized');

            expect($n['funcA']['globalMetrics']['wt-total-percentage'])->toEqual(0.0)
                ->and($n['funcA']['globalMetrics']['wt-excl-percentage'])->toEqual(0.0);
        });
    });

    // ── sortParentChildren / sortParentChildrenRecursive ──────────────────────
    describe('sortParentChildren', function () {

        it('places main() first in the sorted output', function () {
            $svc = normServiceAfterStep3();
            $svc->setExcludeMetricsAndPercentageMetrics();
            $svc->sortParentChildren();

            expect(array_key_first(normPrivate($svc, 'reportNormalizedSorted')))->toBe('main()');
        });

        it('includes every function in the output', function () {
            $svc = normServiceAfterStep3();
            $svc->setExcludeMetricsAndPercentageMetrics();
            $svc->sortParentChildren();

            expect(normPrivate($svc, 'reportNormalizedSorted'))
                ->toHaveKeys(['main()', 'funcA', 'funcB', 'funcC']);
        });

        it('does not duplicate any function', function () {
            $svc = normServiceAfterStep3();
            $svc->setExcludeMetricsAndPercentageMetrics();
            $svc->sortParentChildren();

            expect(normPrivate($svc, 'reportNormalizedSorted'))->toHaveCount(4);
        });

        it('places every parent before its direct children', function () {
            $svc = normServiceAfterStep3();
            $svc->setExcludeMetricsAndPercentageMetrics();
            $svc->sortParentChildren();
            $keys    = array_keys(normPrivate($svc, 'reportNormalizedSorted'));
            $posMain = array_search('main()', $keys);
            $posA    = array_search('funcA', $keys);
            $posB    = array_search('funcB', $keys);

            expect($posMain)->toBeLessThan($posA)
                ->and($posMain)->toBeLessThan($posB);
        });

        it('places funcA before its child funcC (depth-first)', function () {
            $svc = normServiceAfterStep3();
            $svc->setExcludeMetricsAndPercentageMetrics();
            $svc->sortParentChildren();
            $keys = array_keys(normPrivate($svc, 'reportNormalizedSorted'));

            expect(array_search('funcA', $keys))->toBeLessThan(array_search('funcC', $keys));
        });

        it('handles a diamond-shaped call graph without duplicating the shared node', function () {
            // main → funcA → funcShared
            // main → funcB → funcShared
            $svc = new XhprofNormalizerService();
            $svc->normalizeFunction(array_merge(normReport(), [
                'funcA==>funcShared' => ['ct' => 1, 'wt' => 5, 'cpu' => 4, 'mu' => 50, 'pmu' => 0],
                'funcB==>funcShared' => ['ct' => 1, 'wt' => 5, 'cpu' => 4, 'mu' => 50, 'pmu' => 0],
            ]));
            $svc->createGlobalMetricByFunction();
            $svc->setChildrenFunctionInFunction();
            $svc->setExcludeMetricsAndPercentageMetrics();
            $svc->sortParentChildren();
            $sorted = normPrivate($svc, 'reportNormalizedSorted');

            $occurrences = array_count_values(array_keys($sorted));
            expect($occurrences['funcShared'])->toBe(1);
        });
    });

    // ── normalize (full pipeline) ─────────────────────────────────────────────
    describe('normalize', function () {

        it('returns all function names from the raw report', function () {
            $result = (new XhprofNormalizerService())->normalize(normReport());

            expect($result)->toHaveKeys(['main()', 'funcA', 'funcB', 'funcC']);
        });

        it('places main() at the top of the returned array', function () {
            $result = (new XhprofNormalizerService())->normalize(normReport());

            expect(array_key_first($result))->toBe('main()');
        });

        it('each entry contains globalMetrics, metrics, parentFunction and childFunction', function () {
            $result = (new XhprofNormalizerService())->normalize(normReport());

            foreach ($result as $data) {
                expect($data)->toHaveKeys(['globalMetrics', 'metrics', 'parentFunction', 'childFunction']);
            }
        });

        it('resets internal state between two consecutive calls', function () {
            $svc = new XhprofNormalizerService();

            $svc->normalize(normReport());

            $onlyMain = ['main()' => ['ct' => 1, 'wt' => 10, 'cpu' => 8, 'mu' => 100, 'pmu' => 0]];
            $second   = $svc->normalize($onlyMain);

            expect($second)->toHaveCount(1)
                ->and($second)->toHaveKey('main()');
        });

        it('works for a minimal report that contains only main()', function () {
            $result = (new XhprofNormalizerService())->normalize([
                'main()' => ['ct' => 1, 'wt' => 10, 'cpu' => 8, 'mu' => 100, 'pmu' => 0],
            ]);

            expect($result)->toHaveCount(1)
                ->and($result['main()']['childFunction'])->toBeEmpty()
                ->and($result['main()']['parentFunction'])->toBeEmpty();
        });

        it('computes wt-total-percentage=100 for main() through the full pipeline', function () {
            $result = (new XhprofNormalizerService())->normalize(normReport());

            expect($result['main()']['globalMetrics']['wt-total-percentage'])->toEqual(100.0);
        });
    });
});
