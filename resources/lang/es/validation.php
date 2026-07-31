<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (es)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => 'El campo :attribute es obligatorio.',
    'string' => 'El campo :attribute debe ser texto.',
    'array' => 'El campo :attribute debe ser una lista.',
    'integer' => 'El campo :attribute debe ser un número entero.',
    'numeric' => 'El campo :attribute debe ser un número.',
    'email' => 'El campo :attribute debe ser una dirección de correo válida.',
    'uuid' => 'El campo :attribute debe ser un UUID válido.',
    'url' => 'El campo :attribute debe ser una dirección válida.',
    'date' => 'El campo :attribute debe ser una fecha válida.',
    'filled' => 'El campo :attribute no puede estar vacío.',
    'in' => 'El valor seleccionado para :attribute no es válido.',
    'max' => [
        'string' => 'El campo :attribute no puede tener más de :max caracteres.',
        'array' => 'El campo :attribute no puede tener más de :max elementos.',
        'numeric' => 'El campo :attribute no puede ser mayor que :max.',
        'file' => 'El campo :attribute no puede pesar más de :max kilobytes.',
    ],
    'min' => [
        'string' => 'El campo :attribute debe tener al menos :min caracteres.',
        'array' => 'El campo :attribute debe tener al menos :min elementos.',
        'numeric' => 'El campo :attribute debe ser al menos :min.',
        'file' => 'El campo :attribute debe pesar al menos :min kilobytes.',
    ],

    'attributes' => [
        'customer_name' => 'nombre completo',
        'phone' => 'teléfono',
        'address' => 'dirección',
        'note' => 'nota',
        'items' => 'carrito',
        'name' => 'nombre completo',
        'email' => 'correo electrónico',
        'subject' => 'asunto',
        'message' => 'mensaje',
    ],

];
