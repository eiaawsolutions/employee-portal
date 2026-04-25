<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class PlatformSetting extends Model
{
    protected $table = 'platform_settings';
    protected $primaryKey = 'key';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value', 'is_secret', 'updated_by'];
    protected $casts = ['is_secret' => 'boolean'];

    private const CACHE_PREFIX = 'platform_setting:';
    private const CACHE_TTL = 600;

    protected function value(): Attribute
    {
        return Attribute::make(
            get: function ($raw) {
                if ($raw === null || $raw === '') return $raw;
                if (! $this->is_secret) return $raw;
                try {
                    return Crypt::decryptString($raw);
                } catch (\Throwable $e) {
                    Log::warning('PlatformSetting decrypt failed', ['key' => $this->getAttribute('key')]);
                    return null;
                }
            },
            set: function ($plain) {
                if ($plain === null || $plain === '') return $plain;
                if (! $this->is_secret) return $plain;
                return Crypt::encryptString($plain);
            },
        );
    }

    public static function get(string $key, $default = null)
    {
        return Cache::remember(self::CACHE_PREFIX . $key, self::CACHE_TTL, function () use ($key, $default) {
            $row = static::find($key);
            return $row ? $row->value : $default;
        });
    }

    public static function put(string $key, ?string $value, bool $isSecret = true, ?int $userId = null): self
    {
        $row = static::firstOrNew(['key' => $key]);
        $row->is_secret = $isSecret;
        $row->value = $value;
        $row->updated_by = $userId;
        $row->save();
        Cache::forget(self::CACHE_PREFIX . $key);
        return $row;
    }

    public static function forget(string $key): void
    {
        static::where('key', $key)->delete();
        Cache::forget(self::CACHE_PREFIX . $key);
    }

    public function maskedValue(): ?string
    {
        if (! $this->value) return null;
        $v = $this->value;
        if (strlen($v) <= 8) return str_repeat('•', strlen($v));
        return substr($v, 0, 4) . str_repeat('•', max(8, strlen($v) - 8)) . substr($v, -4);
    }
}
