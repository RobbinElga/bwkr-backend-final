<?php

namespace App\Http\Controllers\Api\V1\Donatur;

use App\Http\Controllers\Controller;
use App\Http\Requests\Donatur\UpdateProfileRequest;
use App\Http\Resources\ProfileResource;
use App\Services\ImageService;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    /** GET /donatur/profile */
    public function show(Request $request)
    {
        return new ProfileResource($request->user());
    }

    /** PUT /donatur/profile */
    public function update(UpdateProfileRequest $request)
    {
        $user = $request->user();
        $user->update($request->validated());

        return new ProfileResource($user->fresh());
    }

    /** POST /donatur/profile/avatar — upload / ganti foto profil. */
    public function updateAvatar(Request $request, ImageService $images)
    {
        $request->validate([
            'avatar' => ['required', 'image', 'mimes:jpeg,jpg,png,webp', 'max:5120'], // 5MB
        ]);

        $user = $request->user();

        if ($user->avatar_path) {
            $images->delete($user->avatar_path);       // buang foto lama
        }

        $user->avatar_path = $images->store($request->file('avatar'), 'avatars'); // auto-WebP
        $user->save();

        return new ProfileResource($user->fresh());
    }

    /** DELETE /donatur/profile/avatar — hapus foto profil. */
    public function deleteAvatar(Request $request, ImageService $images)
    {
        $user = $request->user();

        if ($user->avatar_path) {
            $images->delete($user->avatar_path);
            $user->avatar_path = null;
            $user->save();
        }

        return new ProfileResource($user->fresh());
    }
}
