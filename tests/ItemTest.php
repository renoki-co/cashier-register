<?php

namespace RenokiCo\CashierRegister\Test;

use RenokiCo\CashierRegister\Saas;

class ItemTest extends TestCase
{
    public function test_build_items()
    {
        Saas::clearItems();

        Saas::item('Sticker', 'sticker')
            ->price(30, 'USD')
            ->description('Some nice stickers.');

        $this->assertCount(1, Saas::getItems());

        $item = Saas::getItem('sticker');

        $this->assertEquals('sticker', $item->getId());
    }

    public function test_build_items_with_subitems()
    {
        Saas::clearItems();

        $plan = Saas::item('Sticker Pack', 'sticker-pack')
            ->description('Some sticker pack.')
            ->subitems([
                Saas::item('Laravel Sticker', 'laravel-sticker')
                    ->price(30, 'USD'),
            ]);

        $this->assertCount(1, $plan->getSubitems());
    }
}
