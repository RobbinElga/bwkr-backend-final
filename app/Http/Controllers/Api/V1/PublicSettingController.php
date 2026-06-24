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

        foreach ($groups as $group) {
            foreach ($group['fields'] as $f) {
                if ($f['type'] === 'image') {
                    $key = $f['key'];
                    $values[$key] = ! empty($values[$key]) ? Storage::disk('public')->url($values[$key]) : null;
                }
            }
        }

        return response()->json(['data' => $values]);
    }
}
