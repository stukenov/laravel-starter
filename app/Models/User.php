<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
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
