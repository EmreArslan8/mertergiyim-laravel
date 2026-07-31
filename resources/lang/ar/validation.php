<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (ar)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => 'حقل :attribute مطلوب.',
    'string' => 'يجب أن يكون حقل :attribute نصًا.',
    'array' => 'يجب أن يكون حقل :attribute قائمة.',
    'integer' => 'يجب أن يكون حقل :attribute عددًا صحيحًا.',
    'numeric' => 'يجب أن يكون حقل :attribute رقمًا.',
    'email' => 'يجب أن يكون حقل :attribute بريدًا إلكترونيًا صالحًا.',
    'uuid' => 'يجب أن يكون حقل :attribute معرّف UUID صالحًا.',
    'url' => 'يجب أن يكون حقل :attribute عنوانًا صالحًا.',
    'date' => 'يجب أن يكون حقل :attribute تاريخًا صالحًا.',
    'filled' => 'لا يمكن أن يكون حقل :attribute فارغًا.',
    'in' => 'القيمة المختارة لحقل :attribute غير صالحة.',
    'max' => [
        'string' => 'يجب ألا يزيد حقل :attribute عن :max حرفًا.',
        'array' => 'يجب ألا يحتوي حقل :attribute على أكثر من :max عنصرًا.',
        'numeric' => 'يجب ألا يزيد حقل :attribute عن :max.',
        'file' => 'يجب ألا يزيد حجم حقل :attribute عن :max كيلوبايت.',
    ],
    'min' => [
        'string' => 'يجب أن يحتوي حقل :attribute على :min حرفًا على الأقل.',
        'array' => 'يجب أن يحتوي حقل :attribute على :min عنصرًا على الأقل.',
        'numeric' => 'يجب أن يكون حقل :attribute :min على الأقل.',
        'file' => 'يجب أن يكون حجم حقل :attribute :min كيلوبايت على الأقل.',
    ],

    'attributes' => [
        'customer_name' => 'الاسم الكامل',
        'phone' => 'الهاتف',
        'address' => 'العنوان',
        'note' => 'ملاحظة',
        'items' => 'سلة الشراء',
        'name' => 'الاسم الكامل',
        'email' => 'البريد الإلكتروني',
        'subject' => 'الموضوع',
        'message' => 'الرسالة',
    ],

];
