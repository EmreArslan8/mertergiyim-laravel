<?php

namespace App\Console\Commands;

use App\Models\TelegramAccount;
use App\Services\Telegram\TelegramClientFactory;
use Illuminate\Console\Command;
use Throwable;

/**
 * Bir Telegram hesabına girer ve oturumu diske kaydeder.
 *
 * Bir kez çalıştırılır: kod SMS ile numaraya gelir, oturum dosyası kaydedilir
 * ve sonraki taramalar tekrar kod istemez. Web isteğinden değil komut
 * satırından yapılıyor çünkü adım adım girdi (kod, iki adımlı doğrulama
 * parolası) gerekiyor ve süre sınırı olmamalı.
 */
class TelegramLoginCommand extends Command
{
    protected $signature = 'telegram:login
        {--phone= : Giriş yapılacak numara (birden çok hesap varsa)}
        {--logout : Oturumu kapat ve kaydı giriş yapılmamışa çevir}';

    protected $description = 'Telegram hesabına giriş yapar, oturumu kaydeder.';

    public function handle(TelegramClientFactory $factory): int
    {
        $account = $this->resolveAccount();

        if (! $account instanceof TelegramAccount) {
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('Hesap: <options=bold>'.$account->label().'</> ('.$account->phone.')');

        try {
            $client = $factory->make($account);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('logout')) {
            return $this->logout($account, $client);
        }

        try {
            // Zaten girilmişse tekrar kod istemeye gerek yok.
            $self = $client->getSelf();

            if (is_array($self)) {
                $this->markActive($account);
                $this->info('Bu hesaba zaten giriş yapılmış: '.$this->describe($self));

                return self::SUCCESS;
            }

            $this->line('Numaraya kod gönderiliyor...');
            $client->phoneLogin($account->phone);

            $account->update(['status' => 'awaiting_code']);

            $code = trim((string) $this->ask('Telegram\'dan gelen kod'));

            if ($code === '') {
                $this->error('Kod girilmedi.');
                $account->update(['status' => 'failed', 'last_error' => 'Kod girilmedi.']);

                return self::FAILURE;
            }

            $result = $client->completePhoneLogin($code);

            // İki adımlı doğrulama açıksa Telegram parola istiyor.
            if (($result['_'] ?? null) === 'account.password') {
                $password = (string) $this->secret('İki adımlı doğrulama parolası');

                $result = $client->complete2faLogin($password);
            }

            // Kayıtsız numaralarda Telegram ad soyad isteyip hesap açtırıyor;
            // burada hesap açmıyoruz, var olan hesaba giriyoruz.
            if (($result['_'] ?? null) === 'account.needSignup') {
                throw new \RuntimeException('Bu numarada Telegram hesabı yok. Önce resmî uygulamadan hesabı açın.');
            }

            $self = $client->getSelf();

            if (! is_array($self)) {
                throw new \RuntimeException('Giriş tamamlanamadı.');
            }

            $this->markActive($account);

            $this->newLine();
            $this->info('Giriş tamam: '.$this->describe($self));
            $this->line('Oturum kaydedildi, bir daha kod istenmeyecek.');

            return self::SUCCESS;
        } catch (Throwable $e) {
            $account->update(['status' => 'failed', 'last_error' => $e->getMessage()]);

            $this->newLine();
            $this->error('Giriş başarısız: '.$e->getMessage());
            $this->line('Ayrıntı: storage/logs/telegram-mtproto.log');

            return self::FAILURE;
        }
    }

    private function resolveAccount(): ?TelegramAccount
    {
        $accounts = TelegramAccount::query()->orderBy('sort_order')->get();

        if ($accounts->isEmpty()) {
            $this->error('Tanımlı hesap yok. Panel → Telegram Hesapları\'ndan numara ekleyin.');

            return null;
        }

        if ($phone = $this->option('phone')) {
            $normalized = TelegramAccount::normalizePhone((string) $phone);
            $account = $accounts->firstWhere('phone', $normalized);

            if (! $account instanceof TelegramAccount) {
                $this->error('Bu numarayla kayıtlı hesap yok: '.$normalized);

                return null;
            }

            return $account;
        }

        if ($accounts->count() === 1) {
            return $accounts->first();
        }

        $choice = $this->choice(
            'Hangi hesap?',
            $accounts->mapWithKeys(fn (TelegramAccount $a): array => [$a->phone => $a->label().' ('.$a->phone.')'])->all(),
        );

        return $accounts->firstWhere('phone', $choice);
    }

    private function logout(TelegramAccount $account, mixed $client): int
    {
        try {
            $client->logout();
        } catch (Throwable $e) {
            // Oturum zaten geçersizse çıkış hata verebiliyor; kaydı yine de
            // temizliyoruz, aksi halde panelde "Bağlı" görünmeye devam eder.
            $this->warn('Çıkış sırasında uyarı: '.$e->getMessage());
        }

        $account->update(['status' => 'new', 'last_error' => null]);

        $this->info('Oturum kapatıldı.');

        return self::SUCCESS;
    }

    private function markActive(TelegramAccount $account): void
    {
        $account->update([
            'status' => 'active',
            'session_path' => $account->sessionPath(),
            'last_error' => null,
            'last_used_at' => now(),
        ]);
    }

    /** @param  array<string, mixed>  $self */
    private function describe(array $self): string
    {
        $name = trim(($self['first_name'] ?? '').' '.($self['last_name'] ?? ''));
        $username = isset($self['username']) ? ' @'.$self['username'] : '';

        return ($name !== '' ? $name : 'isimsiz').$username;
    }
}
