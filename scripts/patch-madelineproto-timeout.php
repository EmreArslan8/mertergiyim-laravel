<?php

/**
 * MadelineProto'nun oturum yükleme timeout'unu paylaşımlı hostlara (alwaysdata)
 * uygun hale getirir: yavaş CPU + NFS'de büyük session'lar 30 sn'de yüklenemiyor
 * ve "Could not connect to MadelineProto" yanıltıcı hatası düşüyor.
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

$patched = str_replace(
    'Tools::getTimeoutCancellation(30.0, "Timeout during session unserialization!")',
    'Tools::getTimeoutCancellation(300.0, "Timeout during session unserialization!")',
    $content,
    $count,
);

if ($count === 0 && str_contains($content, 'Tools::getTimeoutCancellation(300.0, "Timeout during session unserialization!")')) {
    echo "madelineproto timeout: zaten yamalı, atlandı.\n";

    return;
}

if ($count === 0) {
    fwrite(STDERR, "madelineproto timeout: yama noktası bulunamadı (sürüm değişmiş olabilir).\n");
    exit(1);
}

if (file_put_contents($file, $patched) === false) {
    fwrite(STDERR, "madelineproto timeout: dosya yazılamadı.\n");
    exit(1);
}

echo "madelineproto timeout: 30 sn -> 300 sn yamalandı.\n";
