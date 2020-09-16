<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'union_id' => $this->union_id,
            'name' => $this->name,
            'code' => $this->code,
            'announcement' => $this->announcement,
            'start_time' => $this->start_time ?: '',
            'end_time' => $this->end_time ?: '',
//            'user' => new GroupUserResource($this->whenLoaded('user')),
//            'file' => new GroupFileResource($this->whenLoaded('file')),
        ];
    }
}
