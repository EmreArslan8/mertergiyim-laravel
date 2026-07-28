-- Supabase PostgreSQL / eksik vitrin çevirilerini güvenli biçimde tamamlar.
-- Mevcut dolu çeviriler her zaman korunur. Yalnızca mevcut satırlar güncellenir.

BEGIN;

-- Kategoriler
WITH translations(slug, defaults) AS (
    VALUES
        ('kadin-giyim', '{
            "tr":"Kadın Giyim","en":"Women''s Clothing","ar":"ملابس نسائية",
            "ru":"Женская одежда","fa":"پوشاک زنانه","uk":"Жіночий одяг",
            "fr":"Vêtements pour femmes","de":"Damenbekleidung",
            "es":"Ropa de mujer","it":"Abbigliamento donna"
        }'::jsonb),
        ('elbiseler', '{
            "tr":"Elbiseler","en":"Dresses","ar":"فساتين","ru":"Платья",
            "fa":"پیراهن‌ها","uk":"Сукні","fr":"Robes","de":"Kleider",
            "es":"Vestidos","it":"Abiti"
        }'::jsonb),
        ('takimlar', '{
            "tr":"Takımlar","en":"Sets","ar":"أطقم","ru":"Комплекты",
            "fa":"ست‌ها","uk":"Комплекти","fr":"Ensembles","de":"Sets",
            "es":"Conjuntos","it":"Completi"
        }'::jsonb),
        ('keten', '{
            "tr":"Keten","en":"Linen","ar":"كتان","ru":"Лён","fa":"کتان",
            "uk":"Льон","fr":"Lin","de":"Leinen","es":"Lino","it":"Lino"
        }'::jsonb)
),
prepared AS (
    SELECT
        c.id,
        COALESCE(c.name_i18n::jsonb, '{}'::jsonb) ||
        COALESCE((
            SELECT jsonb_object_agg(entry.key, entry.value)
            FROM jsonb_each(t.defaults) AS entry
            WHERE btrim(COALESCE(c.name_i18n::jsonb ->> entry.key, '')) = ''
        ), '{}'::jsonb) AS merged
    FROM categories AS c
    JOIN translations AS t ON t.slug = c.slug
)
UPDATE categories AS c
SET name_i18n = p.merged
FROM prepared AS p
WHERE c.id = p.id
  AND COALESCE(c.name_i18n::jsonb, '{}'::jsonb) IS DISTINCT FROM p.merged;

