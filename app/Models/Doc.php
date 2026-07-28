<?php

namespace App\Models;

use App\Support\Files\HasStoredFiles;
use Database\Factories\DocFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['title', 'attachment'])]
class Doc extends Model
{
    /** @use HasFactory<DocFactory> */
    use HasFactory, HasStoredFiles, HasUuids;

    /*
     * RULE(db): UUIDv7 keys, generated in two places on purpose. HasUuids
     * sets the key on the model; the database default covers inserts that
     * bypass Eloquent. See AGENTS.md.
     */

    /**
     * RULE(search): the query lives on the model — a controller may not
     * reach the database directly. See AGENTS.md.
     *
     * @param  Builder<Doc>  $query
     * @return Builder<Doc>
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        // Grouped, not chained: a bare `orWhere` escapes any condition
        // the caller already applied, so a scoped list would quietly
        // return rows it had been filtered away from.
        return $query->where(function ($query) use ($like) {
            $query->where('title', 'ilike', $like);
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'attachment' => 'App\Support\Files\StoredFile',
        ];
    }

    /**
     * What the panel is allowed to see.
     *
     * Named rather than implied: a page that receives the whole model
     * receives every column added to it afterwards, whether or not
     * anybody meant it to.
     *
     * @return array<string, mixed>
     */
    public function toPanelItem(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'attachment' => $this->attachment,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
