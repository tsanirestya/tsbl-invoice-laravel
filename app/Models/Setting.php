<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;

    const UPDATED_AT = 'updated_at';
    const CREATED_AT = null;

    protected $fillable = ['key', 'value', 'label'];

    public static function get(string $key, mixed $default = null): mixed
    {
        // F-018: cache all settings for 5 min — eliminates 28+ DB hits per request
        $all = Cache::remember('settings_all', 300, fn() => static::pluck('value', 'key'));
        return $all->get($key, $default);
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value, 'updated_at' => now()]);
        Cache::forget('settings_all');
    }
}
