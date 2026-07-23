<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditService;
use App\Services\ImageService;
use App\Services\SettingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function __construct(
        private readonly SettingService $settings,
        private readonly AuditService $audit,
        private readonly ImageService $images,
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
            if ($request->hasFile($key)) {
                $request->validate([$key => ['image', 'mimes:jpeg,jpg,png,webp,svg', 'max:5120']]);

                $old = Setting::where('key', $key)->value('value');
                if ($old) Storage::disk('public')->delete($old);

                $file  = $request->file($key);
                $isSvg = strtolower($file->getClientOriginalExtension()) === 'svg'
                    || $file->getClientMimeType() === 'image/svg+xml';

                // SVG: vektor, sudah ringan → simpan apa adanya.
                // Raster (png/jpg/webp): konversi ke WebP + resize (maks 800px cukup untuk logo/settings).
                $payload[$key] = $isSvg
                    ? $file->store('settings', 'public')
                    : $this->images->store($file, 'settings', 800);
            }
        }

        $this->settings->setMany($payload);
        $this->audit->log('updated', null, new: ['settings' => array_keys($payload)]);

        return response()->json(['message' => 'Pengaturan disimpan.']);
    }


    /** Status WA untuk admin (tidak mengembalikan token asli). */
    public function whatsapp()
    {
        $enabledRaw = $this->settings->get('wa_enabled');
        return response()->json([
            'wa_enabled'     => $enabledRaw !== null
                ? filter_var($enabledRaw, FILTER_VALIDATE_BOOLEAN)
                : (bool) config('whatsapp.enabled'),
            'wa_api_key_set' => ! empty($this->settings->get('wa_api_key')),
        ]);
    }

    /** Simpan token & status WA. */
    public function updateWhatsapp(Request $request)
    {
        $request->validate([
            'wa_api_key' => ['nullable', 'string', 'max:255'],
            'wa_enabled' => ['nullable', 'boolean'],
        ]);

        $payload = [];
        if ($request->filled('wa_api_key')) {
            $payload['wa_api_key'] = trim($request->input('wa_api_key'));
        }
        if ($request->has('wa_enabled')) {
            $payload['wa_enabled'] = $request->boolean('wa_enabled') ? '1' : '0';
        }

        $this->settings->setMany($payload);
        $this->audit->log('updated', null, new: ['settings' => array_keys($payload)]);

        return response()->json(['message' => 'Pengaturan WhatsApp disimpan.']);
    }
}
