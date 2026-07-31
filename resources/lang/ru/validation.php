<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (ru)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => 'Поле «:attribute» обязательно для заполнения.',
    'string' => 'Поле «:attribute» должно быть текстом.',
    'array' => 'Поле «:attribute» должно быть списком.',
    'integer' => 'Поле «:attribute» должно быть целым числом.',
    'numeric' => 'Поле «:attribute» должно быть числом.',
    'email' => 'Поле «:attribute» должно быть корректным адресом эл. почты.',
    'uuid' => 'Поле «:attribute» должно быть корректным UUID.',
    'url' => 'Поле «:attribute» должно быть корректным адресом.',
    'date' => 'Поле «:attribute» должно быть корректной датой.',
    'filled' => 'Поле «:attribute» не может быть пустым.',
    'in' => 'Выбранное значение поля «:attribute» некорректно.',
    'max' => [
        'string' => 'Поле «:attribute» не должно превышать :max символов.',
        'array' => 'Поле «:attribute» не должно содержать больше :max элементов.',
        'numeric' => 'Поле «:attribute» не должно быть больше :max.',
        'file' => 'Поле «:attribute» не должно превышать :max килобайт.',
    ],
    'min' => [
        'string' => 'Поле «:attribute» должно содержать не менее :min символов.',
        'array' => 'Поле «:attribute» должно содержать не менее :min элементов.',
        'numeric' => 'Поле «:attribute» должно быть не менее :min.',
        'file' => 'Поле «:attribute» должно быть не менее :min килобайт.',
    ],

    'attributes' => [
        'customer_name' => 'имя и фамилия',
        'phone' => 'телефон',
        'address' => 'адрес',
        'note' => 'примечание',
        'items' => 'корзина',
        'name' => 'имя и фамилия',
        'email' => 'эл. почта',
        'subject' => 'тема',
        'message' => 'сообщение',
    ],

];
