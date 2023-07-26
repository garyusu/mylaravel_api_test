教學YT來源：https://www.youtube.com/watch?v=YGqCZjdgJJk
跳過Composer安裝、Laravel佈署步驟

#### 開頭建置
* 透過終端輸入`php artisan serve`啟動Laravel
* 同時建立 Model, Factory, Migration, Seeder, Controller, Policy
* 終端建檔指令：
```
php artisan make:model Customer --all
php artisan make:model Invoice --all
```

# 資料庫: 建置table與資料填充

#### Models\
`Customer.php`中class Customer加入
```
public function invoices(){
    return $this->hasMany(Invoice::class);
}
```
`Invoice.php`中class Invoice加入
```
public function customer() {
    return $this->belongsTo(Customer::class);
}
```

#### table結構建立，路徑：database\migrations\
* 末尾有`create_customers_table`字樣的php檔
* `$table->id();` 下行加入
```
$table->string('name');
$table->string('type'); // individual or Business
$table->string('email');
$table->string('address');
$table->string('city');
$table->string('state');
$table->string('postal_code');
```

* 末尾有`create_invoices_table`字樣的php檔
* `$table->id();` 下行加入
```
$table->integer('customer_id');
$table->integer('amount');
$table->string('status');  //Billed, Paid, Void
$table->dateTime('billed_date');
$table->dateTime('paid_date')->nullable();
```


#### 設置資料產生方式 (隨機產生模擬資料)
路徑：`database\factories\`
* `CustomerFactory.php`中函式`definition`內改寫：
```
//faker 用於產生隨機內容
$type = $this->faker->randomElement(['I', 'B']);
$name = $type =='I' ? $this->faker->name() : $this->faker->company(); 

return [
    'name' => $name,
    'type' => $type,                
    'email' => $this->faker->email(),
    'address' => $this->faker->streetAddress(),
    'city' => $this->faker->city(),
    'state' => $this->faker->state(),   //州別
    'postal_code' => $this->faker->postCode()
];
```

* `InvoiceFactory.php`中函式`definition`內改寫
```
$status = $this->faker->randomElement(['B', 'P', 'V']);
return [
    'customer_id' => Customer::factory(),
    'amount' => $this->faker->numberBetween(100,20000),
    'status' => $status,
    'billed_date' => $this->faker->dateTimeThisDecade(),
    'paid_date' => $status == 'P' ? $this->faker->dateTimeThisDecade() : NULL
];
```


#### 分配資料生產量，路徑：database\seeders\
`CustomerSeeder.php`中函式`run`內加入
```
Customer::factory()
    ->count(25)
    ->hasInvoices(10)
    ->create();

Customer::factory()
    ->count(100)
    ->hasInvoices(5)
    ->create();

Customer::factory()
    ->count(100)
    ->hasInvoices(3)
    ->create();

Customer::factory()
    ->count(5)
    ->create();
