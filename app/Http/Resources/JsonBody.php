<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JsonBody extends JsonResource
{
    private string $message;
    private int $status_code;

    /**
     * @param mixed $resource
     * @param string $message
     * @param int $status_code
     */
    public function __construct($resource, string $message = 'ok', int $status_code = 200)
    {
        parent::__construct($resource);
        $this->message = $message;
        $this->status_code = $status_code;
    }

    public function toArray(Request $request): array
    {
        return [
            'status_code' => $this->status_code,
            'data' => $this->resource,
            'message' => $this->message,
        ];
    }

    public function withResponse($request, $response)
    {
        $response->setStatusCode($this->status_code);
    }
}
