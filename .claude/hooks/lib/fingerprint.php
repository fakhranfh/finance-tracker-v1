<?php

// Shared by the Stop hooks: lets a hook skip when the relevant files are
// identical to the last successful run, so it doesn't fire on every prompt.

/** Regex of `git status` lines that make each Stop check run. */
function stop_check_pattern(string $name): string
{
    return [
        'graphify' => '#^\s*\S+\s+"?(app|routes|resources|database|config|tests)/#m',
        'larastan' => '#^\s*\S+\s+"?app/.*\.php"?$#m',
        'pest' =>'#^\s*\S+\s+"?(app|routes|config|lang|database|resources/views|tests/(Feature|Unit)|tests/Pest\.php)(/|$).*\.(php|js)"?$#m',
        'playwright' => '#^\s*\S+\s+"?(?!app/Console/Commands/)(app|routes|config|lang|database|resources/views|resources/js|tests/playwright)/.*\.(php|js)"?$#m',
    ][$name];
}

/**
 * Fingerprint (path + content) of the changed files in `git status` matching $relevant.
 * Returns null when nothing relevant changed.
 */
function relevant_fingerprint(string $relevant): ?string
{
    $parts = [];

    foreach (explode("\n", shell_exec('git status --porcelain -uall') ?: '') as $line) {
        if (! preg_match($relevant, $line)) {
            continue;
        }

        $path = trim(substr($line, 3), '"');
        $path = str_contains($path, ' -> ') ? substr($path, strpos($path, ' -> ') + 4) : $path;
        $parts[] = $path.':'.(is_file($path) ? hash_file('sha1', $path) : 'deleted');
    }

    return $parts === [] ? null : sha1(implode("\n", $parts));
}

function fingerprint_is_unchanged(string $name, string $fingerprint): bool
{
    $file = "storage/framework/cache/hook-{$name}.hash";

    return is_file($file) && trim((string) file_get_contents($file)) === $fingerprint;
}

function remember_fingerprint(string $name, string $fingerprint): void
{
    file_put_contents("storage/framework/cache/hook-{$name}.hash", $fingerprint);
}

/** Show a one-line status to the user (stdout JSON) without blocking the stop. */
function hook_message(string $message): void
{
    echo json_encode(['systemMessage' => $message]);
}

/** Normalize a name for loose matching: lowercase, letters/digits only, no plural "s". */
function test_match_key(string $name): string
{
    $name = preg_replace('/(Controller|Service|Repository|Request|Resource|Policy|Test|\.spec|\.blade|\.php|\.js|\.ts)/i', '', $name);

    return rtrim(strtolower(preg_replace('/[^a-z0-9]/i', '', $name)), 's');
}

/**
 * Test files under $dir (matching $glob) that cover the changed files in `git status`:
 * changed test files themselves, plus tests whose name matches a changed file's name or folder.
 *
 * @return list<string>
 */
function affected_tests(string $dir, string $glob): array
{
    $changed = [];

    foreach (explode("\n", shell_exec('git status --porcelain -uall') ?: '') as $line) {
        if (strlen($line) > 3 && $line[0] !== 'D' && $line[1] !== 'D') {
            $path = trim(substr($line, 3), '"');
            $changed[] = str_contains($path, ' -> ') ? substr($path, strpos($path, ' -> ') + 4) : $path;
        }
    }

    $tests = glob("{$dir}/{$glob}") ?: [];
    $keys = [];

    foreach ($changed as $path) {
        if (str_starts_with($path, $dir.'/')) {
            continue;
        }

        $segments = explode('/', $path);
        $segments = array_slice($segments, in_array($segments[0], ['app', 'resources'], true) ? 1 : 0);

        foreach ($segments as $segment) {
            $key = test_match_key($segment);

            if (strlen($key) >= 4 && ! in_array($key, ['http', 'controllers', 'view', 'views', 'component', 'components', 'index', 'layout', 'layouts', 'livewire', 'model', 'models', 'api', 'lang', 'config', 'route', 'routes', 'database', 'migration', 'migrations'], true)) {
                $keys[] = $key;
            }
        }
    }

    $matched = [];

    foreach ($tests as $test) {
        $testKey = test_match_key(basename($test));
        $isChanged = in_array($test, $changed, true);
        $isRelated = strlen($testKey) >= 4 && array_filter($keys, fn ($k) => str_contains($testKey, $k) || str_contains($k, $testKey));

        if ($isChanged || $isRelated) {
            $matched[] = $test;
        }
    }

    return $matched;
}

/** Show a Windows desktop toast (no-op on other platforms). */
function desktop_toast(string $title, string $body): void
{
    if (PHP_OS_FAMILY !== 'Windows') {
        return;
    }

    $script = <<<'PS'
[Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications, ContentType = WindowsRuntime] | Out-Null
$xml = [Windows.UI.Notifications.ToastNotificationManager]::GetTemplateContent('ToastText02')
$text = $xml.GetElementsByTagName('text')
$text.Item(0).AppendChild($xml.CreateTextNode($env:HOOK_TITLE)) | Out-Null
$text.Item(1).AppendChild($xml.CreateTextNode($env:HOOK_BODY)) | Out-Null
$appId = '{1AC14E77-02E7-4E5D-B744-2EB1AE5198B7}\WindowsPowerShell\v1.0\powershell.exe'
[Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier($appId).Show([Windows.UI.Notifications.ToastNotification]::new($xml))
PS;

    putenv('HOOK_TITLE='.$title);
    putenv('HOOK_BODY='.$body);
    exec('powershell -NoProfile -NonInteractive -EncodedCommand '.base64_encode(mb_convert_encoding($script, 'UTF-16LE', 'UTF-8')));
}
