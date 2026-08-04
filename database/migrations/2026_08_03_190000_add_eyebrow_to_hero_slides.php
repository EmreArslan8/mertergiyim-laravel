<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table): void {
            // Başlığın üstündeki küçük yazı; önceden site adresinin host'undan
            // türetiliyordu (Site Ayarları > Site URL'si). Artık slaytın kendi
            // metni, site adresinden bağımsız ve doğrudan burada düzenleniyor.
            $table->json('eyebrow')->nullable()->after('title');
        });
    }

    public function down(): void
    {
        Schema::table('hero_slides', function (Blueprint $table): void {
            $table->dropColumn('eyebrow');
        });
    }
};