-- Footer bağlantıları
WITH translations(link_key, defaults) AS (
    VALUES
        ('about', '{
            "tr":"Hakkımızda","en":"About Us","ar":"من نحن","ru":"О нас",
            "fa":"درباره ما","uk":"Про нас","fr":"À propos","de":"Über uns",
            "es":"Sobre nosotros","it":"Chi siamo"
        }'::jsonb),
        ('distance_sale', '{
            "tr":"Mesafeli Satış Sözleşmesi","en":"Distance Sales Agreement",
            "ar":"اتفاقية البيع عن بُعد","ru":"Договор дистанционной продажи",
            "fa":"قرارداد فروش از راه دور","uk":"Договір дистанційного продажу",
            "fr":"Contrat de vente à distance","de":"Fernabsatzvertrag",
            "es":"Contrato de venta a distancia","it":"Contratto di vendita a distanza"
        }'::jsonb),
        ('pre_information', '{
            "tr":"Ön Bilgilendirme Formu","en":"Preliminary Information Form",
            "ar":"نموذج المعلومات الأولية","ru":"Форма предварительной информации",
            "fa":"فرم اطلاعات اولیه","uk":"Форма попередньої інформації",
            "fr":"Formulaire d’information préalable","de":"Vorabinformation",
            "es":"Formulario de información previa","it":"Modulo informativo preliminare"
        }'::jsonb),
        ('privacy', '{
            "tr":"Gizlilik Politikası","en":"Privacy Policy","ar":"سياسة الخصوصية",
            "ru":"Политика конфиденциальности","fa":"سیاست حفظ حریم خصوصی",
            "uk":"Політика конфіденційності","fr":"Politique de confidentialité",
            "de":"Datenschutzerklärung","es":"Política de privacidad",
            "it":"Informativa sulla privacy"
        }'::jsonb),
        ('delivery', '{
            "tr":"Teslimat ve Kargo Politikası","en":"Delivery and Shipping Policy",
            "ar":"سياسة التوصيل والشحن","ru":"Политика доставки",
            "fa":"سیاست تحویل و ارسال","uk":"Політика доставки",
            "fr":"Politique de livraison et d’expédition",
            "de":"Liefer- und Versandbedingungen","es":"Política de entrega y envío",
            "it":"Politica di consegna e spedizione"
        }'::jsonb),
        ('refund_policy', '{
            "tr":"İptal ve Geri Ödeme Politikası","en":"Cancellation and Refund Policy",
            "ar":"سياسة الإلغاء واسترداد الأموال",
            "ru":"Политика отмены и возврата средств",
            "fa":"سیاست لغو و بازپرداخت",
            "uk":"Політика скасування та повернення коштів",
            "fr":"Politique d’annulation et de remboursement",
            "de":"Stornierungs- und Rückerstattungsrichtlinie",
            "es":"Política de cancelación y reembolso",
            "it":"Politica di cancellazione e rimborso"
        }'::jsonb),
        ('cookie_policy', '{
            "tr":"Çerez Politikası","en":"Cookie Policy",
            "ar":"سياسة ملفات تعريف الارتباط",
            "ru":"Политика использования файлов cookie","fa":"سیاست کوکی‌ها",
            "uk":"Політика використання файлів cookie",
            "fr":"Politique relative aux cookies","de":"Cookie-Richtlinie",
            "es":"Política de cookies","it":"Politica sui cookie"
        }'::jsonb),
        ('terms', '{
            "tr":"Kullanım Koşulları","en":"Terms of Use","ar":"شروط الاستخدام",
            "ru":"Условия использования","fa":"شرایط استفاده",
            "uk":"Умови використання","fr":"Conditions d’utilisation",
            "de":"Nutzungsbedingungen","es":"Condiciones de uso",
            "it":"Termini di utilizzo"
        }'::jsonb),
        ('return', '{
            "tr":"İade ve Değişim Koşulları","en":"Return and Exchange Conditions",
            "ar":"شروط الإرجاع والاستبدال","ru":"Условия возврата и обмена",
            "fa":"شرایط مرجوعی و تعویض","uk":"Умови повернення та обміну",
            "fr":"Conditions de retour et d’échange",
            "de":"Rückgabe- und Umtauschbedingungen",
            "es":"Condiciones de devolución y cambio",
            "it":"Condizioni di reso e cambio"
        }'::jsonb),
        ('kvkk', '{
            "tr":"KVKK Aydınlatma Metni","en":"Personal Data Protection Notice",
            "ar":"إشعار حماية البيانات الشخصية",
            "ru":"Уведомление о защите персональных данных",
            "fa":"اطلاعیه حفاظت از داده‌های شخصی",
            "uk":"Повідомлення про захист персональних даних",
            "fr":"Avis de protection des données personnelles",
            "de":"Hinweis zum Schutz personenbezogener Daten",
            "es":"Aviso de protección de datos personales",
            "it":"Informativa sulla protezione dei dati personali"
        }'::jsonb),
        ('whatsapp', '{
            "tr":"WhatsApp","en":"WhatsApp","ar":"WhatsApp","ru":"WhatsApp",
            "fa":"WhatsApp","uk":"WhatsApp","fr":"WhatsApp","de":"WhatsApp",
            "es":"WhatsApp","it":"WhatsApp"
        }'::jsonb),
        ('instagram', '{
            "tr":"Instagram","en":"Instagram","ar":"Instagram","ru":"Instagram",
            "fa":"Instagram","uk":"Instagram","fr":"Instagram","de":"Instagram",
            "es":"Instagram","it":"Instagram"
        }'::jsonb)
),
prepared AS (
    SELECT
        l.id,
        COALESCE(l.label::jsonb, '{}'::jsonb) ||
        COALESCE((
            SELECT jsonb_object_agg(entry.key, entry.value)
            FROM jsonb_each(t.defaults) AS entry
            WHERE btrim(COALESCE(l.label::jsonb ->> entry.key, '')) = ''
        ), '{}'::jsonb) AS merged
    FROM site_links AS l
    JOIN translations AS t ON t.link_key = l.link_key
    WHERE l.location = 'footer'
)
UPDATE site_links AS l
SET label = p.merged
FROM prepared AS p
WHERE l.id = p.id
  AND COALESCE(l.label::jsonb, '{}'::jsonb) IS DISTINCT FROM p.merged;

