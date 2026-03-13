<?php

namespace Slimani\MediaManager\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Foundation\Auth\User;
use Slimani\MediaManager\Database\Factories\FileFactory;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class File extends Model implements HasMedia
{
    use HasFactory;
    use InteractsWithMedia;

    protected static function newFactory(): FileFactory
    {
        return FileFactory::new();
    }

    protected $table = 'media_files';

    protected $fillable = [
        'uploaded_by_user_id',
        'folder_id',
        'name',
        'caption',
        'alt_text',
        'size',
        'extension',
        'mime_type',
        'width',
        'height',
    ];

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'media_taggables');
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(300)
            ->sharpen(10)
            ->nonQueued();

        $this->addMediaConversion('preview')
            ->width(800)
            ->height(800)
            ->nonQueued();
    }

    public function folder(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'folder_id');
    }

    public function uploader(): BelongsTo
    {
        $userModel = config('auth.providers.users.model') ?? 'App\Models\User';

        if (! class_exists($userModel)) {
            // Fallback for tests or environments where the model isn't available yet
            return $this->belongsTo(User::class, 'uploaded_by_user_id');
        }

        return $this->belongsTo($userModel, 'uploaded_by_user_id');
    }

    public function getUrl(string $conversion = ''): ?string
    {
        $media = $this->getFirstMedia('default');

        if (! $media) {
            return null;
        }

        try {
            return $media->getTemporaryUrl(now()->addMinutes(20), $conversion);
        } catch (\Throwable $exception) {
            // Fallback to standard URL if driver doesn't support temporary URLs
            return $media->getUrl($conversion);
        }
    }
}
