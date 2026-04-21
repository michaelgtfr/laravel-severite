<?php

use Severite\Services\XhprofNormalizerService;

// Minimal dataset: main → funcA → funcC, main → funcB
// Simple values to verify calculations by hand.
function minimalReport(): array
{
    return [
        'main()'         => ['ct' => 1, 'wt' => 100, 'cpu' => 90, 'mu' => 1000, 'pmu' => 900],
        'main()==>funcA' => ['ct' => 1, 'wt' => 60,  'cpu' => 55, 'mu' => 600,  'pmu' => 500],
        'main()==>funcB' => ['ct' => 2, 'wt' => 30,  'cpu' => 25, 'mu' => 300,  'pmu' => 200],
        'funcA==>funcC'  => ['ct' => 1, 'wt' => 40,  'cpu' => 35, 'mu' => 400,  'pmu' => 300],
    ];
}

// Reads a private property via reflection.
function readPrivate(object $object, string $property): mixed
{
    return (new ReflectionProperty($object, $property))->getValue($object);
}

// Service after the first three pipeline steps (normalize + globalMetrics + children).
function buildServiceAfterNormalize(): XhprofNormalizerService
{
    $service = new XhprofNormalizerService();
    $service->normalizeFunction(minimalReport());
    $service->createGlobalMetricByFunction();
    $service->setChildrenFunctionInFunction();
    return $service;
}

// reportNormalized after createGlobalMetricByFunction.
function getGlobalMetrics(): array
{
    $service = new XhprofNormalizerService();
    $service->normalizeFunction(minimalReport());
    $service->createGlobalMetricByFunction();
    return readPrivate($service, 'reportNormalized');
}

// reportNormalized after setExcludeMetricsAndPercentageMetrics.
function getExcludeMetrics(): array
{
    $service = buildServiceAfterNormalize();
    $service->setExcludeMetricsAndPercentageMetrics();
    return readPrivate($service, 'reportNormalized');
}

// reportNormalizedSorted after the full pipeline.
function getSorted(): array
{
    $service = buildServiceAfterNormalize();
    $service->setExcludeMetricsAndPercentageMetrics();
    $service->sortParentChildren();
    return readPrivate($service, 'reportNormalizedSorted');
}

