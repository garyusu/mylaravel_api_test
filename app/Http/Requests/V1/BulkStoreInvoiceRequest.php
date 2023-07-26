<?php

namespace App\Http\Requests\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkStoreInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();
        return $user != null && $user->tokenCan('create'); 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        //回傳 用戶填寫的訊息(key-value)，required:必需提供，*代表傳入的陣列索引
        return [
            '*.customerId' => ['required', 'integer'], //整數
            '*.amount' => ['required', 'numeric'],  //數字
            '*.status' => ['required', Rule::in(['B', 'P', 'V', 'b', 'p', 'v'])],
            '*.billedDate' => ['required', 'date_format:Y-m-d H:i:s'], //日期格式
            '*.paidDate' => ['date_format:Y-m-d H:i:s', 'nullable'], //日期格式,可空值
        ];
    }

    protected function prepareForValidation()
    {
        $data = [];

        foreach ($this->toArray() as $obj) {
            //轉換key名為欄位名
            $obj['customer_id'] = $obj['customerId'] ?? null;
            $obj['billed_date'] = $obj['billedDate'] ?? null;
            $obj['paid_date'] = $obj['paidDate'] ?? null;

            $data[] = $obj;
        }
        //因customers表內的欄位名為postal_code，若不做此對應會導致insert時缺少此項目
        $this->merge($data);
    }
}

//多soup