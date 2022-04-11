<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class KitResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'eyebrow_text' => $this->eyebrow_text,
            'title' => $this->title,
            'description' => $this->description,
            'meta_desc' => $this->meta_desc,
            'languages' => $this->languages,
            'status' => $this->status,
            'discount' => $this->discount,
            'image' => $this->image,
        ];
    }
}
