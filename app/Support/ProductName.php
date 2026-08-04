<?php

namespace App\Support;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

/**
 * Mükerrer ürün girişini engelleyen ad anahtarı.
 *
 * Ürünün kimliği koddur; ad anahtarı ise "aynı ürünü ikinci kez girme"yi
 * önleyen veri hijyeni kuralıdır. Anahtar products.name_key kolonunda tutulur
 * ve benzersizdir, böylece panel dışından (toplu içe aktarma, eşzamanlı kayıt)
 * gelen mükerrer kayıtlar da veritabanı seviyesinde reddedilir.
 */
class ProductName
{
    /**
     * "BEYAZ  ATLET.", "beyaz atlet" ve "Beyaz Atlet" aynı anahtara iner.
     */
    public static function key(mixed $name): string
    {
        return Str::slug(trim((string) (is_array($name) ? ($name['tr'] ?? '') : $name)));
    }

    /**
     * Aynı ada sahip ürün (varsa). Yayında olmayanlar da kontrole dahildir;
     * aksi hâlde pasif ürünün ikizi oluşurdu.
     */
    public static function duplicate(mixed $name, ?string $ignoreId = null): ?Product
    {
        $key = self::key($name);

        if ($key === '') {
            return null;
        }

        return Product::query()
            ->where('name_key', $key)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->first();
    }

    /**
     * Muhtemel yazım hatasını bulur. Bu kontrol kayıt engellemez; yalnızca
     * "Ktenli" → "Ketenli" gibi çok yakın tek sonucu kullanıcıya önerir.
     * Kısa adlarda yanlış eşleşmeyi azaltmak için en az beş karakter aranır.
     */
    public static function closestTypo(mixed $name, ?string $ignoreId = null): ?Product
    {
        $key = self::key($name);
        $length = strlen($key);

        if ($length < 5) {
            return null;
        }

        $compact = str_replace('-', '', $key);
        $gramSize = 2;
        $grams = [];

        for ($index = 0; $index <= strlen($compact) - $gramSize; $index++) {
            $grams[] = substr($compact, $index, $gramSize);
        }

        $grams = array_values(array_unique($grams));

        if ($grams === []) {
            return null;
        }

        // Önce küçük bir aday kümesi çıkarılır; tüm ürün adlarını her alan
        // çıkışında PHP'ye taşımamak için ortak ikili parçalar SQL'de süzülür.
        $candidates = Product::query()
            ->select(['id', 'name', 'name_key', 'code', 'active'])
            ->where('name_key', '!=', $key)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where(function (Builder $query) use ($grams): void {
                foreach ($grams as $gram) {
                    $query->orWhere('name_key', 'like', '%'.$gram.'%');
                }
            })
            ->limit(250)
            ->get();

        $maximumDistance = $length >= 12 ? 2 : 1;

        return $candidates
            ->map(function (Product $product) use ($key, $length, $maximumDistance): array {
                $candidateKey = (string) $product->name_key;
                $candidateLength = strlen($candidateKey);

                if ($candidateLength < 5 || abs($candidateLength - $length) > $maximumDistance) {
                    return ['product' => $product, 'distance' => PHP_INT_MAX, 'length_delta' => PHP_INT_MAX];
                }

                return [
                    'product' => $product,
                    'distance' => self::editDistance($key, $candidateKey),
                    'length_delta' => abs($candidateLength - $length),
                ];
            })
            ->filter(fn (array $match): bool => $match['distance'] <= $maximumDistance)
            ->sortBy(fn (array $match): string => sprintf(
                '%04d-%04d-%s',
                $match['distance'],
                $match['length_delta'],
                $match['product']->name_key,
            ))
            ->value('product');
    }

    /**
     * Levenshtein'e ek olarak yan yana iki harfin yer değiştirmesini tek hata
     * sayan Optimal String Alignment uzaklığı ("ketenli" / "keetnli").
     */
    private static function editDistance(string $left, string $right): int
    {
        $leftLength = strlen($left);
        $rightLength = strlen($right);
        $matrix = [];

        for ($row = 0; $row <= $leftLength; $row++) {
            $matrix[$row] = [$row];
        }

        for ($column = 0; $column <= $rightLength; $column++) {
            $matrix[0][$column] = $column;
        }

        for ($row = 1; $row <= $leftLength; $row++) {
            for ($column = 1; $column <= $rightLength; $column++) {
                $cost = $left[$row - 1] === $right[$column - 1] ? 0 : 1;
                $matrix[$row][$column] = min(
                    $matrix[$row - 1][$column] + 1,
                    $matrix[$row][$column - 1] + 1,
                    $matrix[$row - 1][$column - 1] + $cost,
                );

                if (
                    $row > 1
                    && $column > 1
                    && $left[$row - 1] === $right[$column - 2]
                    && $left[$row - 2] === $right[$column - 1]
                ) {
                    $matrix[$row][$column] = min(
                        $matrix[$row][$column],
                        $matrix[$row - 2][$column - 2] + 1,
                    );
                }
            }
        }

        return $matrix[$leftLength][$rightLength];
    }

    /**
     * Birebir aynı olmayan ama karıştırılabilecek ürünler: "Beyaz Atlet" için
     * "Beyaz Atlet Bayan". Engellemez, panelde uyarı olarak gösterilir.
     *
     * @return array<int, Product>
     */
    public static function similar(mixed $name, ?string $ignoreId = null, int $limit = 5): array
    {
        $key = self::key($name);

        if ($key === '') {
            return [];
        }

        // Anahtarın anlamlı kelimeleri; "ve", "6" gibi kısa parçalar elenir.
        $words = array_filter(explode('-', $key), fn (string $word): bool => mb_strlen($word) >= 3);

        if ($words === []) {
            return [];
        }

        return Product::query()
            ->where('name_key', '!=', $key)
            ->when($ignoreId, fn (Builder $query) => $query->whereKeyNot($ignoreId))
            ->where(function (Builder $query) use ($words): void {
                foreach ($words as $word) {
                    $query->orWhere('name_key', 'like', '%'.$word.'%');
                }
            })
            ->orderBy('name_key')
            ->limit($limit)
            ->get()
            ->all();
    }
}