-- Header bağlantıları
WITH translations(link_key, defaults) AS (
    VALUES
        ('home', '{
            "tr":"Anasayfa","en":"Home","ar":"الرئيسية","ru":"Главная",
            "fa":"صفحه اصلی","uk":"Головна","fr":"Accueil","de":"Startseite",
            "es":"Inicio","it":"Home"
        }'::jsonb),
        ('new', '{
            "tr":"Yeni Gelenler","en":"New Arrivals","ar":"وصل حديثاً",
            "ru":"Новинки","fa":"جدیدترین‌ها","uk":"Новинки","fr":"Nouveautés",
            "de":"Neuheiten","es":"Novedades","it":"Nuovi Arrivi"
        }'::jsonb),
        ('categories', '{
            "tr":"Kategoriler","en":"Categories","ar":"الفئات","ru":"Категории",
            "fa":"دسته‌بندی‌ها","uk":"Категорії","fr":"Catégories",
            "de":"Kategorien","es":"Categorías","it":"Categorie"
        }'::jsonb),
        ('tracking', '{
            "tr":"Sipariş Takibi","en":"Order Tracking","ar":"تتبع الطلب",
            "ru":"Отслеживание заказа","fa":"پیگیری سفارش",
            "uk":"Відстеження замовлення","fr":"Suivi de commande",
            "de":"Sendungsverfolgung","es":"Seguimiento del pedido",
            "it":"Traccia l’ordine"
        }'::jsonb)
),
prepared AS (
    SELECT
        l.id,
        COALESCE(l.label::jsonb, '{}'::jsonb) ||
        COALESCE((
            SELECT jsonb_object_agg(entry.key, entry.value)
            FROM jsonb_each(t.defaults) AS entry
            WHERE btrim(COALESCE(l.label::jsonb ->> entry.key, '')) = ''
        ), '{}'::jsonb) AS merged
    FROM site_links AS l
    JOIN translations AS t ON t.link_key = l.link_key
    WHERE l.location = 'header'
)
UPDATE site_links AS l
SET label = p.merged
FROM prepared AS p
WHERE l.id = p.id
  AND COALESCE(l.label::jsonb, '{}'::jsonb) IS DISTINCT FROM p.merged;

