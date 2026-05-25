<?php

namespace App\Http\Controllers;

use App\Services\PublicUploadService;
use App\Support\Toast;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    private const AVATAR_EXT = ['jpg', 'jpeg', 'png', 'webp'];

    private const AVATAR_MIMES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private PublicUploadService $uploads) {}

    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user()->load('company'),
        ]);
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $user = auth()->user();

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'avatar_data' => ['nullable', 'string'],
            'avatar_name' => ['nullable', 'string', 'max:255'],
            'remove_avatar' => ['nullable', 'boolean'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
        ]);

        $user->name = $data['name'];
        $user->phone = $data['phone'] ?? null;

        if ($request->boolean('remove_avatar') && $user->avatar_path) {
            $this->uploads->delete($user->avatar_path);
            $user->avatar_path = null;
        }

        if (! empty($data['avatar_data']) && ! empty($data['avatar_name'])) {
            if ($user->avatar_path) {
                $this->uploads->delete($user->avatar_path);
            }
            $user->avatar_path = $this->storeAvatarBinary($data['avatar_data'], $data['avatar_name'], $user->id);
        }

        if (! empty($data['password'])) {
            $user->password = Hash::make($data['password']);
        }

        $user->save();

        return Toast::to(route('profile.edit'), 'Profile updated successfully.');
    }

    private function storeAvatarBinary(string $base64, string $originalName, int $userId): string
    {
        $binary = $this->uploads->decodeBase64($base64);

        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if (! in_array($ext, self::AVATAR_EXT, true)) {
            throw ValidationException::withMessages([
                'avatar_data' => 'Only JPG, PNG or WebP images are allowed.',
            ]);
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($binary);
        if (! in_array($mime, self::AVATAR_MIMES, true)) {
            throw ValidationException::withMessages([
                'avatar_data' => 'Invalid image file. Use JPG, PNG or WebP.',
            ]);
        }

        return $this->uploads->storeBinary($binary, $originalName, 'avatars', $userId);
    }
}
