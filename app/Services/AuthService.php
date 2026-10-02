<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\User\UserRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Stevebauman\Location\Facades\Location;

class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
    ) {}

    /**
     * Register a new user.
     *
     * @param  array{name: string, email: string, password: string}  $data
     */
    public function register(array $data): User
    {
        return $this->userRepository->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
        ]);
    }

    /**
     * Attempt to authenticate a user and, on success, store their
     * IP-address-detected timezone on the users table.
     *
     * @param  array{email: string, password: string, remember?: bool}  $credentials
     * @return string|null A Sanctum API token, or null on failed authentication.
     */
    public function attemptLogin(array $credentials, Request $request): ?string
    {
        $remember = $credentials['remember'] ?? false;

        $attempted = Auth::attempt([
            'email' => $credentials['email'],
            'password' => $credentials['password'],
        ], $remember);

        if (! $attempted) {
            return null;
        }

        $request->session()->regenerate();

        $this->storeDetectedTimezone($request);

        /** @var User $user */
        $user = Auth::user();

        return $this->issueToken($user);
    }

    /**
     * Local-dev only: log in as the oldest user, creating one first when
     * no user exists.
     *
     * @return array{0: User, 1: string} The user and a Sanctum API token.
     */
    public function devAutoLogin(Request $request): array
    {
        $user = $this->userRepository->first()
            ?? $this->register([
                'name' => 'Dev User',
                'email' => 'dev@example.com',
                'password' => 'password',
            ]);

        Auth::login($user);

        $request->session()->regenerate();

        $this->storeDetectedTimezone($request);

        return [$user,$this->issueToken($user)];
    }

    /**
     * Issue a new Sanctum API token for the given user.
     */
    public function issueToken(User $user): string
    {
        return $user->createToken('api')->plainTextToken;
    }

    /**
     * Detect the authenticated user's timezone from their IP address and
     * persist it to the users table.
     */
    protected function storeDetectedTimezone(Request $request): void
    {
        /** @var User $user */
        $user = Auth::user();

        $timezone = $this->detectTimezone($request->ip());

        if ($timezone !== null) {
            $this->userRepository->update($user, ['timezone' => $timezone]);
        }
    }

    /**
     * Resolve a timezone string from the given IP address.
     */
    protected function detectTimezone(?string $ip): ?string
    {
        if (! $ip) {
            return null;
        }

        // Private/loopback IPs cannot be geolocated; passing null lets the
        // package fall back to its configured testing IP in local envs.
        $isPublic = filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);

        $position = Location::get($isPublic ? $ip : null);

        if (! $position) {
            return null;
        }

        return $position->timezone ?? null;
    }

    /**
     * Log the current user out, invalidate their session, and revoke their
     * issued Sanctum API tokens.
     */
    public function logout(Request $request): void
    {
        /** @var User|null $user */
        $user = $request->user() ?? Auth::guard('web')->user();

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $user?->tokens()->delete();
    }

    /**
     * Send a password reset link to the given email address.
     */
    public function sendPasswordResetLink(string $email): string
    {
        return Password::sendResetLink(['email' => $email]);
    }

    /**
     * Reset the password for the given credentials.
     *
     * @param  array{token: string, email: string, password: string, password_confirmation: string}  $data
     */
    public function resetPassword(array $data): string
    {
        return Password::reset(
            $data,
            function (User $user, string $password): void {
                $this->userRepository->update($user, [
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ]);
            }
        );
    }
}
