<?php

namespace Slimani\MediaManager\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * @property int $id
 * @property string $name
 * @property int|null $parent_id
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Folder extends Model
{
    protected $table = 'media_folders';

    protected $fillable = [
        'name',
        'parent_id',
    ];

    public function tags(): MorphToMany
    {
        return $this->morphToMany(Tag::class, 'taggable', 'media_taggables');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Folder::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Folder::class, 'parent_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(File::class, 'folder_id');
    }

    /**
     * Fetch all child folder IDs beneath this folder using a high-performance Recursive CTE.
     * This executes a single raw SQL query to get infinite depth instead of N+1 PHP recursion.
     */
    public function getAllDescendantIds(): array
    {
        $query = '
            WITH RECURSIVE FolderHierarchy AS (
                SELECT id, parent_id FROM media_folders WHERE id = ?
                UNION ALL
                SELECT f.id, f.parent_id FROM media_folders f
                INNER JOIN FolderHierarchy fh ON fh.id = f.parent_id
            )
            SELECT id FROM FolderHierarchy WHERE id != ?
        ';

        $results = DB::select($query, [$this->id, $this->id]);

        return array_column($results, 'id');
    }

    /**
     * Get recursive statistics for this folder (total size and files count across all levels).
     */
    public function getRecursiveStats(): array
    {
        $query = '
            WITH RECURSIVE FolderHierarchy AS (
                SELECT id FROM media_folders WHERE id = ?
                UNION ALL
                SELECT f.id FROM media_folders f
                INNER JOIN FolderHierarchy fh ON fh.id = f.parent_id
            )
            SELECT 
                COUNT(DISTINCT media_files.id) as files_count,
                SUM(media_files.size) as total_size,
                (SELECT COUNT(*) FROM FolderHierarchy WHERE id != ?) as folders_count
            FROM FolderHierarchy
            LEFT JOIN media_files ON media_files.folder_id = FolderHierarchy.id
        ';

        $result = DB::selectOne($query, [$this->id, $this->id]);

        return [
            'files_count' => (int) ($result->files_count ?? 0),
            'folders_count' => (int) ($result->folders_count ?? 0),
            'total_size' => (int) ($result->total_size ?? 0),
        ];
    }
}
