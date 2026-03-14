<?php

use Filament\Facades\Filament;
use Filament\Panel;
use Illuminate\Contracts\View\View;
use Slimani\MediaManager\MediaManagerPlugin;
use Slimani\MediaManager\Pages\MediaManager;
use Slimani\MediaManager\Tests\TestCase;

uses(TestCase::class);

it('can customize navigation and page components via plugin', function () {
    $headerView = Mockery::mock(View::class);
    $footerView = Mockery::mock(View::class);

    $plugin = MediaManagerPlugin::make()
        ->navigationGroup('System')
        ->navigationLabel('Assets')
        ->navigationIcon('heroicon-o-folder')
        ->navigationSort(5)
        ->shouldRegisterNavigation(false)
        ->headerWidgets(['header-widget'])
        ->footerWidgets(['footer-widget'])
        ->header($headerView)
        ->footer($footerView);

    $panel = Panel::make('custom')
        ->id('custom')
        ->plugin($plugin);

    Filament::registerPanel($panel);
    Filament::setCurrentPanel($panel);

    expect(MediaManager::getNavigationGroup())->toBe('System');
    expect(MediaManager::getNavigationLabel())->toBe('Assets');
    expect(MediaManager::getNavigationIcon())->toBe('heroicon-o-folder');
    expect(MediaManager::getNavigationSort())->toBe(5);
    expect(MediaManager::shouldRegisterNavigation())->toBeFalse();

    $page = new MediaManager;
    expect($page->getHeaderWidgets())->toBe(['header-widget']);
    expect($page->getFooterWidgets())->toBe(['footer-widget']);
    expect($page->getHeader())->toBe($headerView);
    expect($page->getFooter())->toBe($footerView);
});

it('has default values for all customizations', function () {
    $plugin = MediaManagerPlugin::make();

    $panel = Panel::make('default')
        ->id('default')
        ->plugin($plugin);

    Filament::registerPanel($panel);
    Filament::setCurrentPanel($panel);

    expect(MediaManager::getNavigationGroup())->toBe('Content');
    expect(MediaManager::getNavigationLabel())->toBe('Media Manager');
    expect(MediaManager::getNavigationIcon())->toBe('heroicon-o-document-text');
    expect(MediaManager::getNavigationSort())->toBeNull();
    expect(MediaManager::shouldRegisterNavigation())->toBeTrue();

    $page = new MediaManager;
    expect($page->getHeaderWidgets())->toBe([]);
    expect($page->getFooterWidgets())->toBe([]);
    expect($page->getHeader())->toBeNull();
    expect($page->getFooter())->toBeNull();
});
