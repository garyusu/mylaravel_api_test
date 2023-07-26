<?php

namespace App\Http\Resources\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        // return parent::toArray($request);
        return [
            // 指定要回傳的內容 (key: value)
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
            'email' => $this->email,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'postalCode' => $this->postal_code,  //郵政區號
            // $this->whenLoaded('invoices')：若'invoices'被加載（即該關聯存在），則返回關聯資源'invoices'的資料，否則返回'null'
            // invoices 為 App\Http\Resources\V1\Customer.php 內函式
            'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
        ];
    }
}
