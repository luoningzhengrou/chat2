<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class GroupUserResource extends JsonResource
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
            'user' => UserResource::collection($this->user),
//            'id' => $this->id,
//            'name' => $this->nickname,
//            'sex' => $this->sex,
//            'phone' => $this->phone,
//            'avatar' => $this->avatar,
//            'area' => $this->area,
        ];
    }
}