```

`DatabaseSeeder.php`中函式`run`內加入
```
//執行 CustomerSeeder 的 run()
$this->call([
    CustomerSeeder::class
]);
```

#### 資料產出，透過終端輸入
```
php artisan migrate:fresh --seed
```
執行後查看資料庫確認


------------------
# 資料的回傳

## 一、建立版本(版控)
* 新建路徑`app\Http\Controllers\Api\V1`
* 將`app\Http\Controllers`下的`CustomerController.php`及`InvoiceController.php`丟到新路徑
* 兩檔分別改寫命名空間為：
```
namespace App\Http\Controllers\Api\V1;
```

* `CustomerController` 回傳另一物件，函式`index`內寫入：
```
return Customer::all();
```

#### 添加路由
路徑：`routes\api\api.php`
* 新增：
```
Route::group(['prefix'=>'v1', 'namespace'=>'App\Http\Controllers\Api\V1'], function(){
    
    Route::apiResource('customers', CustomerController::class);
    Route::apiResource('invoices', InvoiceController::class);
});
```

#### 測試，網址輸入
```
127.0.0.1:8000/api/v1/customers
```
回傳從customers table取出的所有內容


## 二、指定要回傳的項目 ex. id, email
* 用指令在`Resources\V1`下建立php檔案：`CustomerResource.php`
* 終端建檔指令：
```
php artisan make:resource V1\CustomerResource
```

#### resource物件設置
路徑：`app\Http\Resources\V1\CustomerResource.php`：
1. 改命名空間
```
namespace App\Http\Resources\V1;
```

2. 物件內指定要回傳的內容
在函式`toArray`內改寫
```
return [
    'id' => $this->id,
    'name' => $this->name,
    'type' => $this->type,
    'email' => $this->email,
    'address' => $this->address,
    'city' => $this->city,
    'state' => $this->state,
    'postalCode' => $this->postal_code,  //郵政區號
];
```

#### 控制器引用resource物件
路徑：`app\Http\Controllers\Api\V1\CustomerController.php`：
1. 導入區塊寫入：
```
use App\Http\Resources\V1\CustomerResource;
```
2. 函式`show`內，回傳resource物件
```
return new CustomerResource($customer);
```

3. 測試結果
網址：`localhost:8000/api/v1/customers/1`



## 三、數據統整化
* 用指令在`Resources\V1`下建立php檔案：`CustomerCollection.php` 
* 終端建檔指令：```php artisan make:resource V1\CustomerCollection```

路徑：`app\Http\Resources\V1\CustomerController.php`
控制器導入
1. 導入區塊寫入：
```
use App\Http\Resources\V1\CustomerCollection;
```

2. 改寫函式`index`：
```
return new CustomerCollection(Customer::paginate()); // paginate(): 分頁處理
```

#### 建立Invoice相關
終端建檔指令：
```
php artisan make:resource V1\InvoiceResource
php artisan make:resource V1\InvoiceCollection
```

路徑：`app\Http\Resources\V1\CustomerResource.php`
* 設置對應的table欄位，改寫函式 `toArray`：
```
return [
    'id' => $this->id,
    'customerId' => $this->customer_id,
    'amount' => $this->amount,
    'status' => $this->status,
    'billedDate' => $this->billed_date,
    'paidDate' => $this->paid_date
];
```


#### InvoiceController控制器導入
路徑：`app\Http\Resources\V1\InvoiceController.php`：
1. 導入區塊寫入：
```
use App\Http\Resources\V1\InvoiceResource;
use App\Http\Resources\V1\InvoiceCollection;
```

2. 改寫函式
* index：
```
return new InvoiceCollection(Invoice::paginate());
```
* show：
```
return new InvoiceResource($invoice);
```

## 四、篩選資料

1. 控制器引入DB查詢(query)規則 
路徑：`app\Http\Controllers\Api\V1\CustomerController.php`
* 函式`index`參數加入`Request $request`，並改寫函式內容：
```
$filter = new CustomerQuery();
$queryItems = $filter->transform($request); //['column', 'operator', 'value']

if (count($queryItems) == 0) {
    return new CustomerCollection(Customer::paginate());
} else {
    //where($queryItems): 根據 $queryItems 指定的條件進行資料庫查詢
    //paginate(): 分頁處理
    return new CustomerCollection(Customer::where($queryItems)->paginate());
}
```

1. 新建DB查詢規則
建立新路徑及PHP檔：`app\Services\V1\CustomerQuery.php`，寫入：
```
<?php
namespace App\Services\V1;
use Illuminate\Http\Request;

class CustomerQuery {
    protected $safeParms = [
        'name' => ['eq'],
        'type' => ['eq'],
        'email' => ['eq'],
        'address' => ['eq'],
        'city' => ['eq'],
        'state' => ['eq'],
        'postalCode' => ['eq', 'gt', 'lt']
    ];

    protected $columnMap = [
        'postalCode' => 'postal_code'
    ];

    protected $operatorMap = [
        'eq' => '=',
        'lt' => '<',
        'lte' => '<=',
        'gt' => '>',
        'gte' => '>='
    ];

    public function transform(Request $request) {
        $eloQuery = [];
        //逐一取出safeParms
        foreach ( $this->safeParms as $parm => $operators ) {
            //傳入key 查詢$request是否有對應值
            $query = $request->query($parm);

            //沒對應值，查詢下一個
            if (!isset($query)) {
                continue;
            }
            
            //檢查 $this->columnMap[$parm]，對應值若不存在則賦值$parm
            $column = $this->columnMap[$parm] ?? $parm;

            foreach ($operators as $operator) {
                if (isset($query[$operator])){
                    $eloQuery[] = [$column, $this->operatorMap[$operator], $query[$operator]];
                }
            }
        }
        return $eloQuery;
    }
}
```

3. 測試結果
* 網址：`http://localhost:8000/api/v1/customers?postalCode[gt]=30000`
* 規則：`postalCode[gt]=30000` 查詢`postalCode > 30000`的資料


