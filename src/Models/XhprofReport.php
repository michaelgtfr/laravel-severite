<?php

namespace Severite\Models;

// use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

// #[Table(name: 'xhprof_report', key: 'id', keyType: 'string', incrementing: true)] // 👈 Laravel 13
class XhprofReport extends Model
{
    use HasUuids;

    protected $table = 'xhprof_report';  // 👈 Laravel 12
    protected $keyType = 'string'; // 👈 Laravel 12
    public $incrementing = false; // 👈 Laravel 12

    protected $fillable = [
        'id',
        'title',
        'report',
        'tag',
        'wall_time',
        'memory_usage',
        'peak_memory_usage',
        'central_processing_unit'
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'report' => 'array',
        ];
    }
}
