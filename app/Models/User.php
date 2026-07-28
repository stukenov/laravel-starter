<?php

namespace App\Models;

use App\Support\Files\HasStoredFiles;
use App\Support\Files\StoredFile;
use App\Support\Tenancy\BelongsToTenant;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Traits\HasRoles;

/**
 * @property string $id
 * @property string $name
 * @property string $email
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property string $locale
 * @property string $timezone
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'avatar', 'password', 'locale', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use BelongsToTenant, HasApiTokens, HasFactory, HasRoles, HasStoredFiles, HasUuids, LogsActivity, Notifiable;

    /*
     * RULE(db): UUIDv7 keys, generated in two places on purpose.
     *
     * HasUuids in Laravel 13 returns Str::uuid7(), so the model sets the
     * key itself and knows it immediately. The uuidv7() default in the
     * database covers rows inserted around Eloquent: by a migration, a
     * script, or by hand in psql.
     *
     * One mechanism is not enough. Disable model generation and Eloquent
     * stops recognising the key the database issued, filling it with
     * junk. Drop the database default and inserts outside models break.
     * See AGENTS.md.
     */

    /**
     * RULE(search): two mechanisms, and both are needed — AGENTS.md.
     *
     * Full-text search matches whole words only, and it tokenises
     * "nurlan@example.kz" as a single email token — so searching for
     * "nurlan" finds nothing. People search exactly like that.
     *
     * So: tsvector handles whole words in names, ILIKE over the pg_trgm
     * index handles fragments and emails. Drop either one and a whole
     * class of searches silently returns nothing.
     *
     * @param  Builder<User>  $query
     * @return Builder<User>
     */
    public function scopeSearch($query, string $term)
    {
        $term = trim($term);

        if ($term === '') {
            return $query;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        return $query->where(function ($q) use ($term, $like) {
            $q->where('name', 'ilike', $like)
                ->orWhere('email', 'ilike', $like);

            // plainto_tsquery on a fragment yields an empty query that
            // matches nothing, so only add it for real words.
            if (mb_strlen($term) >= 4) {
                $q->orWhereRaw(
                    "search_vector @@ plainto_tsquery('simple', ?)",
                    [$term],
                );
            }
        });
    }

    /**
     * RULE(activity): log what changed, never the password.
     *
     * The hash is useless in a log and dangerous in a backup of one.
     * dontSubmitEmptyLogs keeps the feed free of entries recording that
     * nothing happened.
     */
    /**
     * What a list of people is allowed to show.
     *
     * Named here rather than written inline in the controller, like
     * `toPanelItem()` on a generated entity — and for one more reason
     * now: the API and the panel share the actions, so a shape built in
     * a closure would be a shape only one of them could rely on. This is
     * the record an API caller gets back from a write.
     *
     * @return array<string, mixed>
     */
    public function toPanelItem(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            // RULE(time): ISO 8601 out, the client formats it — AGENTS.md.
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'roles' => $this->relationLoaded('roles')
                ? $this->roles->pluck('name')->all()
                : [],
        ];
    }

    /**
     * What the signed-in person's own pages are allowed to see.
     *
     * Named, like `toPanelItem()` and `toFeedItem()` — the shared props
     * used to hand the page `$request->user()`, the model itself, on
     * every Inertia response there is. Nothing leaks today, because
     * `#[Hidden]` covers the password and the remember token. But that is
     * a list of what to withhold, and the only one in this codebase: add
     * a column to `users` — an internal note, a flag, an integration
     * token — and it appears in the JSON of every panel page, for as long
     * as nobody remembers to extend the list.
     *
     * A list of what to send has the opposite failure: forget it and a
     * field is missing, which somebody notices immediately.
     *
     * @return array<string, mixed>
     */
    public function toSharedProps(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'locale' => $this->locale,
            'timezone' => $this->timezone,
            // A route, not a path: the disk is private and the file is
            // served through the policy on the record — AGENTS.md.
            'avatar' => $this->avatar === null
                ? null
                : route('dash.files.show', ['entity' => 'users', 'id' => $this->id, 'field' => 'avatar']),
            'email_verified_at' => $this->email_verified_at?->toIso8601String(),
        ];
    }

    /**
     * Change this person's roles and leave a trace of it.
     *
     * `logOnly` watches columns, and a role does not live in one — it is
     * a row in a pivot table. So granting somebody administrator wrote
     * nothing at all to the activity log: the count before and after was
     * identical, verified. The log exists to answer "who gave this person
     * access", and that was the one question it could not answer.
     *
     * Logged only when something actually changed, and with both sides
     * recorded: "roles: member → admin, member" is an audit entry, "roles
     * changed" is not.
     *
     * The causer is nullable because `php artisan user:admin` has none:
     * it is run by whoever holds the shell. An entry with no causer reads
     * as "system" on the page, which is honest — the grant happened, and
     * outside the application. Leaving it out entirely would mean the
     * most privileged grant of all was the one nothing recorded.
     *
     * @param  array<int, string>  $roles
     */
    public function syncRolesAudited(array $roles, ?self $causer = null): void
    {
        $before = $this->roles->pluck('name')->sort()->values()->all();

        $this->syncRoles($roles);

        $after = $this->fresh()->roles->pluck('name')->sort()->values()->all();

        if ($before === $after) {
            return;
        }

        activity('user')
            ->performedOn($this)
            ->causedBy($causer)
            ->event('roles')
            // `withChanges`, not `withProperties`: the two write to
            // different columns, and the panel reads `attribute_changes`.
            // Sent to the wrong one the entry appears in the log with
            // nothing in it, which is worse than no entry — it looks like
            // a change that had no content.
            ->withChanges(['old' => ['roles' => $before], 'attributes' => ['roles' => $after]])
            ->log('roles');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'email', 'locale', 'timezone'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges()
            ->useLogName('user');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            // Stored on assignment, deleted when replaced or when the
            // account goes — see App\Support\Files\HasStoredFiles.
            'avatar' => StoredFile::class,
            'password' => 'hashed',
        ];
    }
}
