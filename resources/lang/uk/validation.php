<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (uk)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => 'Поле «:attribute» обов’язкове для заповнення.',
    'string' => 'Поле «:attribute» має бути текстом.',
    'array' => 'Поле «:attribute» має бути списком.',
    'integer' => 'Поле «:attribute» має бути цілим числом.',
    'numeric' => 'Поле «:attribute» має бути числом.',
    'email' => 'Поле «:attribute» має бути коректною адресою е-пошти.',
    'uuid' => 'Поле «:attribute» має бути коректним UUID.',
    'url' => 'Поле «:attribute» має бути коректною адресою.',
    'date' => 'Поле «:attribute» має бути коректною датою.',
    'filled' => 'Поле «:attribute» не може бути порожнім.',
    'in' => 'Вибране значення поля «:attribute» некоректне.',
    'max' => [
        'string' => 'Поле «:attribute» не має перевищувати :max символів.',
        'array' => 'Поле «:attribute» не має містити більше :max елементів.',
        'numeric' => 'Поле «:attribute» не має бути більше :max.',
        'file' => 'Поле «:attribute» не має перевищувати :max кілобайт.',
    ],
    'min' => [
        'string' => 'Поле «:attribute» має містити не менше :min символів.',
        'array' => 'Поле «:attribute» має містити не менше :min елементів.',
        'numeric' => 'Поле «:attribute» має бути не менше :min.',
        'file' => 'Поле «:attribute» має бути не менше :min кілобайт.',
    ],

    'attributes' => [
        'customer_name' => 'ім’я та прізвище',
        'phone' => 'телефон',
        'address' => 'адреса',
        'note' => 'примітка',
        'items' => 'кошик',
        'name' => 'ім’я та прізвище',
        'email' => 'е-пошта',
        'subject' => 'тема',
        'message' => 'повідомлення',
    ],

];
