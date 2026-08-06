<?php

/**
 * MadelineProto'yu paylaşımlı hostlara (alwaysdata) uygun hale getirir.
 *
 * 1) Oturum yükleme timeout'u: yavaş CPU + NFS'te büyük session'lar 30 sn'de
 *    yüklenemiyor ve "Could not connect to MadelineProto" yanıltıcı hatası
 *    düşüyor. 30 sn -> 300 sn.
 *
 * 2) Taze oturumdaki destruct + yeniden yükleme döngüsü: constructor fallback'i
 *    oturumu kaydedip MTProto'yu imha ediyor, sonra tekrar yüklüyor. NFS'te bu
 *    ikinci yükleme safe.php.lock paylaşımlı kilidinde (önceki EX kilidin
 *    release'u NFS'te silinmiyor) sonsuza dek bekliyor. İlk kayıt yeterli
 *    olduğu için döngü atlanıyor, canlı wrapper döndürülüyor.
 *
 * composer install/update sonrası (vendor silinip yeniden kurulunca) otomatik
 * yeniden uygulanır. Idempotent'tir: zaten yamalıysa dokunmaz.
 */

$file = __DIR__.'/../vendor/danog/madelineproto/src/API.php';

if (! is_file($file)) {
    fwrite(STDERR, 'API.php bulunamadı: '.$file.PHP_EOL);
    exit(1);
}

$content = file_get_contents($file);

if ($content === false) {
    fwrite(STDERR, 'API.php okunamadı.'.PHP_EOL);
    exit(1);
}

$ok = true;

// --- Yama 1: oturum yükleme timeout'u 30 sn -> 300 sn ----------------------

[$patched, $count] = [str_replace(
    'Tools::getTimeoutCancellation(30.0, "Timeout during session unserialization!")',
    'Tools::getTimeoutCancellation(300.0, "Timeout during session unserialization!")',
    $content,
    $count
), $count];

if ($count > 0) {
    $content = $patched;
    echo "madelineproto: oturum yükleme timeout'u 30 sn -> 300 sn yamalandı.\n";
} elseif (str_contains($content, 'Tools::getTimeoutCancellation(300.0, "Timeout during session unserialization!")')) {
    echo "madelineproto: timeout yaması zaten uygulanmış.\n";
} else {
    fwrite(STDERR, "madelineproto: timeout yama noktası bulunamadı (sürüm değişmiş olabilir).\n");
    $ok = false;
}

// --- Yama 2: taze oturumda destruct + yeniden yükleme döngüsünü atla --------

$dance = <<<'PHP'
        $this->destruct();
        if (!$this->connectToMadelineProto($settings)) {
            throw new Exception("Could not start IPC server!");
        }
PHP;

$replacement = <<<'PHP'
        // Paylaşımlı host yaması: destruct + yeniden yükleme döngüsü NFS'te
        // safe.php.lock paylaşımlı kilidinde sonsuz beklemeye düşüyor
        // (alwaysdata). Oturum yukarıda zaten kaydedildi; canlı sarmalayıcı
        // olduğu gibi döndürülüyor, kilitler __destruct'ta bırakılıyor.
        return;
PHP;

[$patched, $count] = [str_replace($dance, $replacement, $content, $count), $count];

if ($count > 0) {
    $content = $patched;
    echo "madelineproto: destruct/yeniden yükleme döngüsü atlandı.\n";
} elseif (str_contains($content, 'kilitler __destruct')) {
    echo "madelineproto: destruct yaması zaten uygulanmış.\n";
} else {
    fwrite(STDERR, "madelineproto: destruct yama noktası bulunamadı (sürüm değişmiş olabilir).\n");
    $ok = false;
}

if (! $ok) {
    exit(1);
}

if (file_put_contents($file, $content) === false) {
    fwrite(STDERR, "madelineproto: dosya yazılamadı.\n");
    exit(1);
}
