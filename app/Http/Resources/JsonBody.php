<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JsonBody extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    private  $message, $status_code;

    /**
     * @param $resource
     * @param string $message
     * @param int $status_code
     */
    public function __construct($resource, string $message = 'ok', int $status_code = 200)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->status_code = $status_code;
    }

//public function __construct($resource)
//{
//    parent::__construct($resource);
//}


    public function toArray(Request $request): array
    {
        return ['data' => $this->resource, 'message' => $this->message, 'status_code' => $this->status_code];
    }
}
