<?php

return [
    'title' => 'Manage Categories',
    'subtitle' => 'Group your income and expense records by category.',
    'create' => 'Add category',
    'edit' => 'Edit category',
    'back' => 'Back to categories',

    'table' => [
        'name' => 'Name',
        'type' => 'Type',
        'color' => 'Color',
        'icon' => 'Icon',
        'created_at' => 'Created',
        'actions' => 'Actions',
        'empty' => 'No categories found.',
        'search' => 'Search by name...',
        'per_page' => 'Items per page',
        'selected' => 'selected',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'select_all_matching' => 'Select all :total categories',
        'all_selected' => 'All :total categories selected.',
        'clear_selection' => 'Clear selection',
    ],

    'fields' => [
        'name' => 'Name',
        'type' => 'Type',
        'color' => 'Color',
        'icon' => 'Icon',
    ],

    'icons' => [
        'tag' => 'Tag',
        'food' => 'Food',
        'transport' => 'Transport',
        'home' => 'Home',
        'cart' => 'Shopping',
        'heart' => 'Health',
        'briefcase' => 'Work',
        'gift' => 'Gift',
    ],

    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Are you sure you want to delete this category? Its records will be kept without a category.',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected categories? Their records will be kept without a category.',
        'generate' => 'Generate',
        'generate_count' => 'Number of categories to generate',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Category created successfully.',
        'updated' => 'Category updated successfully.',
        'deleted' => 'Category deleted successfully.',
        'generated' => 'Fake categories generated successfully.',
    ],
];
