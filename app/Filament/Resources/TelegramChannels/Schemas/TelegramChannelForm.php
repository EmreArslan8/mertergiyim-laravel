<?php

namespace App\Filament\Resources\TelegramChannels\Schemas;

use App\Models\TelegramChannel;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class TelegramChannelForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->columns(1)
                    ->schema([
                        TextInput::make('username')
                            ->label('Kanal adresi')
                            ->required()
                            ->maxLength(120)
                            ->placeholder('@kanaladi')
                            // Kullanıcı '@kanaladi', 't.me/kanaladi' ya da tam
                            // adres yapıştırabiliyor; model hepsini sade
                            // kullanıcı adına indirger.
                            ->dehydrateStateUsing(fn (?string $state): string => TelegramChannel::normalizeUsername($state))
                            // Benzersizlik sadeleştirilmiş ada göre kontrol
                            // edilmeli: hazır unique kuralı ham girdiye bakıyor
                            // ve "@kanaladi" ile "kanaladi" farklı sanılıp
                            // veritabanı kısıtına takılıyordu (500 hatası).
                            ->rule(fn (?TelegramChannel $record) => function (string $attribute, mixed $value, callable $fail) use ($record): void {
                                $username = TelegramChannel::normalizeUsername((string) $value);

                                if ($username === '') {
                                    $fail('Kanal adresi okunamadı.');

                                    return;
                                }

                                $exists = TelegramChannel::query()
                                    ->where('username', $username)
                                    ->when($record, fn ($query) => $query->whereKeyNot($record->getKey()))
                                    ->exists();

                                if ($exists) {
                                    $fail('Bu kanal zaten ekli.');
                                }
                            })
                            ->helperText('@ ile ya da t.me bağlantısı olarak yapıştırabilirsiniz. Kanal herkese açık olmalı.'),

                        TextInput::make('title')
                            ->label('Görünen ad')
                            ->maxLength(120)
                            ->helperText('Boş bırakılırsa kanal adresi gösterilir.'),

                        Checkbox::make('active')
                            ->label('Taramalarda kullanılsın')
                            ->default(true),
                    ]),
            ]);
    }
}