## Filter架構 
#### Customer部分
* 改目錄名及檔名
1. `app`下的`Services`改為`Filters`
2. `app\Filters\V1\`下的`CustomerQuery.php`改為`CustomerFilter.php`

* 承上，改`CustomerFilter.php`內容：
1. 命名空間：`namespace App\Filters\V1;`
2. 加入導入：`use App\Filters\ApiFilter;`
3. 改class名:`class CustomerQuery`改為`class CustomerFilter extends ApiFilter`
4. 移除`transform`函式

* 改控制器
路徑：`app\Http\Controllers\Api\V1\CustomerController.php`
  1. 改導入路徑：
   ```use App\Services\V1\CustomerQuery;```
   改為
   ```use App\Filters\V1\CustomerFilter;```
  2. 物件名修正：
   ```$filter = new CustomerQuery();```
   改為
   ```$filter = new CustomerFilter();```


#### Invoice部分
* 建檔
  1. 直接複製另一`app\Filters\V1\`下的`CustomerFilter.php`到同層目錄，並改名`InvoiceFilter.php`
  2. 改class名：`CustomerFilter`改為`InvoicesFilter`

  * 需要對照DB的欄位名，參考創建DB table的欄位名
  * 可參照檔案：`database\migrations\`下，末尾有`create_invoices_table`字樣的php檔

* 改`app\Filters\V1\`下的`InvoiceFilter.php`：
  1. 可使用的運算子名，`$safeParms`陣列內改為：
    ```
    'customer_id' => ['eq'],
    'amount' => ['eq', 'lt', 'gt', 'lte', 'gte'],
    'status' => ['eq', 'ne'],
    'billed_date' => ['eq', 'lt', 'gt', 'lte', 'gte'],
    'paid_date' => ['eq', 'lt', 'gt', 'lte', 'gte']
    ```
  2. 對外開放的欄位名對應規則，`$columnMap`陣列內改為：
    ```
    'customerId' => 'customer_id',
    'billedDate' => 'billed_date',
    'paidDate' => 'paid_date'
    ```

* 運算子名稱對應，`$operatorMap`陣列內改為：
    ```
    'eq' => '=',
    'lt' => '<',
    'lte' => '<=',
    'gt' => '>',
    'gte' => '>=',
    'ne' => '!='
    ```

* 改控制器
路徑：`app\Http\Controllers\Api\V1\InvoiceController.php`

1. 導入區加入：
    ```use App\Filters\V1\CustomerFilter;```
2. 以`CustomerController.php`的index為基礎， 將index函式改為
    ```
    public function index(Request $request)
    {
        $filter = new InvoiceFilter();
        $queryItems = $filter->transform($request); //['column', 'operator', 'value']
        
        if (count($queryItems) == 0) {
            return new InvoiceCollection(Invoice::paginate());
        } else {
            //where($queryItems): 根據 $queryItems 指定的條件進行資料庫查詢
            //paginate(): 分頁處理
            return new InvoiceCollection(Invoice::where($queryItems)->paginate());
        }
    }
    ```
* 測試invoice，此時查看分頁網址未帶有條件式
測試網址：`http://localhost:8000/api/v1/invoices?status[eq]=P`

* 讓分頁網址也顯示條件式
    將
    ```
    return new InvoiceCollection(Invoice::where($queryItems)->paginate());
    ```
    改寫為：
    ```
    $invoice = Invoice::where($queryItems)->paginate();
    return new InvoiceCollection($invoice->appends($request->query()));
    ```

    `CustomerController.php`的index函式也同樣處理
    將
    ```return new CustomerCollection(Customer::where($queryItems)->paginate());```
    改寫為：
    ```
    $customer = Customer::where($queryItems)->paginate();
    return new CustomerCollection($customer->appends($request->query()));
    ```

* 網址查看 customer, invoice 分頁往是否都已帶有條件式
  * customer: `http://localhost:8000/api/v1/customers?postalCode[gt]=30000&type[eq]=I`
  * invoice: `http://localhost:8000/api/v1/invoices?status[eq]=P`

