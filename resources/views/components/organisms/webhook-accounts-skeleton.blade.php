<div class="contents" data-accounts-skeleton aria-hidden="true">
    @foreach (range(1, 4) as $row)
        <div class="flex items-center gap-2">
            <x-atoms.skeleton class="h-4 w-4" />
            <x-atoms.skeleton class="h-4 w-32" />
        </div>
    @endforeach
</div>
