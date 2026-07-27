<?php

namespace App\Observers;

use App\Support\StorefrontCache;

/**
 * Vitrini besleyen her modelin kayıt/güncelleme/silme sonrası cache'i tazeler.
 */
class StorefrontCacheObserver
{
    public function saved(): void
    {
        StorefrontCache::flush();
    }

    public function deleted(): void
    {
        StorefrontCache::flush();
    }
}
