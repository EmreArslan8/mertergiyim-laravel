<?php

namespace App\Observers;

use App\Models\Category;
use App\Models\Color;
use App\Models\SiteSetting;
use App\Models\Size;
use App\Services\AdminOptionService;
use App\Support\BrandSettings;
use App\Support\StorefrontCache;
use Illuminate\Database\Eloquent\Model;

/**
 * Vitrini besleyen her modelin kayıt/güncelleme/silme sonrası cache'i tazeler.
 */
class StorefrontCacheObserver
{
    public function saved(Model $model): void
    {
        StorefrontCache::flushFor($model);
        $this->flushAdminOptionsWhenNeeded($model);
        $this->flushBrandWhenNeeded($model);
    }

    public function deleted(Model $model): void
    {
        StorefrontCache::flushFor($model);
        $this->flushAdminOptionsWhenNeeded($model);
        $this->flushBrandWhenNeeded($model);
    }

    private function flushAdminOptionsWhenNeeded(Model $model): void
    {
        if ($model instanceof Category || $model instanceof Size || $model instanceof Color) {
            AdminOptionService::flush();
        }
    }

    private function flushBrandWhenNeeded(Model $model): void
    {
        if ($model instanceof SiteSetting) {
            BrandSettings::flush();
        }
    }
}
