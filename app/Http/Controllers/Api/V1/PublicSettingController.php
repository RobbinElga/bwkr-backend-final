<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\SettingService;
use Illuminate\Support\Facades\Storage;

class PublicSettingController extends Controller
{
    public function __construct(private readonly SettingService $settings) {}

    /** Map setting publik (gambar sudah jadi URL). */
    public function index()
    {
        $values = $this->settings->all();
        $groups = config('site_settings.groups', []);

        $data = [];
        foreach ($groups as $gkey => $group) {
            if ($gkey === 'wa') {
                continue; // jangan ekspos pesan/token WA ke publik
            }
            foreach ($group['fields'] as $f) {
                $key = $f['key'];
                $val = $values[$key] ?? null;
                if ($f['type'] === 'image') {
                    $val = ! empty($val) ? Storage::disk('public')->url($val) : null;
                }
                $data[$key] = $val;
            }
        }

        return response()->json(['data' => $data]);
    }
}
