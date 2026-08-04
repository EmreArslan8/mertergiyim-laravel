<?php

namespace Tests\Feature;

use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use App\Models\User;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Sipariş listesinde telefona göre arama.
 *
 * Telefon, müşteri sütununun altında görünmesine rağmen aranabilir değildi;
 * arama yalnızca ada bakıyordu. Numaralar ayraçlı kaydedildiği için
 * ("0535 123 45 67") ayraçsız yazılan aramanın da eşleşmesi gerekiyor.
 */
class OrderPhoneSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs(User::query()->firstOrFail());
    }

    private function order(string $name, string $phone): Order
    {
        return Order::query()->create([
            'order_number' => 'MG-TEST-'.strtoupper(uniqid()),
            'customer_name' => $name,
            'phone' => $phone,
            'address' => 'Merter, İstanbul',
            'status' => 'new',
            'total' => 100,
            'currency' => 'TRY',
        ]);
    }

    public static function searchProvider(): array
    {
        return [
            'ayraçlı numaranın bir parçası' => ['535'],
            'ayraçsız tam numara' => ['05351234567'],
            'ülke kodsuz ayraçsız' => ['5351234567'],
            'aradaki blok' => ['1234'],
        ];
    }

    #[DataProvider('searchProvider')]
    public function test_orders_are_found_by_phone_number(string $search): void
    {
        $match = $this->order('Ayşe Demir', '0535 123 45 67');
        $other = $this->order('Mehmet Kaya', '0542 987 65 43');

        Livewire::test(ListOrders::class)
            ->set('tableSearch', $search)
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }

    public function test_searching_by_name_still_works(): void
    {
        $match = $this->order('Ayşe Demir', '0535 123 45 67');
        $other = $this->order('Mehmet Kaya', '0542 987 65 43');

        Livewire::test(ListOrders::class)
            ->set('tableSearch', 'Ayşe')
            ->assertCanSeeTableRecords([$match])
            ->assertCanNotSeeTableRecords([$other]);
    }
}
