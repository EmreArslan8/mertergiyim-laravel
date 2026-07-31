<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (de)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => ':attribute ist erforderlich.',
    'string' => ':attribute muss Text sein.',
    'array' => ':attribute muss eine Liste sein.',
    'integer' => ':attribute muss eine ganze Zahl sein.',
    'numeric' => ':attribute muss eine Zahl sein.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'uuid' => ':attribute muss eine gültige UUID sein.',
    'url' => ':attribute muss eine gültige Adresse sein.',
    'date' => ':attribute muss ein gültiges Datum sein.',
    'filled' => ':attribute darf nicht leer sein.',
    'in' => 'Die Auswahl für :attribute ist ungültig.',
    'max' => [
        'string' => ':attribute darf höchstens :max Zeichen lang sein.',
        'array' => ':attribute darf höchstens :max Einträge enthalten.',
        'numeric' => ':attribute darf höchstens :max sein.',
        'file' => ':attribute darf höchstens :max Kilobyte groß sein.',
    ],
    'min' => [
        'string' => ':attribute muss mindestens :min Zeichen lang sein.',
        'array' => ':attribute muss mindestens :min Einträge enthalten.',
        'numeric' => ':attribute muss mindestens :min sein.',
        'file' => ':attribute muss mindestens :min Kilobyte groß sein.',
    ],

    'attributes' => [
        'customer_name' => 'Name',
        'phone' => 'Telefon',
        'address' => 'Adresse',
        'note' => 'Notiz',
        'items' => 'Warenkorb',
        'name' => 'Name',
        'email' => 'E-Mail',
        'subject' => 'Betreff',
        'message' => 'Nachricht',
    ],

];
