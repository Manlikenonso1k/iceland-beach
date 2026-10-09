<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

/**
 * Tenant-scoped key/value store. Mirrors the global Setting model's get/set/cast
 * shape, but every read and write is confined to the current tenant by
 * BelongsToTenant. Values persist as text; `type` records how to cast them back.
 */
class TenantSetting extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'key',
        'value',
        'type',
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::query()->where('key', $key)->first();

        if (! $setting) {
            return $default;
        }

        return $setting->castValue();
    }

    public static function set(string $key, mixed $value, string $type = 'string'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => static::stringify($value, $type), 'type' => $type],
        );
    }

    /** All settings for the current tenant as a plain key => cast-value map. */
    public static function map(): array
    {
        return static::query()->get()
            ->mapWithKeys(fn (TenantSetting $s): array => [$s->key => $s->castValue()])
            ->all();
    }

    public function castValue(): mixed
    {
        return match ($this->type) {
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'integer' => (int) $this->value,
            default => $this->value,
        };
    }

    private static function stringify(mixed $value, string $type): string
    {
        return match ($type) {
            'boolean' => $value ? '1' : '0',
            'integer' => (string) (int) $value,
            default => (string) $value,
        };
    }
}