-- 01–05 kodlu ürün adları ve ortak açıklama
WITH product_names(code, defaults) AS (
    VALUES
        ('01', '{
            "tr":"Kot Garnili Papertouch Kumaş Elbise",
            "en":"Denim-Trimmed Papertouch Fabric Dress",
            "ar":"فستان من قماش بابرتاتش بتفاصيل جينز",
            "ru":"Платье из ткани Papertouch с джинсовой отделкой",
            "fa":"لباس پارچه‌ای پپرتاچ با حاشیه جین",
            "uk":"Сукня з тканини Papertouch із джинсовим оздобленням",
            "fr":"Robe en tissu Papertouch avec garniture en denim",
            "de":"Papertouch-Stoffkleid mit Jeansbesatz",
            "es":"Vestido de tela Papertouch con ribete de denim",
            "it":"Abito in tessuto Papertouch con finiture in denim"
        }'::jsonb),
        ('02', '{
            "tr":"Keten Kumaş Şortlu Takım","en":"Linen Fabric Shorts Set",
            "ar":"طقم شورت من قماش الكتان",
            "ru":"Комплект с шортами из льняной ткани",
            "fa":"ست شلوارک پارچه‌ای کتان",
            "uk":"Комплект із шортами з лляної тканини",
            "fr":"Ensemble short en tissu de lin",
            "de":"Shorts-Set aus Leinenstoff",
            "es":"Conjunto de shorts de lino",
            "it":"Completo con pantaloncini in lino"
        }'::jsonb),
        ('03', '{
            "tr":"Keten Dantelli Bluz&Etek Takım",
            "en":"Linen Lace Blouse and Skirt Set",
            "ar":"طقم بلوزة وتنورة من الكتان والدانتيل",
            "ru":"Льняной комплект с кружевной блузкой и юбкой",
            "fa":"ست بلوز و دامن کتانی با توری",
            "uk":"Лляний комплект із мереживною блузкою та спідницею",
            "fr":"Ensemble blouse et jupe en lin et dentelle",
            "de":"Leinen-Set mit Spitzenbluse und Rock",
            "es":"Conjunto de blusa y falda de lino con encaje",
            "it":"Completo in lino con blusa e gonna in pizzo"
        }'::jsonb),
        ('04', '{
            "tr":"Zimmerman Desen Keten Takım",
            "en":"Zimmerman Pattern Linen Set",
            "ar":"طقم كتان بنقشة زيمرمان",
            "ru":"Льняной комплект с узором Zimmerman",
            "fa":"ست کتانی طرح زیمِرمن",
            "uk":"Лляний комплект із візерунком Zimmerman",
            "fr":"Ensemble en lin à motif Zimmerman",
            "de":"Leinen-Set mit Zimmerman-Muster",
            "es":"Conjunto de lino con estampado Zimmerman",
            "it":"Completo in lino con fantasia Zimmerman"
        }'::jsonb),
        ('05', '{
            "tr":"Zimmerman Model Keten Elbise",
            "en":"Zimmerman-Style Linen Dress",
            "ar":"فستان كتان بقصة زيمرمان",
            "ru":"Льняное платье в стиле Zimmerman",
            "fa":"پیراهن کتانی مدل زیمِرمن",
            "uk":"Лляна сукня в стилі Zimmerman",
            "fr":"Robe en lin style Zimmerman",
            "de":"Leinenkleid im Zimmerman-Stil",
            "es":"Vestido de lino estilo Zimmerman",
            "it":"Abito in lino stile Zimmerman"
        }'::jsonb)
),
description(defaults) AS (
    VALUES ('{
        "tr":"Satışlarımız toptandır. Ürünün serisi 6''lıdır. Kargo alıcıya aittir.",
        "en":"We sell wholesale. Each product set contains 6 pieces. Shipping costs are paid by the buyer.",
        "ar":"نبيع بالجملة. تتكون سلسلة المنتج من 6 قطع. يتحمل المشتري تكاليف الشحن.",
        "ru":"Мы продаём оптом. В комплект входит 6 единиц. Доставку оплачивает покупатель.",
        "fa":"فروش ما به‌صورت عمده است. هر سری محصول شامل ۶ عدد است. هزینه ارسال بر عهده خریدار است.",
        "uk":"Ми продаємо оптом. Комплект складається з 6 одиниць. Доставку оплачує покупець.",
        "fr":"Nous vendons en gros. Chaque série comprend 6 pièces. Les frais de livraison sont à la charge de l’acheteur.",
        "de":"Wir verkaufen im Großhandel. Eine Produktserie enthält 6 Teile. Die Versandkosten trägt der Käufer.",
        "es":"Vendemos al por mayor. Cada serie contiene 6 unidades. Los gastos de envío corren a cargo del comprador.",
        "it":"Vendiamo all’ingrosso. Ogni serie contiene 6 pezzi. Le spese di spedizione sono a carico dell’acquirente."
    }'::jsonb)
),
prepared AS (
    SELECT
        p.id,
        COALESCE(p.name::jsonb, '{}'::jsonb) ||
        COALESCE((
            SELECT jsonb_object_agg(entry.key, entry.value)
            FROM jsonb_each(n.defaults) AS entry
            WHERE btrim(COALESCE(p.name::jsonb ->> entry.key, '')) = ''
        ), '{}'::jsonb) AS merged_name,
        COALESCE(p.description::jsonb, '{}'::jsonb) ||
        COALESCE((
            SELECT jsonb_object_agg(entry.key, entry.value)
            FROM jsonb_each(d.defaults) AS entry
            WHERE btrim(COALESCE(p.description::jsonb ->> entry.key, '')) = ''
        ), '{}'::jsonb) AS merged_description
    FROM products AS p
    JOIN product_names AS n ON n.code = p.code
    CROSS JOIN description AS d
)
UPDATE products AS p
SET name = prepared.merged_name,
    description = prepared.merged_description
