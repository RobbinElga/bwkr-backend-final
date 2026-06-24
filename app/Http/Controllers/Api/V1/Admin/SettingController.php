<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditService $audit,
    ) {}

    /** Kirim skema grup + nilai sekarang (URL untuk field gambar). */
    public function index()
    {
        $values = $this->settings->all();
        $groups = config('site_settings.groups', []);

        $data = [];
        foreach ($groups as $key => $group) {
            $fields = array_map(function ($f) use ($values) {
                $val = $values[$f['key']] ?? null;
                $f['value'] = $val;
                if ($f['type'] === 'image') {
                    $f['url'] = $val ? Storage::disk('public')->url($val) : null;
                }
                return $f;
            }, $group['fields']);

            $data[] = ['key' => $key, 'label' => $group['label'], 'fields' => $fields];
        }

        return response()->json(['data' => $data]);
    }

    /** Simpan teks + upload gambar (multipart). */
    public function update(Request $request)
    {
        $groups    = config('site_settings.groups', []);
        $imageKeys = [];
        $textKeys  = [];
        foreach ($groups as $group) {
            foreach ($group['fields'] as $f) {
                $f['type'] === 'image' ? $imageKeys[] = $f['key'] : $textKeys[] = $f['key'];
            }
        }

        $payload = [];

        // teks
        foreach ($textKeys as $key) {
            if ($request->has($key)) {
                $payload[$key] = $request->input($key);
            }
        }

        // gambar
        foreach ($imageKeys as $key) {
            // Hapus gambar (kosongkan + hapus file lama)
            if ($request->boolean("{$key}__remove")) {
                $old = Setting::where('key', $key)->value('value');
                if ($old) Storage::disk('public')->delete($old);
                Setting::updateOrCreate(['key' => $key], ['value' => null]);
                continue;
            }
            if ($request->hasFile($key)) {
                $request->validate([$key => ['image', 'mimes:jpeg,jpg,png,webp,svg', 'max:5120']]);
                $old = Setting::where('key', $key)->value('value');
                if ($old) Storage::disk('public')->delete($old);
                $payload[$key] = $request->file($key)->store('settings', 'public');
            }
        }

        $this->settings->setMany($payload);
        $this->audit->log('updated', null, new: ['settings' => array_keys($payload)]);

        return response()->json(['message' => 'Pengaturan disimpan.']);
    }
}
