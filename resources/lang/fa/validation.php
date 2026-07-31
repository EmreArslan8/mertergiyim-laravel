<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (fa)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => 'فیلد :attribute الزامی است.',
    'string' => 'فیلد :attribute باید متن باشد.',
    'array' => 'فیلد :attribute باید یک فهرست باشد.',
    'integer' => 'فیلد :attribute باید عدد صحیح باشد.',
    'numeric' => 'فیلد :attribute باید عدد باشد.',
    'email' => 'فیلد :attribute باید یک نشانی ایمیل معتبر باشد.',
    'uuid' => 'فیلد :attribute باید یک UUID معتبر باشد.',
    'url' => 'فیلد :attribute باید یک نشانی معتبر باشد.',
    'date' => 'فیلد :attribute باید یک تاریخ معتبر باشد.',
    'filled' => 'فیلد :attribute نمی‌تواند خالی باشد.',
    'in' => 'مقدار انتخاب‌شده برای :attribute معتبر نیست.',
    'max' => [
        'string' => 'فیلد :attribute نباید بیشتر از :max نویسه باشد.',
        'array' => 'فیلد :attribute نباید بیشتر از :max مورد داشته باشد.',
        'numeric' => 'فیلد :attribute نباید بیشتر از :max باشد.',
        'file' => 'فیلد :attribute نباید بیشتر از :max کیلوبایت باشد.',
    ],
    'min' => [
        'string' => 'فیلد :attribute باید حداقل :min نویسه باشد.',
        'array' => 'فیلد :attribute باید حداقل :min مورد داشته باشد.',
        'numeric' => 'فیلد :attribute باید حداقل :min باشد.',
        'file' => 'فیلد :attribute باید حداقل :min کیلوبایت باشد.',
    ],

    'attributes' => [
        'customer_name' => 'نام و نام خانوادگی',
        'phone' => 'تلفن',
        'address' => 'نشانی',
        'note' => 'یادداشت',
        'items' => 'سبد خرید',
        'name' => 'نام و نام خانوادگی',
        'email' => 'ایمیل',
        'subject' => 'موضوع',
        'message' => 'پیام',
    ],

];
