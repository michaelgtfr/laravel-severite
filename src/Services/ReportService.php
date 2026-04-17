<?php

namespace Severite\Services;

use Severite\Models\XhprofReport;

class ReportService
{
    //todo: récupèrer la list des raport
    public static function getListOfReport()
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
