<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuestionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'subject_id' => $this->subject_id,
            'class_id' => $this->class_id,
            'type' => $this->type,
            'content' => $this->content,
            'explanation' => $this->explanation,
            'difficulty' => $this->difficulty,
            'created_by' => $this->created_by,
            'options' => $this->options,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