#### 導入關聯資料
* 改控制器 index 函式內容：
路徑：`app\Http\Controllers\Api\V1\CustomerController.php`
```
$filter = new CustomerFilter();
$filterItems = $filter->transform($request); // return ['column', 'operator', 'value']

$includeInvoices = $request->query('includeInvoices'); //網址是否有'includeInvoices'查詢
$customers = Customer::where($filterItems); // $filterItems作為sql查詢的where條件式

if ($includeInvoices) {
    $customers = $customers->with('invoices'); //調用 App\Models\Customer 的函式
}
return new CustomerCollection($customers->paginate()->appends($request->query()));
```

* 追加回傳的項目
路徑：`app\Http\Resources\V1\CustomerResource.php`
在 return 陣列的最後一行下加上
```
// $this->whenLoaded('invoices')：若'invoices'被加載（即該關聯存在），則返回關聯資源'invoices'的資料，否則返回'null'
// invoices 為 App\Http\Resources\V1\Customer.php 內函式
'invoices' => InvoiceResource::collection($this->whenLoaded('invoices')),
```

# 五、以 POST 方式將資料加到 DB table
1. 定義驗證規則 `StoreCustomerRequest.php `
注：新版本laravel執行`php artisan make:model Customer --all`會一起建立`StoreCustomerRequest.php `，不須額外在終端輸入指令

   * 控版：在`app\Http\Requests\`下建立 V1 資料夾，將`StoreCustomerRequest.php`移入並開啟
   * 改命名空間： ```namespace App\Http\Requests\V1;```
   * 新導入：```use Illuminate\Validation\Rule;```

   * 開放user傳送request：authorize 函式內改為 ```return true;```
   * 驗證規則，在 rules 函式的return[]內寫入：
   ```
   //'required'確保輸入值不為空
   'name' => ['required'],
   'type' => ['required', Rule::in(['I', 'B', 'i', 'b'])], //限定只能填入 in([])當中的字
   'email' => ['required', 'email'],
   'address' => ['required'],
   'city' => ['required'],
   'state' => ['required'],
   'postalCode' => ['required'],
   ```

   * 因user填的key名與欄位名有差異，所以必須再加上一段轉換功能
    在`class StoreCustomerRequest`內追加函式：
    ```
    protected function prepareForValidation()
    {   
        //因customers表內的欄位名為postal_code，若不做此對應會導致insert時缺少此項目
        $this->merge([
            'postal_code' => $this->postalCode
        ]);
    }
    ```


2. 控制器，創物件以及create()
路徑：`app\Http\Controllers\Api\V1\CustomerController.php`
   * 新導入，將
    ```use App\Http\Requests\StoreCustomerRequest;```
    改為
    ```use App\Http\Requests\V1\StoreCustomerRequest;```

   * store 函式改為
    ```
    public function store(StoreCustomerRequest $request)
    {
        return new CustomerResource(Customer::create($request->all()));
    }
    ```

3. 批量寫入資料庫 Customer.php
路徑：`app\Models\Customer.php`
   * 當控制器的 store 函式呼叫 create() 時，依`$fillable`列出的key名批量寫入DB
   * 在`use HasFactory;`的下行加上：
    ```
    protected $fillable = [
        'name',
        'type',
        'email',
        'address',
        'city',
        'state',
        'postal_code',
    ];
    ```

#### 以 Postman 驗證POST結果 (操作筆記)
1. 新建一個頁面
2. 網址填入：`http://localhost:8000/api/v1/customers`
3. 上方區塊：
    * 切到`Headers`，取消勾選`Accept`
    * 新加 Key：`Accept`-Value：`application/json`
    * 切到`Body`，選取`raw`，文字區塊填入要傳送Server端的內容，ex.
```
{
    "name": "柯P",
    "type": "i",
    "email": "xxx@ipevo.com",
    "address": "716 King Point",
    "city": "Taipei",
    "state": "Taiwan",
    "postalCode": "00000"
}
```
4. 下方區塊：
   * 下拉選單選取JSON，按下`Send`，下方會顯示回傳的Json內容
   * 開啟mysql檢查傳送的內容是否已新增到`customer` table裡


