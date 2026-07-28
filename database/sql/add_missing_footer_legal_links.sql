begin;

insert into content_pages (
    id,
    slug,
    title,
    content,
    active,
    sort_order,
    created_at,
    updated_at
)
values
    (
        gen_random_uuid(),
        'mesafeli-satis-sozlesmesi',
        '{"tr":"Mesafeli Satış Sözleşmesi"}'::json,
        '{"tr":"Bu sözleşme, internet sitesi üzerinden verilen siparişlerde tarafların hak ve yükümlülüklerini düzenler.\n\nSiparişin oluşturulmasıyla ürünün temel özellikleri, satış bedeli, ödeme ve teslimat koşulları müşteri tarafından kabul edilmiş sayılır. Sipariş ve teslimatla ilgili sorularınız için iletişim kanallarımızdan bize ulaşabilirsiniz."}'::json,
        true,
        6,
        now(),
        now()
    ),
    (
        gen_random_uuid(),
        'on-bilgilendirme-formu',
        '{"tr":"Ön Bilgilendirme Formu"}'::json,
        '{"tr":"Siparişinizi onaylamadan önce ürünün temel özelliklerini, toplam satış bedelini, ödeme yöntemini, teslimat koşullarını ve cayma hakkına ilişkin bilgileri kontrol ediniz.\n\nSipariş özeti ödeme öncesinde gösterilir ve siparişin tamamlanması bu bilgilerin kabul edildiği anlamına gelir."}'::json,
        true,
        7,
        now(),
        now()
    ),
    (
        gen_random_uuid(),
        'iptal-iade-politikasi',
        '{"tr":"İptal ve Geri Ödeme Politikası"}'::json,
        '{"tr":"Kargoya verilmemiş siparişler için iletişim kanallarımızdan iptal talebi oluşturabilirsiniz. Kargoya verilmiş siparişlerde iade süreci uygulanır.\n\nOnaylanan geri ödemeler, ödemenin yapıldığı yönteme uygun olarak işleme alınır. Banka ve ödeme kuruluşlarının işlem süreleri değişiklik gösterebilir."}'::json,
        true,
        8,
        now(),
        now()
    ),
    (
        gen_random_uuid(),
        'cerez-politikasi',
        '{"tr":"Çerez Politikası"}'::json,
        '{"tr":"Sitemizde sepetinizi korumak, dil tercihinizi hatırlamak ve temel site işlevlerini sağlamak amacıyla gerekli çerezler kullanılabilir.\n\nTarayıcı ayarlarınızdan çerezleri silebilir veya engelleyebilirsiniz. Gerekli çerezlerin engellenmesi bazı özelliklerin düzgün çalışmamasına neden olabilir."}'::json,
        true,
        9,
        now(),
        now()
    ),
    (
        gen_random_uuid(),
        'kullanim-kosullari',
        '{"tr":"Kullanım Koşulları"}'::json,
        '{"tr":"Bu internet sitesini kullanarak yayımlanan kullanım koşullarını kabul etmiş olursunuz. Sitedeki ürün, fiyat ve stok bilgileri güncel tutulmaya çalışılır; gerekli durumlarda içeriklerde değişiklik yapılabilir.\n\nSite içeriği izinsiz olarak kopyalanamaz veya ticari amaçla kullanılamaz."}'::json,
        true,
        10,
        now(),
        now()
    )
on conflict (slug) do nothing;

insert into site_links (
    id,
    location,
    link_key,
    label,
    url,
    sort_order,
    active,
    created_at
)
values
    (gen_random_uuid(), 'footer', 'about', '{"tr":"Hakkımızda"}'::json, '/tr/hakkimizda', 1, true, now()),
    (gen_random_uuid(), 'footer', 'distance_sale', '{"tr":"Mesafeli Satış Sözleşmesi"}'::json, '/tr/mesafeli-satis-sozlesmesi', 2, true, now()),
    (gen_random_uuid(), 'footer', 'pre_information', '{"tr":"Ön Bilgilendirme Formu"}'::json, '/tr/on-bilgilendirme-formu', 3, true, now()),
    (gen_random_uuid(), 'footer', 'privacy', '{"tr":"Gizlilik Politikası"}'::json, '/tr/gizlilik-politikasi', 4, true, now()),
    (gen_random_uuid(), 'footer', 'delivery', '{"tr":"Teslimat ve Kargo Politikası"}'::json, '/tr/teslimat-kargo', 5, true, now()),
    (gen_random_uuid(), 'footer', 'refund_policy', '{"tr":"İptal ve Geri Ödeme Politikası"}'::json, '/tr/iptal-iade-politikasi', 6, true, now()),
    (gen_random_uuid(), 'footer', 'cookie_policy', '{"tr":"Çerez Politikası"}'::json, '/tr/cerez-politikasi', 7, true, now()),
    (gen_random_uuid(), 'footer', 'terms', '{"tr":"Kullanım Koşulları"}'::json, '/tr/kullanim-kosullari', 8, true, now()),
    (gen_random_uuid(), 'footer', 'return', '{"tr":"İade ve Değişim Koşulları"}'::json, '/tr/iade-degisim', 9, true, now()),
    (gen_random_uuid(), 'footer', 'kvkk', '{"tr":"KVKK Aydınlatma Metni"}'::json, '/tr/kvkk', 10, true, now()),
    (gen_random_uuid(), 'footer', 'whatsapp', '{"tr":"WhatsApp"}'::json, 'https://wa.me/905555555555', 11, true, now()),
    (gen_random_uuid(), 'footer', 'instagram', '{"tr":"Instagram"}'::json, 'https://instagram.com', 12, true, now())
on conflict (location, link_key) do update
set
    label = (
        coalesce(site_links.label::jsonb, '{}'::jsonb)
        || excluded.label::jsonb
    )::json,
    url = excluded.url,
    sort_order = excluded.sort_order,
    active = true;

commit;