FROM prepared
WHERE p.id = prepared.id
  AND (
      COALESCE(p.name::jsonb, '{}'::jsonb) IS DISTINCT FROM prepared.merged_name
      OR COALESCE(p.description::jsonb, '{}'::jsonb)
         IS DISTINCT FROM prepared.merged_description
  );

-- Footer "Bilgilendirmeler" başlığı
WITH headings(locale, heading) AS (
    VALUES
        ('tr', 'Bilgilendirmeler'),
        ('en', 'Information'),
        ('ar', 'معلومات'),
        ('ru', 'Информация'),
        ('fa', 'اطلاعات'),
        ('uk', 'Інформація'),
        ('fr', 'Informations'),
        ('de', 'Informationen'),
        ('es', 'Información'),
        ('it', 'Informazioni')
),
current_setting AS (
    SELECT
        s.key,
        COALESCE(s.value::jsonb, '{}'::jsonb) AS current_value
    FROM site_settings AS s
    WHERE s.key = 'storefront'
),
locale_patch AS (
    SELECT
        cs.key,
        cs.current_value,
        jsonb_object_agg(
            h.locale,
            COALESCE(cs.current_value -> h.locale, '{}'::jsonb) ||
            CASE
                WHEN btrim(COALESCE(
                    cs.current_value -> h.locale ->> 'footerInfoTitle',
                    ''
                )) = ''
                THEN jsonb_build_object('footerInfoTitle', h.heading)
                ELSE '{}'::jsonb
            END
        ) AS patch
    FROM current_setting AS cs
    CROSS JOIN headings AS h
    GROUP BY cs.key, cs.current_value
),
prepared AS (
    SELECT
        key,
        current_value,
        current_value || patch AS merged
    FROM locale_patch
)
UPDATE site_settings AS s
SET value = p.merged
FROM prepared AS p
WHERE s.key = p.key
  AND p.current_value IS DISTINCT FROM p.merged;

COMMIT;

-- Kontrol sorguları (yalnızca okur)
SELECT slug, name_i18n
FROM categories
WHERE slug IN ('kadin-giyim', 'elbiseler', 'takimlar', 'keten')
ORDER BY slug;

SELECT location, link_key, label
FROM site_links
WHERE (location = 'header' AND link_key IN ('home', 'new', 'categories', 'tracking'))
   OR (location = 'footer' AND link_key IN (
       'about', 'distance_sale', 'pre_information', 'privacy', 'delivery',
       'refund_policy', 'cookie_policy', 'terms', 'return', 'kvkk',
       'whatsapp', 'instagram'
   ))
ORDER BY location, link_key;

SELECT code, name, description
FROM products
WHERE code IN ('01', '02', '03', '04', '05')
ORDER BY code;

SELECT
    key,
    value::jsonb #>> '{en,footerInfoTitle}' AS footer_info_title_en
FROM site_settings
WHERE key = 'storefront';
