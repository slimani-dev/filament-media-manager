# Filament Media Manager

[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/slimani-dev/filament-media-manager/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/slimani-dev/filament-media-manager/actions/workflows/run-tests.yml)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/slimani-dev/filament-media-manager/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/slimani-dev/filament-media-manager/actions/workflows/phpstan.yml)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/slimani-dev/filament-media-manager/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/slimani-dev/filament-media-manager/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/slimani/filament-media-manager.svg?style=flat-square)](https://packagist.org/packages/slimani/filament-media-manager)
[![License](https://img.shields.io/packagist/l/slimani/filament-media-manager.svg?style=flat-square)](https://github.com/slimani-dev/filament-media-manager/blob/main/LICENSE)

A comprehensive media manager plugin for Filament v5.

## Features

- **Folder-based organization**: Organize your media into hierarchical folders.
- **Native Filament integration**: Built specifically for Filament v5 with support for forms, tables, and actions.
- **Smooth UI**: Modern, responsive media browser with search and filtering capabilities.
- **Hierarchical Folder Navigation**: Browse and organize media using a powerful tree selection interface, thanks to `filament-select-tree`.
- **Taggable media**: Add tags to your files for easier searching and filtering.
- **Support for multiple disks**: Configure which disk to use for storage per field or globally.

## Installation

You can install the package via composer:

```bash
composer require slimani/filament-media-manager
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="media-manager-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="media-manager-config"
```

## Styling

If you are using a custom Filament theme, you should add the plugin's views to your Tailwind configuration.

Add the following `@source` directive to your `theme.css` file:

```css
@source '../../../../vendor/slimani/filament-media-manager/resources/**/*';
```

## Usage

### Plugin Registration

Register the plugin in your Panel Provider:

```php
use Slimani\MediaManager\MediaManagerPlugin;

public function panel(Panel $panel): Panel
{
    return $panel
        ->plugin(MediaManagerPlugin::make());
}
```

### Plugin Customization

You can customize the Media Manager directly in your Panel Provider:

```php
use Slimani\MediaManager\MediaManagerPlugin;

MediaManagerPlugin::make()
    ->navigationGroup('System')
    ->navigationLabel('Assets')
    ->navigationIcon('heroicon-o-folder')
    ->navigationSort(5)
    ->shouldRegisterNavigation(fn () => auth()->user()->isAdmin())
    ->headerWidgets([
        MyCustomWidget::class,
    ])
    ->footerWidgets([
        AnotherWidget::class,
    ])
    ->header(view('custom.header'))
    ->footer(view('custom.footer'))
```

### Media Picker Field

Use the `MediaPicker` field in your Filament forms to select files from the media library:

```php
use Slimani\MediaManager\Forms\Components\MediaPicker;

MediaPicker::make('avatar')
    ->label('User Avatar')
    ->disk('public')
    ->directory('avatars')
    ->acceptedFileTypes(['image/jpeg', 'image/png'])
    ->multiple() // Optional
```

### Media Column

Display media in your Filament tables using the `MediaColumn`:

```php
use Slimani\MediaManager\Tables\Columns\MediaColumn;

MediaColumn::make('avatar')
    ->circular()
    ->stacked()
```

### Media Image Entry

If you're using an Infolist, you can use the `MediaImageEntry` to display media:

```php
use Slimani\MediaManager\Infolists\Components\MediaImageEntry;

MediaImageEntry::make('avatar')
    ->label('User Avatar')
    ->circular() // Optional
    ->width(100) // Optional
```

This component also supports a preview action that opens the file in a slide-over.

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](SECURITY.md) on how to report security vulnerabilities.

## Credits

- [CodeWithDennis - Select Tree](https://filamentphp.com/plugins/codewithdennis-select-tree)
- [HugoMyb - Media Action](https://filamentphp.com/plugins/hugomyb-media-action)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
