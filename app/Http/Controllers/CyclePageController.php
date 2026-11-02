<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class CyclePageController extends Controller
{
    public function index(): View
    {
        return $this->browse('groups', config('category.icons'));
    }

    public function group(int $group): View
    {
        return $this->browse('types', config('cycle.icons'), ['group' => $group]);
    }

    public function type(int $group, int $type): View
    {
        return $this->browse('entries', config('cycle.icons'), ['group' => $group, 'type' => $type]);
    }

    public function show(int $group, int $type, int $id): View
    {
        return view('cycles.show', [
            'id' => $id,
            'group' => $group,
            'type' => $type,
            'labels' => __('cycles.js'),
        ]);
    }

    /**
     * The wording of the edit and delete dialogs for a category (`group`) or an item name (`type`).
     *
     * @return array<string, string>
     */
    private function tileCopy(string $kind): array
    {
        return [
            'edit_title' => __("cycles.form.edit_{$kind}"),
            'delete_title' => __("cycles.form.delete_{$kind}_title"),
            'delete_text' => __("cycles.form.delete_{$kind}_text"),
            'bulk_title' => __("cycles.form.bulk_delete_{$kind}_title"),
            'bulk_text' => __("cycles.form.bulk_delete_{$kind}_text"),
        ];
    }

    /**
     * @param  array<string, string>  $icons
     * @param  array<string, int>  $params
     */
    private function browse(string $level, array $icons, array $params = []): View
    {
        return view('cycles.index', [
            'level' => $level,
            'params' => $params,
            'labels' => __('cycles.js'),
            'icons' => $icons,
            'iconLabels' => collect($icons)->mapWithKeys(fn (string $path, string $icon) => [$icon => __(($level === 'groups' ? 'categories' : 'cycles').".icons.{$icon}")])->all(),
            'defaultIcon' => 'tag',
            'generateCount' => $level === 'entries' ? 6 : 3,
            'perPageOptions' => $level === 'entries' ? [10, 25, 50, 100] : [12, 24, 48, 96],
            'tileTitle' => __($level === 'groups' ? 'cycles.form.add_group' : 'cycles.form.add_type'),
            'tileCopy' => $this->tileCopy($level === 'groups' ? 'group' : 'type'),
        ]);
    }
}
