<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Tests\TestCase;

class AdminRoleAuthorizationTest extends TestCase
{
    public function test_super_admin_can_access_every_management_area(): void
    {
        $admin = User::factory()->create([
            'role' => 'super_admin',
            'is_active' => true,
        ]);

        $this->actingAs($admin);

        foreach ([
            '/admin/orders',
            '/admin/products',
            '/admin/categories',
            '/admin/site-settings/storefront/edit',
            '/admin/admin-users',
        ] as $path) {
            $this->get($path)->assertOk();
        }
    }

    public function test_editor_can_manage_content_but_not_sales_or_system_settings(): void
    {
        $editor = User::factory()->create([
            'role' => 'editor',
            'is_active' => true,
        ]);

        $this->actingAs($editor);

        foreach ([
            '/admin/products',
            '/admin/products/create',
            '/admin/categories',
            '/admin/sizes',
            '/admin/colors',
            '/admin/hero-slides',
            '/admin/blog-posts',
            '/admin/content-pages',
            '/admin/media',
            '/admin/site-links',
            '/admin/homepage/storefront/edit',
        ] as $path) {
            $this->get($path)->assertOk();
        }

        foreach ([
            '/admin/orders',
            '/admin/contact-messages',
            '/admin/currencies',
            '/admin/languages',
            '/admin/site-settings/storefront/edit',
            '/admin/admin-users',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }
    }

    public function test_sales_staff_can_manage_orders_and_only_view_the_product_list(): void
    {
        $sales = User::factory()->create([
            'role' => 'order_manager',
            'is_active' => true,
        ]);
        $product = Product::query()->firstOrFail();
        $order = Order::query()->firstOrFail();

        $this->actingAs($sales);

        $this->get('/admin/orders')->assertOk();
        $this->get('/admin/orders/create')->assertOk();
        $this->get('/admin/orders/'.$order->getKey().'/edit')->assertOk();

        $this->get('/admin/products')
            ->assertOk()
            ->assertDontSee('Yeni ürün ekle')
            ->assertDontSee('toggleTableReordering', escape: false)
            ->assertDontSee('Düzenle');

        $this->get('/admin/products/create')->assertForbidden();
        $this->get('/admin/products/'.$product->getKey().'/edit')->assertForbidden();

        foreach ([
            '/admin/categories',
            '/admin/sizes',
            '/admin/colors',
            '/admin/hero-slides',
            '/admin/blog-posts',
            '/admin/content-pages',
            '/admin/media',
            '/admin/site-links',
            '/admin/homepage/storefront/edit',
            '/admin/site-settings/storefront/edit',
            '/admin/admin-users',
        ] as $path) {
            $this->get($path)->assertForbidden();
        }
    }
}
