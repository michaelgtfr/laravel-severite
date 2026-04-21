<?php

namespace Severite\Services;

use Illuminate\Database\Eloquent\Collection;
use Severite\Models\XhprofReport;

class ReportService
{
    public static function getListOfReport(): Collection
    {
        return XhprofReport::all([
            'id',
            'title',
            'tag',
            'wall_time',
            'memory_usage',
            'peak_memory_usage',
            'central_processing_unit'
        ]);
    }
}