describe('XhprofReportService', function () {

    // ────────────────────────────────────────────────────────────────────────
    describe('getCurrentAndParentFunctionOnKey', function () {
        it('extracts current and parent from a parent==>child key', function () {
            $service = new XhprofNormalizerService();
            [$current, $parent] = $service->getCurrentAndParentFunctionOnKey('parentFunc==>childFunc');

            expect($current)->toBe('childFunc')
                ->and($parent)->toBe('parentFunc');
        });

        it('returns null parent and the key itself as current for a root function', function () {
            $service = new XhprofNormalizerService();
            [$current, $parent] = $service->getCurrentAndParentFunctionOnKey('main()');

            expect($current)->toBe('main()')
                ->and($parent)->toBeNull();
        });

        it('handles fully-qualified class method names on both sides', function () {
            $service = new XhprofNormalizerService();
            [$current, $parent] = $service->getCurrentAndParentFunctionOnKey(
                'App\\Http\\Kernel::handle==>Illuminate\\Pipeline\\Pipeline::run'
            );

            expect($current)->toBe('Illuminate\\Pipeline\\Pipeline::run')
                ->and($parent)->toBe('App\\Http\\Kernel::handle');
        });
    });

    // ────────────────────────────────────────────────────────────────────────
    describe('normalizeFunction', function () {
        it('creates one entry per unique function name', function () {
            $service = new XhprofNormalizerService();
            $service->normalizeFunction(minimalReport());

            expect(readPrivate($service, 'reportNormalized'))
                ->toHaveKeys(['main()', 'funcA', 'funcB', 'funcC']);
        });

        it('stores metrics under the parent key for a child function', function () {
            $service = new XhprofNormalizerService();
            $service->normalizeFunction(minimalReport());

            expect(readPrivate($service, 'reportNormalized')['funcA']['metrics'])
                ->toHaveKey('main()');
        });

        it('stores metrics under its own key for the root function', function () {
            $service = new XhprofNormalizerService();
            $service->normalizeFunction(minimalReport());

            expect(readPrivate($service, 'reportNormalized')['main()']['metrics'])
                ->toHaveKey('main()');
        });

        it('preserves the raw metric values', function () {
            $service = new XhprofNormalizerService();
            $service->normalizeFunction(minimalReport());
            $normalized = readPrivate($service, 'reportNormalized');

            expect($normalized['funcA']['metrics']['main()']['wt'])->toBe(60)
                ->and($normalized['funcA']['metrics']['main()']['mu'])->toBe(600);
        });
    });

    // ────────────────────────────────────────────────────────────────────────
    describe('createGlobalMetricByFunction', function () {
        it('aggregates parent contributions into globalMetrics', function () {
            $normalized = getGlobalMetrics();

            expect($normalized['funcA']['globalMetrics']['wt'])->toBe(60)
                ->and($normalized['funcA']['globalMetrics']['mu'])->toBe(600);
        });

        it('sets an empty parentFunction for main()', function () {
            expect(getGlobalMetrics()['main()']['parentFunction'])->toBeEmpty();
        });

        it('identifies the correct parent of direct children of main()', function () {
            $normalized = getGlobalMetrics();

            expect($normalized['funcA']['parentFunction'])->toContain('main()')
                ->and($normalized['funcB']['parentFunction'])->toContain('main()');
        });

        it('identifies the correct parent of a nested function', function () {
            expect(getGlobalMetrics()['funcC']['parentFunction'])->toContain('funcA');
        });

        it('sums globalMetrics when a function is called from multiple parents', function () {
            // funcShared is called from both funcA (wt=10) and funcB (wt=15) → total wt = 25
            $service = new XhprofNormalizerService();
            $service->normalizeFunction(array_merge(minimalReport(), [
                'funcA==>funcShared' => ['ct' => 1, 'wt' => 10, 'cpu' => 8,  'mu' => 100, 'pmu' => 0],
                'funcB==>funcShared' => ['ct' => 1, 'wt' => 15, 'cpu' => 12, 'mu' => 150, 'pmu' => 0],
            ]));
            $service->createGlobalMetricByFunction();
            $normalized = readPrivate($service, 'reportNormalized');

            expect($normalized['funcShared']['globalMetrics']['wt'])->toBe(25)
                ->and($normalized['funcShared']['parentFunction'])->toContain('funcA')
                ->and($normalized['funcShared']['parentFunction'])->toContain('funcB');
        });
    });

    // ────────────────────────────────────────────────────────────────────────
    describe('setChildrenFunctionInFunction', function () {
        it('assigns the correct direct children to main()', function () {
            $normalized = readPrivate(buildServiceAfterNormalize(), 'reportNormalized');

            expect($normalized['main()']['childFunction'])
                ->toContain('funcA')
                ->and($normalized['main()']['childFunction'])->toContain('funcB');
        });

        it('assigns the correct child to a non-root function', function () {
            $normalized = readPrivate(buildServiceAfterNormalize(), 'reportNormalized');

            expect($normalized['funcA']['childFunction'])->toContain('funcC');
        });

        it('sets an empty childFunction for leaf functions', function () {
            $normalized = readPrivate(buildServiceAfterNormalize(), 'reportNormalized');

            expect($normalized['funcB']['childFunction'])->toBeEmpty()
                ->and($normalized['funcC']['childFunction'])->toBeEmpty();
        });
    });

    // ────────────────────────────────────────────────────────────────────────
    describe('setExcludeMetricsAndPercentageMetrics', function () {

        // ── total-percentage ─────────────────────────────────────────────────

        it('gives main() a 100% wt-total-percentage', function () {
            // 100 * 100 / 100 = 100
            expect(getExcludeMetrics()['main()']['globalMetrics']['wt-total-percentage'])
                ->toEqual(100);
        });

        it('calculates the correct wt-total-percentage for a direct child', function () {
            // funcA wt=60, main wt=100 → 60 * 100 / 100 = 60
            expect(getExcludeMetrics()['funcA']['globalMetrics']['wt-total-percentage'])
                ->toEqual(60);
        });

        it('calculates the correct wt-total-percentage for a nested child', function () {
            // funcC wt=40, main wt=100 → 40 * 100 / 100 = 40
            expect(getExcludeMetrics()['funcC']['globalMetrics']['wt-total-percentage'])
                ->toEqual(40);
        });

        // ── excl ────────────────────────────────────────────────────────────

        it('sums direct-children contributions into wt-excl of main()', function () {
            // funcA under main: wt=60 + funcB under main: wt=30 = 90
            expect(getExcludeMetrics()['main()']['globalMetrics']['wt-excl'])->toBe(90);
        });

        it('puts the single child contribution into wt-excl of funcA', function () {
            // funcC under funcA: wt=40
            expect(getExcludeMetrics()['funcA']['globalMetrics']['wt-excl'])->toBe(40);
        });

        it('sets wt-excl to 0 for leaf functions', function () {
            $normalized = getExcludeMetrics();

            expect($normalized['funcB']['globalMetrics']['wt-excl'])->toBe(0)
                ->and($normalized['funcC']['globalMetrics']['wt-excl'])->toBe(0);
        });

        // ── excl-percentage ─────────────────────────────────────────────────

        it('calculates the correct wt-excl-percentage for main()', function () {
            // 90 * 100 / 100 = 90
            expect(getExcludeMetrics()['main()']['globalMetrics']['wt-excl-percentage'])
                ->toEqual(90);
        });

        it('sets wt-excl-percentage to 0 for leaf functions', function () {
            $normalized = getExcludeMetrics();

            expect($normalized['funcB']['globalMetrics']['wt-excl-percentage'])->toEqual(0)
                ->and($normalized['funcC']['globalMetrics']['wt-excl-percentage'])->toEqual(0);
        });

        // ── structure ────────────────────────────────────────────────────────

        it('exposes all four excl and excl-percentage keys for every function', function () {
            $normalized = getExcludeMetrics();
            $expectedKeys = [
                'wt-excl', 'cpu-excl', 'mu-excl', 'pmu-excl',
                'wt-excl-percentage', 'cpu-excl-percentage',
                'mu-excl-percentage', 'pmu-excl-percentage',
            ];

            foreach (['main()', 'funcA', 'funcB', 'funcC'] as $fn) {
                expect($normalized[$fn]['globalMetrics'])->toHaveKeys($expectedKeys);
            }
        });

        it('exposes all four total-percentage keys for every function', function () {
            $normalized = getExcludeMetrics();
            $expectedKeys = [
                'wt-total-percentage', 'cpu-total-percentage',
                'mu-total-percentage', 'pmu-total-percentage',
            ];

            foreach (['main()', 'funcA', 'funcB', 'funcC'] as $fn) {
                expect($normalized[$fn]['globalMetrics'])->toHaveKeys($expectedKeys);
            }
        });
    });

    // ────────────────────────────────────────────────────────────────────────
    describe('sortParentChildren', function () {
        it('places main() first', function () {
            expect(array_key_first(getSorted()))->toBe('main()');
        });

        it('includes every function in the output', function () {
            expect(getSorted())->toHaveKeys(['main()', 'funcA', 'funcB', 'funcC']);
        });

        it('does not duplicate any function', function () {
            expect(count(getSorted()))->toBe(4);
        });

        it('places a parent before its direct children', function () {
            $keys = array_keys(getSorted());
            $posMain  = array_search('main()', $keys);
            $posFuncA = array_search('funcA', $keys);
            $posFuncB = array_search('funcB', $keys);

            expect($posMain)->toBeLessThan($posFuncA)
                ->and($posMain)->toBeLessThan($posFuncB);
        });

        it('places funcA before its child funcC', function () {
            $keys = array_keys(getSorted());

            expect(array_search('funcA', $keys))->toBeLessThan(array_search('funcC', $keys));
        });
    });
});
