<?php

use Redot\Sidebar\Item;
use Redot\Sidebar\Sidebar;

it('builds sidebar items fluently', function () {
    $item = Item::make()
        ->title('Dashboard')
        ->icon('layout-dashboard')
        ->route('dashboard.index', ['locale' => 'en'])
        ->url('/dashboard')
        ->external(false)
        ->hidden(false);

    expect($item->title)->toBe('Dashboard')
        ->and($item->icon)->toBe('layout-dashboard')
        ->and($item->route)->toBe('dashboard.index')
        ->and($item->parameters)->toBe(['locale' => 'en'])
        ->and($item->url)->toBe('/dashboard')
        ->and($item->external)->toBeFalse()
        ->and($item->isHidden())->toBeFalse();
});

it('assigns parent items when children are configured', function () {
    $parent = Item::make()->title('Content');
    $child = Item::make()->title('Pages');

    $parent->children([$child]);

    expect($parent->children)->toHaveCount(1)
        ->and($child->parent)->toBe($parent);
});

it('filters hidden sidebar items', function () {
    $sidebar = Sidebar::make([
        Item::make()->title('Visible')->url('/visible'),
        Item::make()->title('Hidden')->url('/hidden')->hidden(true),
    ]);

    expect($sidebar->getItems())
        ->toHaveCount(1)
        ->and($sidebar->getItems()[0]->title)->toBe('Visible');
});
