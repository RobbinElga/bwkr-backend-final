<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\StoreUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class UserManagementController extends Controller
{
    public function __construct(private readonly AuditService $audit) {}

    public function index(Request $request)
    {
        $users = User::query()
            ->when(
                $request->role,
                fn($q, $r) => $q->where('role', $r),
                fn($q) => $q->where('role', '!=', 'donatur')   // default: staff saja
            )
            ->when($request->search, fn($q, $s) => $q->where(
                fn($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")
            ))
            ->latest()
            ->paginate($request->integer('per_page', 15));

        return UserResource::collection($users);
    }

    public function store(StoreUserRequest $request)
    {
        $data = $request->validated();

        $user = User::create([
            'name'      => $data['name'],
            'email'     => $data['email'],
            'phone'     => $data['phone'],
            'role'      => $data['role'],
            'password'  => $data['password'],
            'is_active' => $data['is_active'] ?? true,
        ]);

        $this->audit->log('created', $user, new: $this->auditable($user));

        return (new UserResource($user))->response()->setStatusCode(201);
    }

    public function show(User $user)
    {
        return new UserResource($user);
    }

    public function update(UpdateUserRequest $request, User $user)
    {
        $old = $this->auditable($user);
        $user->update($request->validated());
        $this->audit->log('updated', $user, $old, $this->auditable($user->fresh()));

        return new UserResource($user->fresh());
    }

    public function destroy(User $user)
    {
        abort_if($user->id === Auth::id(), 422, 'Tidak dapat menghapus akun sendiri.');

        $user->tokens()->delete();   // logout dari semua perangkat
        $user->delete();
        $this->audit->log('deleted', $user);

        return response()->json(['message' => 'User berhasil dinonaktifkan.']);
    }

    /** Reset password -> kembalikan password sementara, cabut semua token. */
    public function resetPassword(User $user)
    {
        $temp = Str::password(12);
        $user->password = $temp;
        $user->save();
        $user->tokens()->delete();

        $this->audit->log('password_reset', $user);

        return response()->json([
            'message'            => 'Kata sandi berhasil direset.',
            'temporary_password' => $temp,
        ]);
    }

    /** Reset 2FA -> user wajib setup ulang saat login berikutnya. */
    public function resetTwoFactor(User $user)
    {
        $user->forceFill([
            'two_factor_enabled'        => false,
            'two_factor_secret'         => null,
            'two_factor_confirmed_at'   => null,
            'two_factor_recovery_codes' => null,
        ])->save();
        $user->tokens()->delete();

        $this->audit->log('2fa_reset', $user);

        return response()->json(['message' => '2FA direset. User akan setup ulang saat login berikutnya.']);
    }

    /** Data audit aman — tanpa data sensitif. */
    private function auditable(User $user): array
    {
        return collect($user->toArray())->only(['id', 'name', 'email', 'role', 'is_active'])->all();
    }

    public function counts()
    {
        return response()->json([
            'staff'   => User::where('role', '!=', 'donatur')->count(),
            'donatur' => User::where('role', 'donatur')->count(),
        ]);
    }
}
