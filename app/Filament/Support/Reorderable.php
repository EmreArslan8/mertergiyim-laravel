<?php

namespace App\Filament\Support;

use Closure;
use Filament\Actions\Action;

/**
 * Tablolarda tekrar eden reorder tetikleyicisi için ortak yapılandırma.
 *
 * Etiket ("Manuel sırayı düzenle" / "Sıralamayı bitir") ve buton görünümü tek
 * yerden yönetilsin diye burada; her tablo `reorderRecordsTriggerAction`
 * içinde bunu kullanır.
 */
class Reorderable
{
    /**
     * Standart "Manuel sırayı düzenle / Sıralamayı bitir" tetikleyicisi.
     */
    public static function triggerAction(): Closure
    {
        return fn (Action $action, bool $isReordering): Action => $action
            ->label($isReordering ? 'Sıralamayı bitir' : 'Manuel sırayı düzenle')
            ->button();
    }
}
