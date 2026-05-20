<?php

use Redot\Sidebar\Item;
use Redot\Sidebar\Sidebar;

it('assigns the parent item to each child when children are configured', function () {
    $parent = Item::make()->title('Content');
    $first = Item::make()->title('Pages');
    $second = Item::make()->title('Posts');

    $parent->children([$first, $second]);

    expect($first->parent)->toBe($parent)
        ->and($second->parent)->toBe($parent)
        ->and($parent->children)->toHaveCount(2);
});

it('hides items that are flagged hidden via a boolean', function () {
    $sidebar = Sidebar::make([
        Item::make()->title('Visible')->url('/visible'),
        Item::make()->title('Hidden')->url('/hidden')->hidden(true),
    ]);

    $titles = array_map(fn (Item $item) => $item->title, $sidebar->getItems());

    expect($titles)->toBe(['Visible']);
});

it('hides items whose hidden closure resolves to true', function () {
    $sidebar = Sidebar::make([
        Item::make()->title('Always shown')->url('/visible')->hidden(fn () => false),
        Item::make()->title('Conditionally hidden')->url('/hidden')->hidden(fn () => true),
    ]);

    $titles = array_map(fn (Item $item) => $item->title, $sidebar->getItems());

    expect($titles)->toBe(['Always shown']);
});

it('removes a parent item once every one of its children has been filtered out', function () {
    $parent = Item::make()->title('Group')->url('/group')->children([
        Item::make()->title('Hidden 1')->url('/h1')->hidden(true),
        Item::make()->title('Hidden 2')->url('/h2')->hidden(true),
    ]);

    $sibling = Item::make()->title('Survivor')->url('/keep');

    $sidebar = Sidebar::make([$parent, $sibling]);

    $titles = array_map(fn (Item $item) => $item->title, $sidebar->getItems());

    expect($titles)->toBe(['Survivor']);
});

it('keeps a parent when at least one child remains visible and drops only the hidden ones', function () {
    $parent = Item::make()->title('Group')->url('/group')->children([
        Item::make()->title('Visible Child')->url('/visible-child'),
        Item::make()->title('Hidden Child')->url('/hidden-child')->hidden(true),
    ]);

    $items = Sidebar::make([$parent])->getItems();

    expect($items)->toHaveCount(1);

    $childTitles = array_map(fn (Item $child) => $child->title, $items[0]->children);
    expect($childTitles)->toBe(['Visible Child']);
});

it('falls back to # as the url when neither url nor route is provided', function () {
    $sidebar = Sidebar::make([
        Item::make()->title('No href'),
    ]);

    expect($sidebar->getItems()[0]->url)->toBe('#');
});
