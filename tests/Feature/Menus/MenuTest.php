<?php

use App\Filament\Resources\Menus\MenuResource;
use App\Filament\Resources\Menus\Pages\EditMenu;
use App\Filament\Resources\Menus\Pages\ListMenus;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\User;
use Livewire\Livewire;

function menu(string $location): Menu
{
    return Menu::where('location', $location)->sole();
}

it('ships the five default menus', function () {
    expect(Menu::pluck('location')->sort()->values()->all())->toBe(['footer_1', 'footer_2', 'footer_3', 'header', 'legal'])
        ->and(collect(Menu::tree('header'))->pluck('label')->all())->toBe(['Home', 'All Tutorials', 'Blog', 'Projects']);
});

it('nests children and hides disabled items', function () {
    $menu = menu('header');
    $parent = $menu->items()->create(['label' => 'Resources', 'type' => 'url', 'url' => '/resources', 'sort' => 10]);
    MenuItem::create(['parent_id' => $parent->id, 'label' => 'Cheatsheets', 'type' => 'url', 'url' => 'https://example.com/cheats', 'sort' => 1]);
    MenuItem::create(['parent_id' => $parent->id, 'label' => 'Hidden', 'type' => 'url', 'url' => '/hidden', 'sort' => 2, 'is_enabled' => false]);

    $resources = collect(Menu::tree('header'))->firstWhere('label', 'Resources');

    expect($resources['href'])->toBe(url('/resources'))
        ->and(collect($resources['children'])->pluck('label')->all())->toBe(['Cheatsheets'])
        ->and($resources['children'][0]['href'])->toBe('https://example.com/cheats');
});

it('gives children the menu of their parent', function () {
    $parent = menu('footer_3')->items()->create(['label' => 'Parent', 'type' => 'url', 'url' => '/p']);
    $child = MenuItem::create(['parent_id' => $parent->id, 'label' => 'Child', 'type' => 'url', 'url' => '/c']);

    expect($child->menu_id)->toBe($parent->menu_id);
});

it('links content by id, so the menu follows slug changes', function () {
    $page = Page::factory()->create(['title' => 'About']);
    menu('header')->items()->create(['label' => 'About', 'type' => 'page', 'linkable_id' => $page->id, 'sort' => 9]);

    expect(collect(Menu::tree('header'))->firstWhere('label', 'About')['href'])->toBe(url('/about'));

    $page->update(['slug' => 'about-me']);

    expect(collect(Menu::tree('header'))->firstWhere('label', 'About')['href'])->toBe(url('/about-me'));
});

it('drops links to unpublished or deleted content and flags them in the admin', function () {
    $page = Page::factory()->create(['title' => 'About']);
    $item = menu('header')->items()->create(['label' => 'About', 'type' => 'page', 'linkable_id' => $page->id]);

    $page->update(['is_published' => false]);

    expect(collect(Menu::tree('header'))->pluck('label'))->not->toContain('About')
        ->and($item->fresh()->isBroken())->toBeTrue();

    $page->delete();
    expect(collect(Menu::tree('header'))->pluck('label'))->not->toContain('About');
});

it('refreshes the cached menu when an item changes', function () {
    Menu::tree('header'); // warm the cache
    menu('header')->items()->create(['label' => 'Fresh', 'type' => 'route', 'route' => 'contact', 'sort' => 99]);

    expect(collect(Menu::tree('header'))->pluck('label'))->toContain('Fresh');
});

it('renders the header, footer columns and legal links from menus', function () {
    menu('footer_3')->update(['name' => 'Community']);
    menu('footer_3')->items()->create(['label' => 'GitHub Discussions', 'type' => 'url', 'url' => 'https://github.com/Asyraf-wa', 'target' => '_blank']);

    $this->get('/')
        ->assertOk()
        ->assertSeeInOrder(['Home', 'All Tutorials', 'Blog', 'Projects'])
        ->assertSee('Community')
        ->assertSee('GitHub Discussions')
        ->assertSee('Privacy Policy')
        ->assertSee('Terms of Service');
});

it('lets the admin edit menus but not create or delete them', function () {
    config(['ctp.admin_emails' => ['owner@example.com']]);
    $this->actingAs(User::factory()->create(['email' => 'owner@example.com']));

    Livewire::test(ListMenus::class)->assertOk()->assertCanSeeTableRecords(Menu::all());
    expect(MenuResource::canCreate())->toBeFalse();

    Livewire::test(EditMenu::class, ['record' => menu('footer_1')->getRouteKey()])
        ->assertOk()
        ->fillForm(['name' => 'Learn'])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(menu('footer_1')->name)->toBe('Learn');
});
