<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (it)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => 'Il campo :attribute è obbligatorio.',
    'string' => 'Il campo :attribute deve essere testo.',
    'array' => 'Il campo :attribute deve essere un elenco.',
    'integer' => 'Il campo :attribute deve essere un numero intero.',
    'numeric' => 'Il campo :attribute deve essere un numero.',
    'email' => 'Il campo :attribute deve essere un indirizzo e-mail valido.',
    'uuid' => 'Il campo :attribute deve essere un UUID valido.',
    'url' => 'Il campo :attribute deve essere un indirizzo valido.',
    'date' => 'Il campo :attribute deve essere una data valida.',
    'filled' => 'Il campo :attribute non può essere vuoto.',
    'in' => 'Il valore selezionato per :attribute non è valido.',
    'max' => [
        'string' => 'Il campo :attribute non può superare :max caratteri.',
        'array' => 'Il campo :attribute non può contenere più di :max elementi.',
        'numeric' => 'Il campo :attribute non può essere maggiore di :max.',
        'file' => 'Il campo :attribute non può superare :max kilobyte.',
    ],
    'min' => [
        'string' => 'Il campo :attribute deve contenere almeno :min caratteri.',
        'array' => 'Il campo :attribute deve contenere almeno :min elementi.',
        'numeric' => 'Il campo :attribute deve essere almeno :min.',
        'file' => 'Il campo :attribute deve essere almeno :min kilobyte.',
    ],

    'attributes' => [
        'customer_name' => 'nome e cognome',
        'phone' => 'telefono',
        'address' => 'indirizzo',
        'note' => 'nota',
        'items' => 'carrello',
        'name' => 'nome e cognome',
        'email' => 'e-mail',
        'subject' => 'oggetto',
        'message' => 'messaggio',
    ],

];
