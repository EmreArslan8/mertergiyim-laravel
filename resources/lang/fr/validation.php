<?php

/*
|--------------------------------------------------------------------------
| Vitrin doğrulama mesajları (fr)
|--------------------------------------------------------------------------
|
| Laravel yalnızca İngilizce dosyaları paketle gönderir. Bu dosya olmadan
| bu dildeki ziyaretçi "validation.required" gibi ham anahtar görür.
| Kapsam: sepet ve iletişim formlarının tetikleyebildiği kurallar.
| Panel yalnızca Türkçe kullanıldığı için tam liste tr/validation.php'de.
|
*/

return [
    'required' => 'Le champ :attribute est obligatoire.',
    'string' => 'Le champ :attribute doit être du texte.',
    'array' => 'Le champ :attribute doit être une liste.',
    'integer' => 'Le champ :attribute doit être un nombre entier.',
    'numeric' => 'Le champ :attribute doit être un nombre.',
    'email' => 'Le champ :attribute doit être une adresse e-mail valide.',
    'uuid' => 'Le champ :attribute doit être un UUID valide.',
    'url' => 'Le champ :attribute doit être une adresse valide.',
    'date' => 'Le champ :attribute doit être une date valide.',
    'filled' => 'Le champ :attribute ne peut pas être vide.',
    'in' => 'La valeur sélectionnée pour :attribute est invalide.',
    'max' => [
        'string' => 'Le champ :attribute ne peut pas dépasser :max caractères.',
        'array' => 'Le champ :attribute ne peut pas contenir plus de :max éléments.',
        'numeric' => 'Le champ :attribute ne peut pas être supérieur à :max.',
        'file' => 'Le champ :attribute ne peut pas dépasser :max kilo-octets.',
    ],
    'min' => [
        'string' => 'Le champ :attribute doit contenir au moins :min caractères.',
        'array' => 'Le champ :attribute doit contenir au moins :min éléments.',
        'numeric' => 'Le champ :attribute doit être au moins :min.',
        'file' => 'Le champ :attribute doit faire au moins :min kilo-octets.',
    ],

    'attributes' => [
        'customer_name' => 'nom complet',
        'phone' => 'téléphone',
        'address' => 'adresse',
        'note' => 'note',
        'items' => 'panier',
        'name' => 'nom complet',
        'email' => 'e-mail',
        'subject' => 'objet',
        'message' => 'message',
    ],

];
