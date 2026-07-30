<?php

declare(strict_types=1);

namespace App\Exceptions;

use RuntimeException;

class UnsupportedDatabaseDriver extends RuntimeException
{
    public static function forDriver(string $driver): self
    {
        return new self(sprintf(
            'This application requires PostgreSQL. Configured driver: "%s". '.
            'Set DB_CONNECTION=pgsql in your .env file. '.
            'The schema relies on uuidv7(), citext and timestamptz, '.
            'none of which exist on other drivers. See AGENTS.md.',
            $driver === '' ? '(empty)' : $driver,
        ));
    }
}
