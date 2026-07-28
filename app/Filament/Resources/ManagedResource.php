<?php

namespace App\Filament\Resources;

use App\Models\Order;
use Illuminate\Database\Eloquent\Model;

abstract class ManagedResource extends \Filament\Resources\Resource
{
    public static function canAccess(): bool
    {
        $user = filament()->auth()->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if ($user->isSuperAdmin() || $user->role === 'editor') {
            return true;
        }

        return $user->role === 'order_manager' && static::getModel() === Order::class;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canAccess();
    }
}
