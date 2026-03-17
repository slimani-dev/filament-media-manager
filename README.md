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

### Prepare Model

To use the media manager with your models, add the `InteractsWithMediaFiles` trait. This is required for relationships and picker functionality.

#### 1. Prepare your Migration

- **For single-file relationships** (like `avatar_id` or `cv_id`), you need to add the foreign key columns to your model's migration:

```php
Schema::table('users', function (Blueprint $table) {
    $table->foreignId('avatar_id')->nullable()->constrained('media_files')->nullOnDelete();
    $table->foreignId('cv_id')->nullable()->constrained('media_files')->nullOnDelete();
});
```

- **For multiple/polymorphic relationships** (using `$this->mediaFiles()`), **no migration is required**. These relationships utilize the built-in `media_attachments` table included in the package migrations.

#### 2. Prepare your Model

Add the trait and define your specific relationships. Don't forget to add the foreign keys to your `$fillable` array.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Slimani\MediaManager\Concerns\InteractsWithMediaFiles;

class User extends Model
{
    use InteractsWithMediaFiles;

    protected $fillable = [
        'name',
        'email',
        'avatar_id',
        'cv_id',
    ];

    public function avatar(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->mediaFile('avatar_id');
    }

    public function cv(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->mediaFile('cv_id');
    }

    /**
     * Optional: Define custom collection relationships
     */
    public function documents(): \Illuminate\Database\Eloquent\Relations\MorphToMany
    {
        return $this->mediaFiles('documents');
    }
}
```

### Media Picker Field

Use the `MediaPicker` field in your Filament forms. It uses the "Prepare Model" setup to handle file selection via a modal.

```php
use Slimani\MediaManager\Form\MediaPicker;
use Filament\Forms\Form;

public static function form(Form $form): Form
{
    return $form
        ->schema([
            // Single file selection with specific names (requires InteractsWithMediaFiles in Model)
            MediaPicker::make('avatar_id')
                ->relationship('avatar')
                ->label('User Avatar')
                ->required(),

            MediaPicker::make('cv_id')
                ->relationship('cv')
                ->label('User CV'),

            // Multiple file selection (requires MorphToMany relationship in Model)
            MediaPicker::make('documents')
                ->relationship('documents')
                ->multiple()
                ->label('User Documents'),
        ]);
}
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

#### Media Library Conversions

You can customize or add media conversions from your application's `AppServiceProvider` or a dedicated Service Provider. Default conversions (`thumb` and `preview`) are registered first, and your callback can override them or add new ones.

```php
use Slimani\MediaManager\Models\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

public function boot(): void
{
    File::registerMediaConversionsUsing(function (File $file, ?Media $media = null) {
        // Override default 'thumb' (300x300)
        $file->addMediaConversion('thumb')
            ->width(150)
            ->height(150)
            ->nonQueued();

        // Add a new custom conversion
        $file->addMediaConversion('square')
            ->width(500)
            ->height(500)
            ->nonQueued();
        
        // Default 'preview' (800x800) is kept if not overridden
    });
}
```

#### Using Conversions in Components

After defining your conversions, you can specify which one to use in your components using the `conversion()` method. This controls which conversion is used for the table thumbnail, infolist image, or form picker preview.

```php
use Slimani\MediaManager\Tables\Columns\MediaColumn;
use Slimani\MediaManager\Infolists\Components\MediaImageEntry;
use Slimani\MediaManager\Form\MediaPicker;

// In Tables
MediaColumn::make('avatar')
    ->conversion('thumb')

// In Infolists
MediaImageEntry::make('avatar')
    ->conversion('preview')

// In Forms (controls the picker's file preview)
MediaPicker::make('avatar_id')
    ->relationship('avatar')
    ->conversion('thumb')
```

All components default to the `thumb` conversion if none is specified.

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
