<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->translations() as $key => $translations) {
            $row = DB::table('site_links')
                ->where('location', 'footer')
                ->where('link_key', $key)
                ->first(['label']);

            if (! $row) {
                continue;
            }

            $existing = is_string($row->label)
                ? json_decode($row->label, true)
                : (array) $row->label;
            $existing = is_array($existing)
                ? array_filter($existing, fn ($value) => trim((string) $value) !== '')
                : [];

            // site_links tablosunda updated_at kolonu yok.
            DB::table('site_links')
                ->where('location', 'footer')
                ->where('link_key', $key)
                ->update([
                    'label' => json_encode(
                        array_merge($translations, $existing),
                        JSON_UNESCAPED_UNICODE,
                    ),
                ]);
        }
    }

    public function down(): void
    {
        // İçerik çevirileri geri alınırken silinmez.
    }

    /**
     * @return array<string, array<string, string>>
     */
    private function translations(): array
    {
        return [
            'about' => [
                'tr' => 'Hakkımızda', 'en' => 'About Us', 'ar' => 'من نحن', 'ru' => 'О нас',
                'fa' => 'درباره ما', 'uk' => 'Про нас', 'fr' => 'À propos', 'de' => 'Über uns',
                'es' => 'Sobre nosotros', 'it' => 'Chi siamo',
            ],
            'distance_sale' => [
                'tr' => 'Mesafeli Satış Sözleşmesi', 'en' => 'Distance Sales Agreement',
                'ar' => 'اتفاقية البيع عن بُعد', 'ru' => 'Договор дистанционной продажи',
                'fa' => 'قرارداد فروش از راه دور', 'uk' => 'Договір дистанційного продажу',
                'fr' => 'Contrat de vente à distance', 'de' => 'Fernabsatzvertrag',
                'es' => 'Contrato de venta a distancia', 'it' => 'Contratto di vendita a distanza',
            ],
            'pre_information' => [
                'tr' => 'Ön Bilgilendirme Formu', 'en' => 'Preliminary Information Form',
                'ar' => 'نموذج المعلومات الأولية', 'ru' => 'Форма предварительной информации',
                'fa' => 'فرم اطلاعات اولیه', 'uk' => 'Форма попередньої інформації',
                'fr' => 'Formulaire d’information préalable', 'de' => 'Vorabinformation',
                'es' => 'Formulario de información previa', 'it' => 'Modulo informativo preliminare',
            ],
            'privacy' => [
                'tr' => 'Gizlilik Politikası', 'en' => 'Privacy Policy', 'ar' => 'سياسة الخصوصية',
                'ru' => 'Политика конфиденциальности', 'fa' => 'سیاست حفظ حریم خصوصی',
                'uk' => 'Політика конфіденційності', 'fr' => 'Politique de confidentialité',
                'de' => 'Datenschutzerklärung', 'es' => 'Política de privacidad',
                'it' => 'Informativa sulla privacy',
            ],
            'delivery' => [
                'tr' => 'Teslimat ve Kargo Politikası', 'en' => 'Delivery and Shipping Policy',
                'ar' => 'سياسة التوصيل والشحن', 'ru' => 'Политика доставки',
                'fa' => 'سیاست تحویل و ارسال', 'uk' => 'Політика доставки',
                'fr' => 'Politique de livraison et d’expédition', 'de' => 'Liefer- und Versandbedingungen',
                'es' => 'Política de entrega y envío', 'it' => 'Politica di consegna e spedizione',
            ],
            'refund_policy' => [
                'tr' => 'İptal ve Geri Ödeme Politikası', 'en' => 'Cancellation and Refund Policy',
                'ar' => 'سياسة الإلغاء واسترداد الأموال', 'ru' => 'Политика отмены и возврата средств',
                'fa' => 'سیاست لغو و بازپرداخت', 'uk' => 'Політика скасування та повернення коштів',
                'fr' => 'Politique d’annulation et de remboursement',
                'de' => 'Stornierungs- und Rückerstattungsrichtlinie',
                'es' => 'Política de cancelación y reembolso',
                'it' => 'Politica di cancellazione e rimborso',
            ],
            'cookie_policy' => [
                'tr' => 'Çerez Politikası', 'en' => 'Cookie Policy', 'ar' => 'سياسة ملفات تعريف الارتباط',
                'ru' => 'Политика использования файлов cookie', 'fa' => 'سیاست کوکی‌ها',
                'uk' => 'Політика використання файлів cookie', 'fr' => 'Politique relative aux cookies',
                'de' => 'Cookie-Richtlinie', 'es' => 'Política de cookies', 'it' => 'Politica sui cookie',
            ],
            'terms' => [
                'tr' => 'Kullanım Koşulları', 'en' => 'Terms of Use', 'ar' => 'شروط الاستخدام',
                'ru' => 'Условия использования', 'fa' => 'شرایط استفاده', 'uk' => 'Умови використання',
                'fr' => 'Conditions d’utilisation', 'de' => 'Nutzungsbedingungen',
                'es' => 'Condiciones de uso', 'it' => 'Termini di utilizzo',
            ],
            'return' => [
                'tr' => 'İade ve Değişim Koşulları', 'en' => 'Return and Exchange Conditions',
                'ar' => 'شروط الإرجاع والاستبدال', 'ru' => 'Условия возврата и обмена',
                'fa' => 'شرایط مرجوعی و تعویض', 'uk' => 'Умови повернення та обміну',
                'fr' => 'Conditions de retour et d’échange', 'de' => 'Rückgabe- und Umtauschbedingungen',
                'es' => 'Condiciones de devolución y cambio', 'it' => 'Condizioni di reso e cambio',
            ],
            'kvkk' => [
                'tr' => 'KVKK Aydınlatma Metni', 'en' => 'Personal Data Protection Notice',
                'ar' => 'إشعار حماية البيانات الشخصية', 'ru' => 'Уведомление о защите персональных данных',
                'fa' => 'اطلاعیه حفاظت از داده‌های شخصی', 'uk' => 'Повідомлення про захист персональних даних',
                'fr' => 'Avis de protection des données personnelles',
                'de' => 'Hinweis zum Schutz personenbezogener Daten',
                'es' => 'Aviso de protección de datos personales',
                'it' => 'Informativa sulla protezione dei dati personali',
            ],
            'whatsapp' => array_fill_keys(config('storefront.locales'), 'WhatsApp'),
            'instagram' => array_fill_keys(config('storefront.locales'), 'Instagram'),
        ];
    }
};