# 六、更新user資料 (PUT)
1. 定義驗證規則 `UpdateCustomerRequest.php`
    * 注：新版本laravel執行`php artisan make:model Customer --all`就會一起建立`StoreCustomerRequest.php `，不須額外在終端輸入指令

	* 控版：在`app\Http\Requests\`下建立 V1 資料夾，將`UpdateCustomerRequest.php`移入
	* 改命名空間：```namespace App\Http\Requests\V1;``` 

	* 以`StoreCustomerRequest.php`為基礎，將`authorize`,  `rules`, `prepareForValidation` 等函式的內容複製過來
	* 一併複製`use Illuminate\Validation\Rule;`到導入區

2. 改寫 rules 函式：
```
$method = $this->method();
if ($method == 'PUT') {
    return [
        'name' => ['required'],
        'type' => ['required', Rule::in(['I', 'B', 'i', 'b'])], //限定只能填入 in([])當中的字
        'email' => ['required', 'email'],
        'address' => ['required'],
        'city' => ['required'],
        'state' => ['required'],
        'postalCode' => ['required'],
    ];
} else {
    return [
        'name' => ['sometimes', 'required'],
        'type' => ['sometimes', 'required', Rule::in(['I', 'B', 'i', 'b'])], //限定只能填入 in([])當中的字
        'email' => ['sometimes', 'required', 'email'],
        'address' => ['sometimes', 'required'],
        'city' => ['sometimes', 'required'],
        'state' => ['sometimes', 'required'],
        'postalCode' => ['sometimes', 'required'],
    ];
}
```

3. prepareForValidation 函式需加上判斷式，改寫：
```
//因customers表內的欄位名為postal_code，若不做此對應會導致insert時缺少此項目
if ($this->postalCode) { // 若少了這行，當user未提供postalCode資料時會Error
    $this->merge([
        'postal_code' => $this->postalCode
    ]);
}
```

4. 控制器，改寫 update 函式
路徑：`app\Http\Controllers\Api\V1\CustomerController.php`
* 導入，將
    ```use App\Http\Requests\UpdateCustomerRequest;```
    改寫為
    ```use App\Http\Requests\V1\UpdateCustomerRequest;```

* update 函式改寫：
    ```
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $customer->update($request->all()); 
    }
    ```

#### 以 Postman 驗證PUT結果 (操作筆記)

1. 任以 POST 方式新增一筆，記下新增的id值，ex. 231
2. 網址輸入：`http://localhost:8000/api/v1/customers/231`，method選單切換到'PUT'
3. 上方區塊：
   * 文字區塊輸入要改動的key-value，ex.
    ```
    {
        "name": "柯P 2.0",
        "type": "B",
        "email": "XXxxx@ipevo.com",
        "address": "617 King Point",
        "city": "TTaipei",
        "state": "TTaiwan",
        "postalCode": "11111"
    }
    ```
4. 按下 Send 後，在瀏覽器輸入同樣網址確認是否已更改


