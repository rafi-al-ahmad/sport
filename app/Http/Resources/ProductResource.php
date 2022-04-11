<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        $productData = [
            'id' => $this->id,
            'eyebrow_text' => $this->eyebrow_text,
            'title' => $this->title,
            'description' => $this->description,
            'meta_desc' => $this->meta_desc,
            'code' => $this->code,
            'category_id' => $this->category_id,
            'languages' => $this->languages,
            'status' => $this->status,
            'options' => $this->variant_options,
            'variants' => $this->variants,
        ];

        return $productData;
    }
}
