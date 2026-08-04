{{-- Görünür gövdesi yok: ana paneldeki ad kutusu zaten "Görselden üretiliyor…"
     yer tutucusunu gösteriyor. wire:init üretimi kendi isteğinde başlatır;
     bitince olay ana panele düşer ve ad kutusu dolar. --}}
<div wire:init="suggest"></div>
