<?php

namespace App\Filament\Resources;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

abstract class ManagedResource extends \Filament\Resources\Resource
{
    /** İçerik editörünün yönetebildiği katalog ve yayın kaynakları. */
    private const EDITOR_RESOURCES = [
        BlogPosts\BlogPostResource::class,
        Categories\CategoryResource::class,
        Colors\ColorResource::class,
        ContentPages\ContentPageResource::class,
        HeroSlides\HeroSlideResource::class,
        Homepage\HomepageResource::class,
        Media\MediaResource::class,
        Products\ProductResource::class,
        SiteLinks\SiteLinkResource::class,
        Sizes\SizeResource::class,
    ];

    public static function getModelLabel(): string
    {
        return Str::ucfirst(parent::getModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return Str::ucfirst(parent::getPluralModelLabel());
    }

    public static function canAccess(): bool
    {
        $user = filament()->auth()->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === 'editor') {
            return in_array(static::class, self::EDITOR_RESOURCES, true);
        }

        return $user->role === 'order_manager'
            && in_array(static::class, [
                Orders\OrderResource::class,
                Products\ProductResource::class,
            ], true);
    }

    /** Kayıt üzerinde değişiklik yapma yetkisi. */
    public static function canManage(): bool
    {
        $user = filament()->auth()->user();

        if (! $user || ! $user->is_active) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return true;
        }

        if ($user->role === 'editor') {
            return in_array(static::class, self::EDITOR_RESOURCES, true);
        }

        return $user->role === 'order_manager'
            && static::class === Orders\OrderResource::class;
    }

    public static function canViewAny(): bool
    {
        return static::canAccess();
    }

    public static function canCreate(): bool
    {
        return static::canManage();
    }

    public static function canView(Model $record): bool
    {
        return static::canAccess();
    }

    public static function canEdit(Model $record): bool
    {
        return static::canManage();
    }

    public static function canDelete(Model $record): bool
    {
        return static::canManage();
    }
}
