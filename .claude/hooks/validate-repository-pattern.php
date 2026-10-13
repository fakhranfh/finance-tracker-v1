<?php

/**
 * PostToolUse hook: validates Controllers, Repositories, and Services against
 * docs/REPOSITORY_PATTERN.md after they are written or edited. A repository must wrap
 * exactly one model: static calls on another model and `$model->relation()->write()`
 * chains are reported. A service must not query (models, `DB::`, relation chains) or open
 * transactions itself; atomic work goes through a repository's `transaction()`.
 *
 * Reads the Claude Code hook payload (tool_name/tool_input JSON) from STDIN,
 * static-checks the edited file if it lives under app/Http/Controllers,
 * app/Repositories, or app/Services, and prints a hook-JSON response with
 * any violations found.
 */

$stdin = stream_get_contents(STDIN);
$payload = json_decode($stdin, true) ?? [];

$filePath = $payload['tool_input']['file_path']
    ?? $payload['tool_response']['filePath']
    ?? null;

if (! $filePath || ! is_string($filePath) || ! str_ends_with($filePath, '.php') || ! is_file($filePath)) {
    exit(0);
}

$normalized = str_replace('\\', '/', $filePath);
$code = file_get_contents($filePath);

$violations = [];

if (str_contains($normalized, '/app/Http/Controllers/')) {
    $violations = validateController($code);
} elseif (str_contains($normalized, '/app/Livewire/')) {
    $violations = validateLivewire($code);
} elseif (str_contains($normalized, '/app/Repositories/')) {
    $violations = validateRepository($code, $normalized);
} elseif (str_contains($normalized, '/app/Services/')) {
    $violations = validateService($code);
} else {
    exit(0);
}

if (empty($violations)) {
    exit(0);
}

$list = implode("\n", array_map(fn ($v) => "- {$v}", $violations));
$message = "Repository Pattern check (docs/REPOSITORY_PATTERN.md) found issues in {$filePath}:\n{$list}";

echo json_encode([
    'systemMessage' => $message,
    'hookSpecificOutput' => [
        'hookEventName' => 'PostToolUse',
        'additionalContext' => $message,
    ],
]);

exit(0);

/**
 * @return array<int, string>
 */
function validateController(string $code): array
{
    $violations = [];

    foreach (constructorParamTypes($code) as $type) {
        if (preg_match('/(Repository|RepositoryInterface)$/', $type)) {
            $violations[] = "Controller constructor injects `{$type}` directly; controllers must depend on a Service, not a Repository.";
        }
    }

    foreach (directModelCalls($code) as [$class, $method]) {
        $violations[] = "Controller calls `{$class}::{$method}(...)` directly; data access must go through a Service.";
    }

    return array_unique($violations);
}

/**
 * @return array<int, string>
 */
function validateLivewire(string $code): array
{
    $violations = [];

    foreach (constructorParamTypes($code) as $type) {
        if (preg_match('/(Repository|RepositoryInterface)$/', $type)) {
            $violations[] = "Livewire component injects `{$type}` directly; per rule 9, Livewire components are treated like Controllers and must depend on a Service, not a Repository.";
        }
    }

    foreach (methodParamTypes($code) as $type) {
        if (preg_match('/(Repository|RepositoryInterface)$/', $type)) {
            $violations[] = "Livewire method injects `{$type}` directly; per rule 9, Livewire components are treated like Controllers and must depend on a Service, not a Repository.";
        }
    }

    foreach (directModelCalls($code) as [$class, $method]) {
        $violations[] = "Livewire component calls `{$class}::{$method}(...)` directly; per rule 9, data access must go through a Service, just like a Controller.";
    }

    return array_unique($violations);
}

/**
 * @return array<int, string>
 */
function validateRepository(string $code, string $normalizedPath): array
{
    $violations = [];

    $className = extractClassName($code);

    if ($className === null) {
        return $violations;
    }

    $isInterface = str_ends_with($className, 'RepositoryInterface');
    $isConcrete = str_ends_with($className, 'Repository') && ! $isInterface;

    if ($isConcrete) {
        if (! preg_match('/class\s+' . preg_quote($className, '/') . '\s+implements\s+(\w+)/', $code, $m)) {
            $violations[] = "`{$className}` does not `implements` a *RepositoryInterface; every repository must implement its interface contract.";
        } elseif (! str_ends_with($m[1], 'RepositoryInterface')) {
            $violations[] = "`{$className}` implements `{$m[1]}`, which is not a *RepositoryInterface.";
        }

        $expectedModel = preg_replace('/Repository$/', '', $className);

        foreach (directModelCalls($code) as [$class, $method]) {
            if ($class !== $expectedModel && $class !== 'DB' && $class !== 'self' && $class !== 'static') {
                $violations[] = "`{$className}` calls `{$class}::{$method}(...)`, but a repository must only touch its own model (`{$expectedModel}`) per rule 7 — cross-entity orchestration belongs in the Service layer.";
            }
        }

        if (preg_match_all('/^use\s+App\\\\Models\\\\(\w+);/m', $code, $imports)) {
            foreach ($imports[1] as $model) {
                if ($model !== $expectedModel) {
                    $violations[] = "`{$className}` imports the `{$model}` model, but a repository wraps exactly one model (`{$expectedModel}`); take a plain id (e.g. `int \$goalId`) instead of the other model and let a Service pass it (\"One model per repository\" in .ai/rules/repository-pattern.md).";
                }
            }
        }

        foreach (relationChainCalls($code) as [$relation, $method]) {
            $violations[] = "`{$className}` calls `->{$relation}()->{$method}(...)`, which queries or writes a related model through its relation; a repository wraps exactly one model (`{$expectedModel}`) — move that to the related model's own repository and orchestrate both from a Service inside `DB::transaction()` (\"One model per repository\" in .ai/rules/repository-pattern.md).";
        }
    }

    return array_unique($violations);
}