# 七、批量處理請求 (Bulk) 
1. 定義驗證規則 `BulkStoreInvoiceRequest.php`
    路徑：`app\Http\Requests\V1\`
* 複製`StoreCustomerRequest.php`並改名為`BulkStoreInvoiceRequest.php`
* class名改為`BulkStoreInvoiceRequest`
* 改 rules 函式內容：
    ```
    //回傳 用戶填寫的訊息(key-value)，required:必需提供，*代表傳入的陣列索引
    return [
        '*.customerId' => ['required', 'integer'], //整數
        '*.amount' => ['required', 'numeric'],  //數字
        '*.status' => ['required', Rule::in(['B', 'P', 'V', 'b', 'p', 'v'])],
        '*.billedDate' => ['required', 'date_format:Y-m-d H:i:s'], //日期格式
        '*.paidDate' => ['date_format:Y-m-d H:i:s', 'nullable'], //日期格式,可空值
    ];
    ```
* 改 prepareForValidation 函式內容：
    ```
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
    ```

2. 定義路由
    路徑：`app\Http\Requests\V1\api.php`
* 新建路由，以POST傳送，在`Route::group`內新增：
    ```
    // 呼叫 InvoiceController 控制器的 bulkStore 函式
    // 陣列也可直接替換為 'InvoiceController@bulkStore'，
    Route::post('invoices/bulk', ['uses' => 'InvoiceController@bulkStore']); 
    ```

3. 控制器
   路徑：`app\Http\Controllers\Api\V1\InvoiceController.php`
* 新導入
    ```
    use Illuminate\Support\Arr;
    use App\Http\Requests\V1\BulkStoreInvoiceRequest;
    ```
* 改名 store 函式為 bulkStore，或下方新建一個，完整函式：
    ```
    public function bulkStore(BulkStoreInvoiceRequest $request)
    {
        $bulk = collect($request->all())->map(function($arr, $key){
            //刪除 $arr 中的 'customerId', 'billedDate', 'paidDate'
            return Arr::except($arr, ['customerId', 'billedDate', 'paidDate']);
        });

        Invoice::insert($bulk->toArray());
    }
    ```

#### 以 Postman 驗證POST結果 (操作筆記)
1. 上方區塊：
輸入想傳送的多組內容，可略過paidDate，ex.
    ```
    [{
        "customerId":"2",
        "amount":"240",
        "status":"P",
        "billedDate":"2021-09-23 14:29:49"
    },{
        "customerId":"3",
        "amount":"500",
        "status":"P",
        "billedDate":"2021-09-23 14:29:49",
        "paidDate":"2021-04-23 14:29:49"
    }]
    ```
2. 切換到 POST後，按下 Send。開啟 MySQL 的 invoices 表查看此新增


# 八、登入驗證:Token
1. 新增路由，創建並回傳token：
路徑：`routes\web.php`
```
// http://localhost:8000/setup
Route::get('/setup', function(){
    $credentials = [
        'email' => 'admin@admin.com',
        'password' => 'password'
    ];

    if (!Auth::attempt($credentials)) {
        $user = new \App\Models\User();

        $user->name = 'Admin';
        $user->email = $credentials['email'];
        $user->password = Hash::make($credentials['password']);

        $user->save();
        
        if (Auth::attempt($credentials)) {
            $user = Auth::user();
            
            //*************************************
            //* 新建token，規定各個 Token 賦予的編輯權限 
            //* createToken( '權限名', [允許的權限＿新增/刪除/修改] )
            //* @return class NewAccessToken
            //* path: vendor\laravel\sanctum\src\NewAccessToken
            //*************************************
            $adminToken = $user->createToken('admin-token', ['create', 'update', 'delete']);
            $updateToken = $user->createToken('update-token', ['create', 'update']);
            $basicToken = $user->createToken('basic-token');

            //回傳關聯陣列，DB也會在 'personal_access_tokens'新增此三筆
            return [
                'admin' => $adminToken->plainTextToken,
                'update' => $updateToken->plainTextToken,
                'basic' => $basicToken->plainTextToken,
            ];
        }
    }
});
```

2. 新加 key-value
* 在`Route::group`的參數`'namespace'=>'App\Http\Controllers\Api\V1'`後方新增一個key-value：
```'middleware'=>'auth:sanctum'```


#### 驗證 (操作筆記)，包含 Postman 操作

1. api發送應會回傳`Route [login] not defined.`錯誤，代表登入被驗證阻擋
   測試網址：`http://localhost:8000/api/v1/customers`
2. 到網址取得admin項目的token值
   網址：`http://localhost:8000/setup`
3. 開啟 Postman ，輸入 1.的網址，並切換到 `GET`
4. 上方區塊切換到`Authorization`頁面，Type選擇`Bearer Token`，右側Token欄輸入 2.
所取得的token值
5. 按下 Send 後確認下方區塊資料正確性


# 九、補上其他驗證
#### 改 authorize 函式內容：
* 在`app\Http\Requests\V1\`下的
1. `BulkStoreInvoiceRequest.php`
2. `UpdateCustomerRequest.php`
3. `StoreCustomerRequest.php`
    改 authorize函式內容：
    ```
    $user = $this->user();
    return $user != null && $user->tokenCan('create'); 
    ```
    而`UpdateCustomerRequest.php`需將`tokenCan('create')`改為`tokenCan('update')`

* 參照前面操作筆記操作、測試結果
  網址：`http://localhost:8000/api/v1/customers`
  三種Token 一一測試 POST、PATCH 兩種method方式，確認回傳結果是否有限制權限
  * 額外說明 PATCH 方式：
    * 可改 `Body` 將任意key的值更新其內容
      ex. DB已有 id:232 的一筆資料，想要將name的值改為customer1
      1. 網址填入：`http://localhost:8000/api/v1/customers/232`
      2. Body填入：
            ```
            {
                "name": "customer1"
            }
            ```
      3. 按Send送出後，即可以GET方式查看更改後的值，或DB直接查詢

---------------- END ----------------