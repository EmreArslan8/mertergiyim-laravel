<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (en)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [

    // Mesajlar çerçevenin en dosyasından gelir; yalnızca alan adları.

    'attributes' => [
        'customer_name' => 'full name',
        'phone' => 'phone',
        'address' => 'address',
        'note' => 'note',
        'items' => 'cart',
        'name' => 'full name',
        'email' => 'e-mail',
        'subject' => 'subject',
        'message' => 'message',
    ],

];
