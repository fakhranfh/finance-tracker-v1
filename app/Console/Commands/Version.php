<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Process\Process;

/**
 * Computes the next semantic version from Conventional Commits since the last
 * v* tag (or the whole history when untagged): `type!:` / `type(scope)!:` or a
 * "BREAKING CHANGE" footer bumps major, `feat` bumps minor, `fix` bumps patch.
 * Other types (chore, docs, ci, ...) do not bump. Commits are applied oldest to
 * newest, so each one bumps on top of the previous result.
 */
#[AsCommand(name: 'version:next')]
class Version extends Command
{
    protected $signature = 'version:next
        {--tag : Create the git tag (vX.Y.Z) for the computed version}';

    protected $description = 'Compute the next semantic version from git commit messages.';

    public function handle(): int
    {
        $last = trim($this->git(['describe', '--tags', '--abbrev=0', '--match', 'v*'], false));
        $current = $last !== '' ? ltrim($last, 'v') : '0.0.0';
        [$major, $minor, $patch] = array_map('intval', explode('.', $current));

        $range = $last !== '' ? ["{$last}..HEAD"] : ['HEAD'];
        $log = $this->git(['log', '--reverse', ...$range, '--format=%B%x1e']);

        $bumped = false;
        foreach (array_filter(array_map('trim', explode("", $log))) as $message) {
            $bump = $this->bumpFor($message);
            if ($bump === null) {
                continue;
            }
            $bumped = true;
            [$major, $minor, $patch] = match ($bump) {
                'major' => [$major + 1, 0, 0],
                'minor' => [$major, $minor + 1, 0],
                default => [$major, $minor, $patch + 1],
            };
        }

        if (! $bumped) {
            $this->info("No releasable commits since {$current}.");

            return self::SUCCESS;
        }

        $next = "{$major}.{$minor}.{$patch}";

        $this->line("{$current} -> {$next}");

        if ($this->option('tag')) {
            $this->git(['tag', "v{$next}"]);
            $this->info("Created tag v{$next}");
        }

        return self::SUCCESS;
    }

    private function bumpFor(string $message): ?string
    {
        if (preg_match('/^\w+(\([^)]*\))?!:/', $message) || preg_match('/^BREAKING[ -]CHANGE:/m', $message)) {
            return 'major';
        }

        return match (true) {
            (bool) preg_match('/^feat(\([^)]*\))?:/', $message) => 'minor',
            (bool) preg_match('/^fix(\([^)]*\))?:/', $message) => 'patch',
            default => null,
        };
    }

    /** @param  list<string>  $args */
    private function git(array $args, bool $mustSucceed = true): string
    {
        $process = new Process(['git', ...$args], base_path());
        $process->run();

        if ($mustSucceed && ! $process->isSuccessful()) {
            throw new \RuntimeException(trim($process->getErrorOutput()));
        }

        return $process->isSuccessful() ? $process->getOutput() : '';
    }
}
