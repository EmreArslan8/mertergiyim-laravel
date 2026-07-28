<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $description = $this->descriptionTranslations();

        foreach ($this->productNameTranslations() as $code => $name) {
            $product = DB::table('products')->where('code', $code)->first(['name', 'description']);

            if (! $product) {
                continue;
            }

            DB::table('products')->where('code', $code)->update([
                'name' => $this->mergeJson($name, $product->name),
                'description' => $this->mergeJson($description, $product->description),
                'updated_at' => now(),
            ]);
        }

        $setting = DB::table('site_settings')->where('key', 'storefront')->first(['value']);

        if ($setting) {
            $value = is_string($setting->value)
                ? json_decode($setting->value, true)
                : (array) $setting->value;
            $value = is_array($value) ? $value : [];

            foreach ($this->footerHeadingTranslations() as $locale => $heading) {
                if (blank($value[$locale]['footerInfoTitle'] ?? null)) {
                    $value[$locale]['footerInfoTitle'] = $heading;
                }
            }

            DB::table('site_settings')->where('key', 'storefront')->update([
                'value' => json_encode($value, JSON_UNESCAPED_UNICODE),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // İçerik çevirileri geri alınırken silinmez.
    }

    /**
     * @param  array<string, string>  $defaults
     */
    private function mergeJson(array $defaults, mixed $stored): string
    {
        $existing = is_string($stored) ? json_decode($stored, true) : (array) $stored;
        $existing = is_array($existing)
            ? array_filter($existing, fn ($value) => trim((string) $value) !== '')
            : [];

        return json_encode(array_merge($defaults, $existing), JSON_UNESCAPED_UNICODE);
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function productNameTranslations(): array
    {
        return [
            '01' => [
                'tr' => 'Kot Garnili Papertouch Kumaş Elbise',
                'en' => 'Denim-Trimmed Papertouch Fabric Dress',
                'ar' => 'فستان من قماش بابرتاتش بتفاصيل جينز',
                'ru' => 'Платье из ткани Papertouch с джинсовой отделкой',
                'fa' => 'لباس پارچه‌ای پپرتاچ با حاشیه جین',
                'uk' => 'Сукня з тканини Papertouch із джинсовим оздобленням',
                'fr' => 'Robe en tissu Papertouch avec garniture en denim',
                'de' => 'Papertouch-Stoffkleid mit Jeansbesatz',
                'es' => 'Vestido de tela Papertouch con ribete de denim',
                'it' => 'Abito in tessuto Papertouch con finiture in denim',
            ],
            '02' => [
                'tr' => 'Keten Kumaş Şortlu Takım',
                'en' => 'Linen Fabric Shorts Set',
                'ar' => 'طقم شورت من قماش الكتان',
                'ru' => 'Комплект с шортами из льняной ткани',
                'fa' => 'ست شلوارک پارچه‌ای کتان',
                'uk' => 'Комплект із шортами з лляної тканини',
                'fr' => 'Ensemble short en tissu de lin',
                'de' => 'Shorts-Set aus Leinenstoff',
                'es' => 'Conjunto de shorts de lino',
                'it' => 'Completo con pantaloncini in lino',
            ],
            '03' => [
                'tr' => 'Keten Dantelli Bluz&Etek Takım',
                'en' => 'Linen Lace Blouse and Skirt Set',
                'ar' => 'طقم بلوزة وتنورة من الكتان والدانتيل',
                'ru' => 'Льняной комплект с кружевной блузкой и юбкой',
                'fa' => 'ست بلوز و دامن کتانی با توری',
                'uk' => 'Лляний комплект із мереживною блузкою та спідницею',
                'fr' => 'Ensemble blouse et jupe en lin et dentelle',
                'de' => 'Leinen-Set mit Spitzenbluse und Rock',
                'es' => 'Conjunto de blusa y falda de lino con encaje',
                'it' => 'Completo in lino con blusa e gonna in pizzo',
            ],
            '04' => [
                'tr' => 'Zimmerman Desen Keten Takım',
                'en' => 'Zimmerman Pattern Linen Set',
                'ar' => 'طقم كتان بنقشة زيمرمان',
                'ru' => 'Льняной комплект с узором Zimmerman',
                'fa' => 'ست کتانی طرح زیمِرمن',
                'uk' => 'Лляний комплект із візерунком Zimmerman',
                'fr' => 'Ensemble en lin à motif Zimmerman',
                'de' => 'Leinen-Set mit Zimmerman-Muster',
                'es' => 'Conjunto de lino con estampado Zimmerman',
                'it' => 'Completo in lino con fantasia Zimmerman',
            ],
            '05' => [
                'tr' => 'Zimmerman Model Keten Elbise',
                'en' => 'Zimmerman-Style Linen Dress',
                'ar' => 'فستان كتان بقصة زيمرمان',
                'ru' => 'Льняное платье в стиле Zimmerman',
                'fa' => 'پیراهن کتانی مدل زیمِرمن',
                'uk' => 'Лляна сукня в стилі Zimmerman',
                'fr' => 'Robe en lin style Zimmerman',
                'de' => 'Leinenkleid im Zimmerman-Stil',
                'es' => 'Vestido de lino estilo Zimmerman',
                'it' => 'Abito in lino stile Zimmerman',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function descriptionTranslations(): array
    {
        return [
            'tr' => "Satışlarımız toptandır. Ürünün serisi 6'lıdır. Kargo alıcıya aittir.",
            'en' => 'We sell wholesale. Each product set contains 6 pieces. Shipping costs are paid by the buyer.',
            'ar' => 'نبيع بالجملة. تتكون سلسلة المنتج من 6 قطع. يتحمل المشتري تكاليف الشحن.',
            'ru' => 'Мы продаём оптом. В комплект входит 6 единиц. Доставку оплачивает покупатель.',
            'fa' => 'فروش ما به‌صورت عمده است. هر سری محصول شامل ۶ عدد است. هزینه ارسال بر عهده خریدار است.',
            'uk' => 'Ми продаємо оптом. Комплект складається з 6 одиниць. Доставку оплачує покупець.',
            'fr' => 'Nous vendons en gros. Chaque série comprend 6 pièces. Les frais de livraison sont à la charge de l’acheteur.',
            'de' => 'Wir verkaufen im Großhandel. Eine Produktserie enthält 6 Teile. Die Versandkosten trägt der Käufer.',
            'es' => 'Vendemos al por mayor. Cada serie contiene 6 unidades. Los gastos de envío corren a cargo del comprador.',
            'it' => 'Vendiamo all’ingrosso. Ogni serie contiene 6 pezzi. Le spese di spedizione sono a carico dell’acquirente.',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function footerHeadingTranslations(): array
    {
        return [
            'tr' => 'Bilgilendirmeler',
            'en' => 'Information',
            'ar' => 'معلومات',
            'ru' => 'Информация',
            'fa' => 'اطلاعات',
            'uk' => 'Інформація',
            'fr' => 'Informations',
            'de' => 'Informationen',
            'es' => 'Información',
            'it' => 'Informazioni',
        ];
    }
};
