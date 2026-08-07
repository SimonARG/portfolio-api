<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutIncrementing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Typed key/value configuration.
 *
 * @property string $key
 * @property mixed $value
 */
#[Fillable(['key', 'value'])]
#[WithoutIncrementing]
class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasFactory;

    /**
     * The cache-invalidation lever described in §2.3. Every content write bumps
     * it; it forms part of every Laravel cache key and every Nitro ISR key, so
     * one write flushes all layers coherently.
     */
    public const string CONTENT_VERSION = 'content_version';

    protected $primaryKey = 'key';

    protected $keyType = 'string';

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return static::query()->find($key)->value ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }

    /**
     * Increment `content_version` atomically and return the new value.
     *
     * The bump happens in SQL rather than as read-modify-write in PHP: two
     * concurrent admin writes must not both read version 7 and both write 8,
     * which would leave a stale cache entry live under a version key that
     * consumers believe is current.
     */
    public static function bumpContentVersion(): int
    {
        $row = DB::selectOne(
            "INSERT INTO settings (key, value, created_at, updated_at)
             VALUES (?, '1'::jsonb, now(), now())
             ON CONFLICT (key) DO UPDATE
               SET value = ((settings.value)::int + 1)::text::jsonb,
                   updated_at = now()
             RETURNING value",
            [self::CONTENT_VERSION],
        );

        return (int) json_decode((string) $row->value, true);
    }

    public static function contentVersion(): int
    {
        return (int) (static::get(self::CONTENT_VERSION) ?? 0);
    }
}
