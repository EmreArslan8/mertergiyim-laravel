<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Filament admin panelinin oturum açtığı kullanıcı tablosu.
 *
 * Supabase şemasında public.users yok; bu migration canlıya
 *   php artisan migrate --path=database/migrations/2026_07_27_100000_create_users_table.php
 * ile TEK BAŞINA uygulanır. Storefront tablolarına dokunmaz.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestampTz('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestampsTz();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
