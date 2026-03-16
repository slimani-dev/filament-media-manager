<?php

namespace Slimani\MediaManager\Tests;

use BladeUI\Heroicons\BladeHeroiconsServiceProvider;
use BladeUI\Icons\BladeIconsServiceProvider;
use Filament\Actions\ActionsServiceProvider;
use Filament\FilamentServiceProvider;
use Filament\Forms\FormsServiceProvider;
use Filament\Infolists\InfolistsServiceProvider;
use Filament\Notifications\NotificationsServiceProvider;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Schemas\SchemasServiceProvider;
use Filament\Support\SupportServiceProvider;
use Filament\Widgets\WidgetsServiceProvider;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Livewire\LivewireServiceProvider;
use Livewire\Mechanisms\DataStore;
use Orchestra\Testbench\TestCase as Orchestra;
use Slimani\MediaManager\MediaManagerPlugin;
use Slimani\MediaManager\MediaManagerServiceProvider;
use Slimani\MediaManager\Tests\Models\User;
use Spatie\MediaLibrary\MediaLibraryServiceProvider;

class TestPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('test')
            ->plugin(MediaManagerPlugin::make());
    }
}

class TestCase extends Orchestra
{
    use RefreshDatabase;

    protected function getPackageProviders($app): array
    {
        return [
            LivewireServiceProvider::class,
            FilamentServiceProvider::class,
            SupportServiceProvider::class,
            ActionsServiceProvider::class,
            FormsServiceProvider::class,
            InfolistsServiceProvider::class,
            NotificationsServiceProvider::class,
            BladeIconsServiceProvider::class,
            BladeHeroiconsServiceProvider::class,
            MediaLibraryServiceProvider::class,
            MediaManagerServiceProvider::class,
            SchemasServiceProvider::class,
            WidgetsServiceProvider::class,
            TestPanelProvider::class,
        ];
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Schema::create('users', function ($table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('email')->unique()->nullable();
            $table->string('password')->nullable();
            $table->unsignedBigInteger('avatar_id')->nullable();
            $table->timestamps();
        });

        Schema::create('user_documents', function ($table) {
            $table->id();
            $table->foreignId('user_id');
            $table->foreignId('file_id');
            $table->string('collection')->nullable();
        });

        Schema::create('media_folders', function ($table) {
            $table->id();
            $table->string('name');
            $table->foreignId('parent_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('media_files', function ($table) {
            $table->id();
            $table->foreignId('uploaded_by_user_id')->nullable();
            $table->foreignId('folder_id')->nullable()->constrained('media_folders')->nullOnDelete();
            $table->string('name');
            $table->string('caption')->nullable();
            $table->string('alt_text')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->string('extension')->nullable();
            $table->string('mime_type')->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->timestamps();
        });

        Schema::create('media', function ($table) {
            $table->id();
            $table->morphs('model');
            $table->uuid('uuid')->nullable()->unique();
            $table->string('collection_name');
            $table->string('name');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->string('disk');
            $table->string('conversions_disk')->nullable();
            $table->unsignedBigInteger('size');
            $table->json('manipulations');
            $table->json('custom_properties');
            $table->json('generated_conversions');
            $table->json('responsive_images');
            $table->unsignedInteger('order_column')->nullable()->index();
            $table->timestamps();
        });
    }

    protected function getEnvironmentSetUp($app)
    {
        $app['view']->addNamespace('media-manager', __DIR__.'/../resources/views');

        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('app.key', 'base64:2fl+Ktvkfl+Fuz4Qhye60lD0Iarr+G7H9j6h7P5q3Cs=');

        $storagePath = __DIR__.'/storage';
        $app['config']->set('session.driver', 'file');
        $app['config']->set('session.files', $storagePath.'/framework/sessions');
        $app['config']->set('view.compiled', $storagePath.'/framework/views');
        $app['config']->set('cache.default', 'file');
        $app['config']->set('cache.stores.file.path', $storagePath.'/framework/cache');

        $app['config']->set('auth.providers.users.model', User::class);

        $app->singleton(DataStore::class);

        $app->make(Kernel::class)->prependMiddleware(StartSession::class);
        $app->make(Kernel::class)->prependMiddleware(ShareErrorsFromSession::class);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $compiledPath = config('view.compiled');
        if (is_dir($compiledPath)) {
            foreach (glob($compiledPath.'/*') as $file) {
                if (is_file($file)) {
                    unlink($file);
                }
            }
        }

        Session::start();
        Session::flush();

        $errorBag = new ViewErrorBag;
        $errorBag->put('default', new MessageBag);

        View::share('errors', $errorBag);
    }
}
