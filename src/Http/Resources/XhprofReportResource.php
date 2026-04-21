<?php

namespace Severite\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class XhprofReportResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'                     => $this->id,
            'title'                  => $this->title,
            'tag'                    => $this->tag,
            'wall_time'              => $this->wall_time,
            'memory_usage'           => $this->memory_usage,
            'peak_memory_usage'      => $this->peak_memory_usage,
            'central_processing_unit'=> $this->central_processing_unit,
        ];
    }
}
