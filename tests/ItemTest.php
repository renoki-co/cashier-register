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
            ->description('Some nice stickers.')
            ->data(['sticky' => 'yes']);

        $this->assertCount(1, Saas::getItems());

        $item = Saas::getItem('sticker');

        $this->assertEquals('sticker', $item->getId());

        $this->assertEquals(['sticky' => 'yes'], $item->getData());
    }

    public function test_build_items_with_subitems()
    {
        Saas::clearItems();

        $item = Saas::item('Sticker Pack', 'sticker-pack')
            ->description('Some sticker pack.')
            ->subitems([
                Saas::item('Laravel Sticker', 'laravel-sticker')
                    ->price(30, 'USD'),
            ]);

        $this->assertCount(1, $item->getSubitems());

        $this->assertTrue(
            is_array($item->toArray())
        );

        $this->assertTrue(
            is_array($item->toArray()['subitems'])
        );
    }
}
