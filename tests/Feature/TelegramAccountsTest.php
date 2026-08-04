<?php

namespace Tests\Feature;

use App\Filament\Resources\TelegramAccounts\Pages\ListTelegramAccounts;
use App\Models\TelegramAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Telegram hesapları: numara alanı ve panel ekranı.
 *
 * Numara koda gömülmüyor; başka bir kurulum kendi numarasını girip aynı kodu
 * çalıştırabilsin diye kayıt olarak tutuluyor.
 */
class TelegramAccountsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    /**
     * @return array<string, array{0: string, 1: string}>
     */
    public static function phoneProvider(): array
    {
        return [
            'basinda sifir' => ['05060603884', '+905060603884'],
            'bosluklu' => ['0506 060 38 84', '+905060603884'],
            'ulke kodu yazilmamis' => ['5060603884', '+905060603884'],
            'artili tam numara' => ['+90 506 060 38 84', '+905060603884'],
            'cift sifirli uluslararasi' => ['00905060603884', '+905060603884'],
            'yabanci numara' => ['+1 (202) 555-0143', '+12025550143'],
        ];
    }

    #[DataProvider('phoneProvider')]
    public function test_numara_tek_bicime_indirgenir(string $input, string $expected): void
    {
        $this->assertSame($expected, TelegramAccount::normalizePhone($input));
    }

    public function test_kayit_sirasinda_numara_normallesir(): void
    {
        $account = TelegramAccount::create([
            'label' => 'Merter ana hat',
            'phone' => '0506 060 38 84',
            'api_id' => '123456',
            'api_hash' => 'gizli-anahtar',
        ]);

        $this->assertSame('+905060603884', $account->fresh()->phone);
    }

    public function test_api_hash_veritabaninda_acik_durmaz(): void
    {
        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'api_id' => '123456',
            'api_hash' => 'gizli-anahtar',
        ]);

        $stored = DB::table('telegram_accounts')->where('id', $account->getKey())->value('api_hash');

        $this->assertNotSame('gizli-anahtar', $stored);
        $this->assertSame('gizli-anahtar', $account->fresh()->api_hash);
    }

    public function test_numara_listede_maskeli_gosterilir(): void
    {
        $account = new TelegramAccount(['phone' => '+905060603884']);

        $masked = $account->maskedPhone();

        $this->assertStringStartsWith('+90506', $masked);
        $this->assertStringEndsWith('84', $masked);
        $this->assertStringNotContainsString('0603', $masked);
    }

    public function test_giris_yapilmamis_hesap_hazir_sayilmaz(): void
    {
        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'api_id' => '123456',
            'api_hash' => 'gizli-anahtar',
        ]);

        $this->assertFalse($account->isReady());
        $this->assertSame(0, TelegramAccount::query()->ready()->count());

        $account->update(['status' => 'active']);

        $this->assertTrue($account->fresh()->isReady());
        $this->assertSame(1, TelegramAccount::query()->ready()->count());
    }

    public function test_anahtar_girilmemisse_kurulumun_kimligi_kullanilir(): void
    {
        // api_id uygulamayı temsil ediyor, kullanıcıyı değil: numarasını giren
        // kişinin my.telegram.org'a uğraması gerekmiyor.
        config([
            'storefront.telegram_client.api_id' => '35455056',
            'storefront.telegram_client.api_hash' => 'kurulum-anahtari',
        ]);

        $account = TelegramAccount::create(['phone' => '+905060603884']);

        $this->assertSame('35455056', $account->resolvedApiId());
        $this->assertSame('kurulum-anahtari', $account->resolvedApiHash());
        $this->assertTrue($account->hasCredentials());
    }

    public function test_kayittaki_anahtar_kurulumunkini_gecersiz_kilar(): void
    {
        config([
            'storefront.telegram_client.api_id' => '35455056',
            'storefront.telegram_client.api_hash' => 'kurulum-anahtari',
        ]);

        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'api_id' => '999888',
            'api_hash' => 'hesaba-ozel',
        ]);

        $this->assertSame('999888', $account->resolvedApiId());
        $this->assertSame('hesaba-ozel', $account->resolvedApiHash());
    }

    public function test_hicbir_yerde_anahtar_yoksa_giris_denenemez(): void
    {
        config([
            'storefront.telegram_client.api_id' => null,
            'storefront.telegram_client.api_hash' => null,
        ]);

        $account = TelegramAccount::create(['phone' => '+905060603884']);

        $this->assertFalse($account->hasCredentials());
        $this->assertNull($account->resolvedApiId());
    }

    public function test_numara_degisince_oturum_dusurulur(): void
    {
        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'api_id' => '123456',
            'api_hash' => 'gizli-anahtar',
            'status' => 'active',
            'session_path' => 'telegram/sessions/eski',
        ]);

        Storage::disk('local')->put('telegram/sessions/eski/session.lock', 'x');

        $account->update(['phone' => '+905321112233']);

        $account->refresh();

        // Eski numaranın oturumu ve anahtarları yeni numarada kullanılamaz.
        $this->assertSame('new', $account->status);
        $this->assertNull($account->session_path);
        $this->assertNull($account->api_id);
        $this->assertNull($account->api_hash);
        $this->assertFalse($account->isReady());
        $this->assertFalse(Storage::disk('local')->exists('telegram/sessions/eski/session.lock'));
    }

    public function test_numarayla_birlikte_girilen_yeni_anahtarlar_korunur(): void
    {
        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'api_id' => '123456',
            'api_hash' => 'eski-anahtar',
            'status' => 'active',
        ]);

        // Panelde numara ve anahtarlar aynı kaydetmede değiştirilirse yeni
        // değerler silinmemeli.
        $account->update([
            'phone' => '+905321112233',
            'api_id' => '999888',
            'api_hash' => 'yeni-anahtar',
        ]);

        $account->refresh();

        $this->assertSame('999888', $account->api_id);
        $this->assertSame('yeni-anahtar', $account->api_hash);
        // Oturum yine de düşer: yeni numarayla tekrar giriş gerekiyor.
        $this->assertSame('new', $account->status);
    }

    public function test_numara_disindaki_degisiklik_oturumu_bozmaz(): void
    {
        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'api_id' => '123456',
            'api_hash' => 'gizli-anahtar',
            'status' => 'active',
            'session_path' => 'telegram/sessions/duran',
        ]);

        $account->update(['label' => 'Yeni ad']);

        $account->refresh();

        $this->assertSame('active', $account->status);
        $this->assertSame('telegram/sessions/duran', $account->session_path);
    }

    public function test_hesap_silinince_oturum_dosyasi_kalmaz(): void
    {
        $account = TelegramAccount::create([
            'phone' => '+905060603884',
            'api_id' => '123456',
            'api_hash' => 'gizli-anahtar',
            'status' => 'active',
            'session_path' => 'telegram/sessions/silinecek',
        ]);

        Storage::disk('local')->put('telegram/sessions/silinecek/session.lock', 'x');

        $account->delete();

        $this->assertFalse(Storage::disk('local')->exists('telegram/sessions/silinecek/session.lock'));
    }

    public function test_hesap_ekrani_kayitlari_listeler(): void
    {
        TelegramAccount::create([
            'label' => 'Merter ana hat',
            'phone' => '+905060603884',
            'api_id' => '123456',
            'api_hash' => 'gizli-anahtar',
        ]);

        Livewire::test(ListTelegramAccounts::class)
            ->assertOk()
            ->assertSee('Merter ana hat')
            ->assertSee('Giriş yapılmadı')
            // Tam numara listede görünmemeli.
            ->assertDontSee('+905060603884');
    }
}
