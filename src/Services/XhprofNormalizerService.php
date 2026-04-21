<?php

namespace Severite\Services;

class XhprofNormalizerService
{
    private string $mainFunction = 'main()';

    private array $reportNormalized = [];

    private array $reportNormalizedSorted = [];

    private array $principalMetricsInPercentage = ['mu', 'wt', 'cpu', 'pmu'];

    /**
     * Runs the full normalization pipeline on a raw XHProf report array.
     *
     * Steps: parse edges → aggregate global metrics → resolve children
     *        → compute percentages/exclusives → depth-first sort.
     */
    public function normalize(array $rawReport): array
    {
        $this->reportNormalized       = [];
        $this->reportNormalizedSorted = [];

        $this->normalizeFunction($rawReport);
        $this->createGlobalMetricByFunction();
        $this->setChildrenFunctionInFunction();
        $this->setExcludeMetricsAndPercentageMetrics();
        $this->sortParentChildren();

        return $this->reportNormalizedSorted;
    }

    /**
     * Step 1 – Parses all XHProf edges into a keyed function map.
     *
     * XHProf keys are either "main()" (root) or "parent==>child" (call edge).
     */
    public function normalizeFunction(array $report): void
    {
        foreach ($report as $key => $metrics) {
            [$currentFunction, $parentFunction] = $this->getCurrentAndParentFunctionOnKey($key);

            $this->setFunctionAndMetricsInReportNormalized($currentFunction, $parentFunction, $metrics);
        }
    }

    /**
     * Splits an XHProf edge key on "==>" and returns [currentFunction, parentFunction].
     *
     * The root entry has no "==>" so parentFunction is null.
     */
    public function getCurrentAndParentFunctionOnKey(string $key): array
    {
        $parts = explode('==>', $key);

        if (count($parts) === 2) {
            return [$parts[1], $parts[0]];
        }

        return [$parts[0], null];
    }

    /**
     * Registers the per-parent metrics for a function.
     *
     * Root functions (no parent) use their own name as the metrics key.
     * If the function already exists a second parent entry is appended.
     */
    public function setFunctionAndMetricsInReportNormalized(
        string $currentFunction,
        ?string $parentFunction,
        array $metrics
    ): void {
        $metricsKey = $parentFunction ?? $currentFunction;
        $this->reportNormalized[$currentFunction]['metrics'][$metricsKey] = $metrics;
    }

    /**
     * Step 2 – Sums all per-parent metric values into a single globalMetrics entry.
     *
     * Also resolves parentFunction (empty array for main()).
     */
    public function createGlobalMetricByFunction(): void
    {
        foreach ($this->reportNormalized as $functionName => $functionData) {
            $globalMetrics = [];
            $parents       = [];

            foreach ($functionData['metrics'] as $parentName => $metricValues) {
                $parents[] = $parentName;

                foreach ($metricValues as $metric => $value) {
                    $globalMetrics[$metric] = ($globalMetrics[$metric] ?? 0) + $value;
                }
            }

            $this->reportNormalized[$functionName]['globalMetrics'] = $globalMetrics;
            $this->reportNormalized[$functionName]['parentFunction'] =
                $functionName === $this->mainFunction ? [] : $parents;
        }
    }

    /**
     * Step 3 – Populates childFunction for every function
     * by checking which functions list it as a parent.
     */
    public function setChildrenFunctionInFunction(): void
    {
        foreach (array_keys($this->reportNormalized) as $functionName) {
            $children = [];

            foreach ($this->reportNormalized as $otherName => $otherData) {
                if (in_array($functionName, $otherData['parentFunction'])) {
                    $children[] = $otherName;
                }
            }

            $this->reportNormalized[$functionName]['childFunction'] = $children;
        }
    }

    /**
     * Step 4 – Appends total-percentage, excl, and excl-percentage to each function's globalMetrics.
     *
     * Percentages are relative to main(). Exclusive metrics subtract the cost
     * attributed to direct children.
     */
    public function setExcludeMetricsAndPercentageMetrics(): void
    {
        $mainMetrics = $this->reportNormalized[$this->mainFunction]['globalMetrics'];

        foreach ($this->reportNormalized as $functionName => $functionData) {
            foreach ($this->principalMetricsInPercentage as $metric) {
                $this->reportNormalized[$functionName]['globalMetrics']["$metric-total-percentage"] =
                    !empty($mainMetrics[$metric])
                        ? ($functionData['globalMetrics'][$metric] * 100) / $mainMetrics[$metric]
                        : 0;
            }

            $exclMetrics = $this->sumChildrenContributions(
                $functionName,
                $functionData['childFunction']
            );

            $this->reportNormalized[$functionName]['globalMetrics'] += $exclMetrics;

            foreach ($this->principalMetricsInPercentage as $metric) {
                $this->reportNormalized[$functionName]['globalMetrics']["$metric-excl-percentage"] =
                    !empty($mainMetrics[$metric])
                        ? ($this->reportNormalized[$functionName]['globalMetrics']["$metric-excl"] * 100) / $mainMetrics[$metric]
                        : 0;
            }
        }
    }

    /**
     * Step 5 – Writes a depth-first ordered copy of the function map into reportNormalizedSorted.
     */
    public function sortParentChildren(): void
    {
        $this->reportNormalizedSorted[$this->mainFunction] =
            $this->reportNormalized[$this->mainFunction];

        foreach ($this->reportNormalized[$this->mainFunction]['childFunction'] as $child) {
            $this->sortParentChildrenRecursive($child);
        }
    }

    /**
     * Recursively appends a function and all its descendants to the sorted output.
     *
     * Already-visited functions are skipped to handle diamond-shaped call graphs.
     */
    public function sortParentChildrenRecursive(string $functionName): void
    {
        if (isset($this->reportNormalizedSorted[$functionName])) {
            return;
        }

        $this->reportNormalizedSorted[$functionName] = $this->reportNormalized[$functionName];

        foreach ($this->reportNormalized[$functionName]['childFunction'] as $child) {
            $this->sortParentChildrenRecursive($child);
        }
    }

    /**
     * Sums each child's metric values attributed to the given parent function.
     *
     * Returns an array keyed by "{metric}-excl".
     */
    private function sumChildrenContributions(string $functionName, array $children): array
    {
        $excl = array_fill_keys(
            array_map(fn (string $m) => "$m-excl", $this->principalMetricsInPercentage),
            0
        );

        foreach ($children as $childName) {
            foreach ($this->principalMetricsInPercentage as $metric) {
                $excl["$metric-excl"] += $this->reportNormalized[$childName]['metrics'][$functionName][$metric];
            }
        }

        return $excl;
    }
}
