# Filament Media Manager

[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/slimani-dev/filament-media-manager/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/slimani-dev/filament-media-manager/actions/workflows/run-tests.yml)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/slimani-dev/filament-media-manager/phpstan.yml?branch=main&label=phpstan&style=flat-square)](https://github.com/slimani-dev/filament-media-manager/actions/workflows/phpstan.yml)
[![GitHub Workflow Status](https://img.shields.io/github/actions/workflow/status/slimani-dev/filament-media-manager/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/slimani-dev/filament-media-manager/actions/workflows/fix-php-code-style-issues.yml)
[![Total Downloads](https://img.shields.io/packagist/dt/slimani/filament-media-manager.svg?style=flat-square)](https://packagist.org/packages/slimani/filament-media-manager)
[![License](https://img.shields.io/packagist/l/slimani/filament-media-manager.svg?style=flat-square)](https://github.com/slimani-dev/filament-media-manager/blob/main/LICENSE)

A comprehensive media manager plugin for Filament v5.

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

## Features

- **Folder-based organization**: Organize your media into hierarchical folders.
- **Taggable media**: Add tags to your files for easier searching and filtering.
- **Support for multiple disks**: Configure which disk to use for storage per field or globally.
- **Native Filament integration**: Built specifically for Filament v5 with support for forms, tables, and actions.
- **Smooth UI**: Modern, responsive media browser with search and filtering capabilities.

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

- [Moh](https://github.com/moh)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.
