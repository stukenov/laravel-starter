<?php

namespace App\Models;

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
#[Fillable(['name', 'email', 'password', 'locale', 'timezone'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasUuids, Notifiable;

    /*
     * ПРАВИЛО(база): идентификаторы — UUIDv7, и генераторов намеренно два.
     *
     * HasUuids в Laravel 13 отдаёт Str::uuid7(), то есть модель проставляет
     * ключ сама и сразу его знает. Значение по умолчанию uuidv7() в базе —
     * страховка для записей, вставленных мимо Eloquent: миграцией,
     * скриптом или руками в psql.
     *
     * Одного механизма мало. Отключишь генерацию в модели — Eloquent
     * перестанет узнавать выданный базой ключ и начнёт подставлять мусор.
     * Уберёшь значение по умолчанию — сломаются вставки мимо моделей.
     * См. AGENTS.md
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
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
