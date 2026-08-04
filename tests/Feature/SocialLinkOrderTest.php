<?php

namespace Tests\Feature;

use App\Support\Storefront;
use Tests\TestCase;

class SocialLinkOrderTest extends TestCase
{
    public function test_social_links_follow_the_configured_order(): void
    {
        $links = Storefront::socialLinks([
            'socialLinks' => [
                ['platform' => 'youtube', 'label' => '', 'url' => 'https://youtube.com/@magaza'],
                ['platform' => 'instagram', 'label' => 'Instagram TR', 'url' => 'https://instagram.com/magaza'],
                ['platform' => 'facebook', 'label' => '', 'url' => ''],
            ],
        ]);

        $this->assertSame(
            [['YouTube', 'https://youtube.com/@magaza'], ['Instagram TR', 'https://instagram.com/magaza']],
            array_map(fn (array $link): array => [$link['label'], $link['url']], $links),
        );
    }

    public function test_links_without_a_url_are_skipped(): void
    {
        $links = Storefront::socialLinks(['socialLinks' => [
            ['platform' => 'facebook', 'label' => '', 'url' => '   '],
        ]]);

        $this->assertSame([], $links);
    }
}
