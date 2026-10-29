<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Terminal\ChangeTerminalPasswordRequest;
use App\Http\Requests\Terminal\ResetTerminalPasswordRequest;
use App\Http\Requests\Terminal\RunTerminalCommandRequest;
use App\Http\Requests\Terminal\UnlockTerminalRequest;
use App\Services\TerminalService;
use App\Traits\Terminal\DocumentsApi;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TerminalController extends Controller
{
    use DocumentsApi;

    public function __construct(
        protected TerminalService $terminalService,
    ) {}

    public function show(): JsonResponse
    {
        return $this->state();
    }

    public function unlock(UnlockTerminalRequest $request): JsonResponse
    {
        if (! $this->terminalService->isConfigured()) {
            throw ValidationException::withMessages(['password' => __('terminal.errors.not_configured')]);
        }

        if (! $this->terminalService->unlock($request->validated('password'))) {
            throw ValidationException::withMessages(['password' => __('terminal.errors.wrong_password')]);
        }

        return $this->state();
    }

    public function password(ChangeTerminalPasswordRequest $request): JsonResponse
    {
        if ($this->terminalService->isConfigured() && ! $this->terminalService->checkPassword($request->validated('current_password'))) {
            throw ValidationException::withMessages(['current_password' => __('terminal.errors.wrong_password')]);
        }

        $this->terminalService->setPassword($request->validated('password'));

        return $this->state();
    }

    public function forgot(Request $request): JsonResponse
    {
        if (! $this->terminalService->isConfigured()) {
            throw ValidationException::withMessages(['terminal_password' => __('terminal.errors.not_configured')]);
        }

        $this->terminalService->sendResetLink($request->user());

        return response()->json(['message' => __('terminal.forgot.sent', ['email' => $request->user()->email])]);
    }

    public function reset(ResetTerminalPasswordRequest $request): JsonResponse
    {
        if (! $this->terminalService->resetPassword($request->validated('token'), $request->validated('password'))) {
            throw ValidationException::withMessages(['password' => __('terminal.errors.invalid_token')]);
        }

        return $this->state();
    }

    public function lock(): JsonResponse
    {
        $this->terminalService->lock();

        return $this->state();
    }

    public function run(RunTerminalCommandRequest $request): JsonResponse
    {
        if (! $this->terminalService->isUnlocked()) {
            return response()->json(['message' => __('terminal.errors.locked')], 423);
        }

        return response()->json(['data' => $this->terminalService->run($request->validated('command'))]);
    }

    private function state(): JsonResponse
    {
        return response()->json(['data' => [
            'configured' => $this->terminalService->isConfigured(),
            'unlocked' => $this->terminalService->isUnlocked(),
        ]]);
    }
}
