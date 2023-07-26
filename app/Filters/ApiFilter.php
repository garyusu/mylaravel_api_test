<?php

namespace App\Filters;

use Illuminate\Http\Request;


class ApiFilter {
    protected $safeParms = [
    ];

    protected $columnMap = [
    ];

    protected $operatorMap = [
    ];

    public function transform(Request $request) {
        $eloQuery = []; // [column, operator, query]
        // 逐一取出 safeParms 比對
        // $parm  ex. amount
        // $operators  ex. ['eq', 'lt', 'gt', 'lte', 'gte']
        foreach ( $this->safeParms as $parm => $operators ) {

            //傳入key 查詢$request是否有對應值
            $query = $request->query($parm);

            //沒對應值，查詢下一個
            if (!isset($query)) {
                continue;
            }
            
            //檢查 $this->columnMap[$parm]，對應值若不存在則賦值$parm
            $column = $this->columnMap[$parm] ?? $parm;

            // 依次比對 找出是哪個運算符號
            foreach ($operators as $operator) {
                if (isset($query[$operator])){
                    $eloQuery[] = [$column, $this->operatorMap[$operator], $query[$operator]];
                }
            }
        }
        return $eloQuery;
    }
}

//localhost:8000/api/v1/customers?postalCode[gt]=30000