/**
 * @return array<int, string>
 */
function validateService(string $code): array
{
    $violations = [];

    foreach (constructorParamTypes($code) as $type) {
        if (preg_match('/Repository$/', $type) && ! str_ends_with($type, 'RepositoryInterface')) {
            $violations[] = "Service constructor depends on the concrete `{$type}` instead of its `{$type}Interface`; depend on the interface (rule 6).";
        }
    }

    foreach (directModelCalls($code) as [$class, $method]) {
        if ($class === 'DB') {
            $violations[] = "Service calls `DB::{$method}(...)` directly; query building must live in the Repository (rule 9).";
        } else {
            $violations[] = "Service calls `{$class}::{$method}(...)` directly on a model; query building must live in the Repository, not the Service (rule 9).";
        }
    }

    if (preg_match_all('/\bDB::(\w+)\s*\(/', $code, $dbCalls)) {
        foreach ($dbCalls[1] as $method) {
            $violations[] = "Service calls `DB::{$method}(...)`; services never touch the database layer, not even for transactions — wrap atomic work in a repository method (e.g. `\$this->xRepository->transaction(fn () => ...)` from the `RunsTransactions` trait) (\"No queries or transactions in services\" in .ai/rules/repository-pattern.md).";
        }
    }

    foreach (relationChainCalls($code) as [$relation, $method]) {
        $violations[] = "Service calls `->{$relation}()->{$method}(...)`, a query through a model relation; query building must live in the Repository (\"No queries or transactions in services\" in .ai/rules/repository-pattern.md).";
    }

    return array_unique($violations);
}

/**
 * @return array<int, string>
 */
function constructorParamTypes(string $code): array
{
    if (! preg_match('/function\s+__construct\s*\(([^)]*)\)/s', $code, $m)) {
        return [];
    }

    preg_match_all('/(?:public|protected|private)?\s*(?:readonly\s+)?(?:\?)?([A-Z][A-Za-z0-9_\\\\]*)\s+\$\w+/', $m[1], $matches);

    return array_map(fn ($t) => basename(str_replace('\\', '/', $t)), $matches[1]);
}

/**
 * Collects param types from every method (not just __construct), e.g. Livewire
 * action methods and render() that type-hint a Repository via method injection.
 *
 * @return array<int, string>
 */
function methodParamTypes(string $code): array
{
    preg_match_all('/function\s+\w+\s*\(([^)]*)\)/s', $code, $methods);

    $types = [];
    foreach ($methods[1] as $params) {
        preg_match_all('/(?:public|protected|private)?\s*(?:readonly\s+)?(?:\?)?([A-Z][A-Za-z0-9_\\\\]*)\s+\$\w+/', $params, $matches);
        foreach ($matches[1] as $type) {
            $types[] = basename(str_replace('\\', '/', $type));
        }
    }

    return $types;
}

/**
 * Finds `ClassName::method(` calls where ClassName looks like an Eloquent
 * model or the DB facade, ignoring `self::`, `static::`, `parent::`.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function directModelCalls(string $code): array
{
    $queryMethods = ['query', 'where', 'whereHas', 'create', 'find', 'findOrFail', 'update', 'delete', 'destroy', 'with', 'all', 'firstOrFail', 'first', 'table'];

    preg_match_all('/\b([A-Z][A-Za-z0-9_]*)::(' . implode('|', $queryMethods) . ')\s*\(/', $code, $matches, PREG_SET_ORDER);

    $calls = [];
    foreach ($matches as $match) {
        $class = $match[1];
        if (in_array($class, ['self', 'static', 'parent'], true)) {
            continue;
        }
        $calls[] = [$class, $match[2]];
    }

    return $calls;
}

/**
 * Finds `$model->relation()->increment(...)`-style chains that query or write a related model
 * through its relation. `$this`, `$query`, `$q` and `$builder` are skipped, as are query
 * builder helpers such as `->latest()->get()`.
 *
 * @return array<int, array{0: string, 1: string}>
 */
function relationChainCalls(string $code): array
{
    $builderHelpers = ['latest', 'oldest', 'distinct', 'lockForUpdate', 'withTrashed', 'onlyTrashed', 'newQuery', 'toBase', 'getQuery'];
    $chained = ['increment', 'decrement', 'create', 'createMany', 'update', 'delete', 'forceDelete', 'insert', 'save', 'saveMany', 'updateOrCreate', 'firstOrCreate', 'attach', 'detach', 'sync', 'where', 'whereIn', 'get', 'first', 'find', 'exists', 'count', 'sum', 'pluck'];

    preg_match_all('/\$(\w+)->(\w+)\(\)->(' . implode('|', $chained) . ')\s*\(/', $code, $matches, PREG_SET_ORDER);

    $calls = [];
    foreach ($matches as $match) {
        if (in_array($match[1], ['this', 'query', 'q', 'builder'], true) || in_array($match[2], $builderHelpers, true)) {
            continue;
        }
        $calls[] = [$match[2], $match[3]];
    }

    return $calls;
}

function extractClassName(string $code): ?string
{
    if (preg_match('/\b(?:class|interface)\s+(\w+)/', $code, $m)) {
        return $m[1];
    }

    return null;
}
