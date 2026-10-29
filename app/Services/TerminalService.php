<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\TerminalPasswordResetNotification;
use App\Repositories\Setting\SettingRepositoryInterface;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\StringInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class TerminalService
{
    private const SESSION_KEY = 'terminal.unlocked';

    private const PASSWORD_KEY = 'terminal.password';

    private const RESET_TOKEN_KEY = 'terminal.reset_token';

    private const RESET_EXPIRES_KEY = 'terminal.reset_expires';

    private const RESET_MINUTES = 60;

    public function __construct(
        protected SettingRepositoryInterface $settingRepository,
    ) {}

    public function isConfigured(): bool
    {
        return filled($this->settingRepository->get(self::PASSWORD_KEY));
    }

    public function isUnlocked(): bool
    {
        return $this->isConfigured() && session()->get(self::SESSION_KEY, false) === true;
    }

    public function checkPassword(string $password): bool
    {
        $hash = $this->settingRepository->get(self::PASSWORD_KEY);

        return filled($hash) && Hash::check($password, $hash);
    }

    public function unlock(string $password): bool
    {
        if (! $this->checkPassword($password)) {
            return false;
        }

        session()->put(self::SESSION_KEY, true);

        return true;
    }

    /**
     * Store a new terminal password and keep this session unlocked.
     */
    public function setPassword(string $password): void
    {
        $this->settingRepository->put(self::PASSWORD_KEY, Hash::make($password));
        session()->put(self::SESSION_KEY, true);
    }

    /**
     * Email the given admin a link that lets them choose a new terminal password.
     */
    public function sendResetLink(User $user): void
    {
        $token = Str::random(64);

        $this->settingRepository->put(self::RESET_TOKEN_KEY, hash('sha256', $token));
        $this->settingRepository->put(self::RESET_EXPIRES_KEY, (string) now()->addMinutes(self::RESET_MINUTES)->getTimestamp());

        $user->notify(new TerminalPasswordResetNotification(route('terminal.index', ['reset' => $token]), self::RESET_MINUTES));
    }

    /**
     * Set a new terminal password using an emailed token. The token works once.
     */
    public function resetPassword(string $token, string $password): bool
    {
        $stored = $this->settingRepository->get(self::RESET_TOKEN_KEY);
        $expires = (int) $this->settingRepository->get(self::RESET_EXPIRES_KEY);

        if (blank($stored) || now()->getTimestamp() > $expires || ! hash_equals($stored, hash('sha256', $token))) {
            return false;
        }

        $this->settingRepository->put(self::RESET_TOKEN_KEY, null);
        $this->settingRepository->put(self::RESET_EXPIRES_KEY, null);
        $this->setPassword($password);

        return true;
    }

    public function lock(): void
    {
        session()->forget(self::SESSION_KEY);
    }

    /**
     * Run any command through bash in the project root.
     *
     * @return array{command: string, output: string, exit_code: int}
     */
    public function run(string $command): array
    {
        $command = trim($command);

        if (! $this->canSpawnProcesses()) {
            return $this->runArtisan($command);
        }

        $process = new Process(['bash', '-c', $command], base_path(), $this->environment(), null, (float) config('terminal.timeout'));

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            return ['command' => $command, 'output' => $process->getOutput().$process->getErrorOutput().PHP_EOL.__('terminal.errors.timeout', ['seconds' => config('terminal.timeout')]), 'exit_code' => 124];
        }

        return ['command' => $command, 'output' => $process->getOutput().$process->getErrorOutput(), 'exit_code' => $process->getExitCode() ?? 1];
    }

    /**
     * Shared hosts often disable proc_open, so bash cannot be started there.
     */
    public function canSpawnProcesses(): bool
    {
        return config('terminal.shell') && function_exists('proc_open');
    }

    /**
     * Fallback without bash: only "php artisan ..." commands, run inside this PHP process.
     *
     * @return array{command: string, output: string, exit_code: int}
     */
    private function runArtisan(string $command): array
    {
        $artisan = preg_replace('/^(php\s+)?artisan\s+/i', '', $command, -1, $count);

        if ($count === 0) {
            return ['command' => $command, 'output' => __('terminal.errors.artisan_only'), 'exit_code' => 1];
        }

        $name = (new StringInput($artisan))->getFirstArgument();

        if (in_array($name, config('terminal.blocked'), true)) {
            return ['command' => $command, 'output' => __('terminal.errors.blocked'), 'exit_code' => 1];
        }

        $output = new BufferedOutput;

        try {
            $exitCode = Artisan::call($artisan.' --no-interaction', [], $output);
        } catch (Throwable $e) {
            $exitCode = 1;
            $output->write($e->getMessage());
        }

        return ['command' => $command, 'output' => $output->fetch(), 'exit_code' => $exitCode];
    }

    /**
     * Put the running PHP's folder first on PATH, so "php" works in bash even when the
     * web server (or Git Bash on Windows) does not have it on its PATH.
     *
     * @return array<string, string>
     */
    private function environment(): array
    {
        $key = array_key_exists('Path', getenv()) ? 'Path' : 'PATH';

        return [$key => dirname(PHP_BINARY).PATH_SEPARATOR.(string) getenv($key)];
    }
}
