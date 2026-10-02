@props(['class' => '', 'rows' => 5, 'headers' => []])

<div {{ $attributes->merge(['class' => $class]) }}>
<div class="space-y-3 pb-20 sm:hidden">
    @for ($i = 0; $i < $rows; $i++)
        <div class="rounded-lg border border-outline-variant bg-surface py-1">
            <div class="flex items-center justify-between px-4 py-3">
                <x-atoms.skeleton class="h-4 w-4" />
                <div class="flex gap-3">
                    <x-atoms.skeleton class="h-5 w-5" />
                    <x-atoms.skeleton class="h-5 w-5" />
                </div>
            </div>
            @foreach ([['w-12', 'w-32'], ['w-12', 'w-20'], ['w-12', 'w-48'], ['w-12', 'w-20'], ['w-12', 'w-24']] as [$labelWidth, $valueWidth])
                <div class="flex items-center justify-between gap-4 px-4 py-2.5">
                    <x-atoms.skeleton class="h-3.5 {{ $labelWidth }}" />
                    <x-atoms.skeleton class="h-4 {{ $valueWidth }}" />
                </div>
            @endforeach
        </div>
    @endfor
</div>

<div class="overflow-hidden rounded-lg border border-outline-variant bg-surface max-sm:hidden">
    <table class="w-full text-left font-body text-body-md">
        <thead class="border-b border-outline-variant bg-surface-container text-body-sm text-on-surface-variant">
            <tr>
                <th class="w-10 px-4 py-3">
                    <x-atoms.skeleton class="h-4 w-4" />
                </th>
                @foreach ($headers as $header)
                    <th class="px-4 py-3 font-medium">
                        @if (is_array($header))
                            <span class="inline-flex items-center gap-1">
                                {{ $header['label'] }}
                                <span class="inline-block h-3.5 w-3.5"></span>
                            </span>
                        @else
                            {{ $header }}
                        @endif
                    </th>
                @endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-outline-variant">
            @for ($i = 0; $i < $rows; $i++)
                <tr>
                    <td class="w-10 px-4 py-3"><x-atoms.skeleton class="h-4 w-4" /></td>
                    <td class="px-4 py-3"><x-atoms.skeleton class="h-4 w-32" /></td>
                    <td class="px-4 py-3"><x-atoms.skeleton class="h-4 w-20" /></td>
                    <td class="px-4 py-3"><x-atoms.skeleton class="h-4 w-48" /></td>
                    <td class="px-4 py-3"><x-atoms.skeleton class="h-4 w-20" /></td>
                    <td class="px-4 py-3"><x-atoms.skeleton class="h-4 w-28" /></td>
                    <td class="px-4 py-3"><x-atoms.skeleton class="h-4 w-16" /></td>
                </tr>
            @endfor
        </tbody>
    </table>
</div>
</div>
