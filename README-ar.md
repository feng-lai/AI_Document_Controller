
# وثائق وحدة التحكم Record.php

🌍 متوفر بـ:  
[English (README.md)](README.md) | [日本語 (README-jp.md)](README-jp.md) | [Español (README-es.md)](README-es.md) | [Português (README-pt.md)](README-pt.md)

## غرض الملف
تتعامل هذه الوحدة مع استرجاع سجلات تاريخ الكشف/إعادة الكتابة للذكاء الاصطناعي ضمن تطبيق إطار عمل ThinkPHP.

## الوظيفة الأساسية `Index()`

### الوظائف
- استرجاع سجلات كشف أو إعادة كتابة المستخدم الحالي  
- دعم النتائج مع الترقيم الصفحي  
- تصور نتائج الكشف لسهولة القراءة

### معلمات الطلب
| المعلمة    | النوع | القيمة الافتراضية | الوصف                       |
|------------|-------|--------------------|----------------------------|
| type       | int   | 1                  | نوع السجل (1=كشف، 2=إعادة كتابة) |
| page_size  | int   | 10                 | عدد العناصر في الصفحة       |

### شرح منطق الكود

```php
public function Index()
{
    // 1. الحصول على معلمات الطلب
    $type = input('type', 1);
    $limit = input('page_size', '10', 'intval');
    
    // 2. بناء شروط الاستعلام
    $where = [
        ['site_id', '=', self::$site_id],
        ['user_id', '=', self::$user['id']],
        ['is_delete', '=', 0]
    ];
    
    // 3. اختيار الجدول ديناميكيًا
    $dbName = ($type == 1) ? 'msg_detect' : 'msg_wyccheck';
    
    // 4. استعلام قاعدة البيانات
    $list = Db::name($dbName)
        ->where($where)
        ->field('id,message_input,response,create_time')
        ->order('id desc')
        ->paginate($limit)
        ->toArray();
    
    // 5. معالجة البيانات
    foreach ($list['data'] as $k => $v) {
        $list['data'][$k]['num'] = mb_strlen($v['message_input'], 'utf8');
        $list['data'][$k]['num2'] = mb_strlen($v['response'], 'utf8');
        $list['data'][$k]['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        
        if($type == 1){
            $response = json_decode($v['response'], true);
            if(isset($response['percent'])){
                $text = 'كتابة مختلطة';
                if($response['percent'] == 0) $text = 'كتابة بشرية';
                if($response['percent'] == 100) $text = 'منشأة بواسطة AI';
                
                $list['data'][$k]['response'] = $text.'(معدل AI:'.$response['percent'].'%)';
            }
        }
    }
    
    // 6. إرجاع الاستجابة المنسقة
    return successJson(['list' => $list]);
}
````

### معالجة البيانات

1. **حساب طول النص**

   * `num`: طول النص الأصلي
   * `num2`: طول النتيجة بعد المعالجة بواسطة AI
   * يستخدم `mb_strlen()` لحساب الأحرف متعددة البايت بدقة

2. **تصور الكشف** (نوع=1 فقط)

   * تحليل حقل `response` بصيغة JSON
   * تحويل قيمة `percent` إلى صيغة قابلة للقراءة:

     * 0% → "كتابة بشرية"
     * 100% → "منشأة بواسطة AI"
     * أخرى → "كتابة مختلطة"
   * صيغة الإخراج: `منشأة بواسطة AI(معدل AI:100%)`

3. **تنسيق الوقت**

   * تحويل الطابع الزمني إلى صيغة `Y-m-d H:i:s`

### ميزات الأمان

* شروط استعلام صارمة: الموقع الحالي، المستخدم الحالي، السجلات غير المحذوفة
* تنقية المعلمات (استخدام `intval`)
* اختيار الجدول ديناميكيًا لعزل البيانات

### هيكل الاستجابة

```json
{
  "list": {
    "data": [
      {
        "id": 123,
        "message_input": "النص الأصلي",
        "response": "منشأة بواسطة AI(معدل AI:100%)",
        "create_time": "2025-06-15 14:30:00",
        "num": 24,
        "num2": 18
      }
    ],
    "total": 45,
    "per_page": 10,
    "current_page": 1,
    "last_page": 5
  }
}
```

---

### ملاحظات التنفيذ

* يعتمد على وحدة تحكم `Base` للحصول على سياق المستخدم/الموقع
* يستخدم `think\facade\Db` لعمليات قاعدة البيانات
* جداول `msg_detect` و `msg_wyccheck` يجب أن تحتوي على الأعمدة اللازمة
* الطوابع الزمنية مخزنة كأعداد صحيحة لنظام Unix

