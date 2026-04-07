<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class AppSetting extends Model
{
    protected $fillable = [
        'key',
        'value',
    ];

    public static function getValue(string $key, ?string $default = null): ?string
    {
        $settings = Cache::rememberForever('app_settings.all', function () {
            return self::query()->pluck('value', 'key')->all();
        });

        return $settings[$key] ?? $default;
    }

    public static function setValue(string $key, ?string $value): void
    {
        self::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );

        Cache::forget('app_settings.all');
    }

    public static function systemLogoPath(): ?string
    {
        return self::getValue('system_logo_path');
    }

    public static function systemLogoUrl(): ?string
    {
        $path = self::systemLogoPath();

        if (!$path) {
            return null;
        }

        return asset('storage/' . ltrim($path, '/'));
    }
}
