<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /**
     * Ambil nilai setting; $default dipakai bila belum ada.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $row = static::query()->where('key', $key)->first();

        return $row?->value ?? $default;
    }

    /**
     * Simpan setting.
     */
    public static function set(string $key, mixed $value, string $group = 'general'): void
    {
        static::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value === null ? null : (string) $value, 'group' => $group]
        );
    }

    /**
     * Hapus setting.
     */
    public static function forget(string $key): void
    {
        static::query()->where('key', $key)->delete();
    }

    /**
     * Ambil nilai numerik boolean ('1'/'0').
     */
    public static function bool(string $key): bool
    {
        return in_array((string) static::get($key), ['1', 'true', 'on', 'yes'], true);
    }
}
