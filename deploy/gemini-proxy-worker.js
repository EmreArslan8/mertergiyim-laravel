/**
 * Gemini vekil sunucusu (Cloudflare Worker).
 *
 * Neden: alwaysdata'nın paylaşımlı barındırma IP'sinden yapılan isteklere
 * Gemini "User location is not supported for the API use" hatası döndürüyor;
 * aynı istek geliştirici makinesinden sorunsuz çalışıyor. Araya bu vekil
 * konunca Google isteği Cloudflare çıkışlı görüyor ve engel kalkıyor.
 *
 * Kurulum: Cloudflare → Workers & Pages → Create Worker → bu dosyayı yapıştır
 * → Deploy. Ardından sunucudaki .env'e:
 *
 *     GEMINI_BASE_URL=https://<worker-adi>.<hesap>.workers.dev
 *
 * API anahtarı burada tutulmaz: uygulama anahtarı `x-goog-api-key` başlığıyla
 * gönderir, vekil onu olduğu gibi iletir. Yani vekilin adresi bilinse bile
 * bizim anahtarımız sızmaz.
 */

const UPSTREAM = 'https://generativelanguage.googleapis.com';

// Yalnızca modele istek atan yol geçer; vekil genel amaçlı bir açık kapı olmasın.
const ALLOWED_PATH = /^\/v1beta\/models\/[A-Za-z0-9.\-]+:generateContent$/;

export default {
  async fetch(request) {
    const url = new URL(request.url);

    if (request.method !== 'POST') {
      return new Response('Method not allowed', { status: 405 });
    }

    if (!ALLOWED_PATH.test(url.pathname)) {
      return new Response('Not found', { status: 404 });
    }

    const target = new URL(url.pathname + url.search, UPSTREAM);

    const upstreamRequest = new Request(target, {
      method: 'POST',
      headers: request.headers,
      body: request.body,
    });

    const response = await fetch(upstreamRequest);

    // Hata gövdesi uygulamada loglanıyor; olduğu gibi geri ver.
    return new Response(response.body, {
      status: response.status,
      headers: response.headers,
    });
  },
};
