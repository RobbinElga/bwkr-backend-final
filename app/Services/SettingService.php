<?php

namespace App\Services;

use App\Models\Setting;

class SettingService
{
    public function defaults(): array
    {
        return config('site_settings.defaults', []);
    }

    public function all(): array
    {
        return array_merge($this->defaults(), Setting::pluck('value', 'key')->all());
    }

    public function get(string $key, $default = null)
    {
        $val = Setting::where('key', $key)->value('value');
        return $val ?? ($this->defaults()[$key] ?? $default);
    }

    public function setMany(array $kv): void
    {
        foreach ($kv as $key => $value) {
            if ($value === null) continue;
            Setting::updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }

    /** Render template pesan: ganti placeholder lalu rapikan spasi ganda. */
    public function renderTemplate(string $key, array $vars, string $default = ''): string
    {
        $tpl = (string) $this->get($key, $default);
        $msg = strtr($tpl, $vars);
        return trim(preg_replace('/[ \t]{2,}/', ' ', $msg));
    }
}
