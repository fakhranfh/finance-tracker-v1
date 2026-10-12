<?php

return [
    'title' => 'Manage Goals',
    'subtitle' => 'Track what you are saving up for.',
    'create' => 'Add goal',
    'edit' => 'Edit goal',
    'back' => 'Back to goals',

    'table' => [
        'name' => 'Name',
        'target_amount' => 'Target Amount',
        'saved_amount' => 'Saved Amount',
        'desired_date' => 'Desired Date',
        'status' => 'Status',
        'color' => 'Color',
        'icon' => 'Icon',
        'note' => 'Note',
        'created_at' => 'Created',
        'actions' => 'Actions',
        'empty' => 'No goals found.',
        'search' => 'Search by name or note...',
        'per_page' => 'Items per page',
        'selected' => 'selected',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'select_all_matching' => 'Select all :total goals',
        'all_selected' => 'All :total goals selected.',
        'clear_selection' => 'Clear selection',
    ],

    'statuses' => [
        'active' => 'Active',
        'paused' => 'Paused',
        'reached' => 'Reached',
    ],

    'fields' => [
        'name' => 'Name',
        'target_amount' => 'Target Amount',
        'saved_amount' => 'Saved Amount',
        'desired_date' => 'Desired Date',
        'status' => 'Status',
        'color' => 'Color',
        'icon' => 'Icon',
        'note' => 'Note',
    ],

    'icons' => [
        'flag' => 'Goal',
        'home' => 'Home',
        'transport' => 'Vehicle',
        'cart' => 'Shopping',
        'briefcase' => 'Work',
        'education' => 'Education',
        'heart' => 'Special',
        'gift' => 'Gift',
    ],

    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Are you sure you want to delete this goal?',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected goals?',
        'generate' => 'Generate',
        'generate_count' => 'Number of goals to generate',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Goal created successfully.',
        'updated' => 'Goal updated successfully.',
        'deleted' => 'Goal deleted successfully.',
        'generated' => 'Fake goals generated successfully.',
        'status_failed' => 'Could not update the goal status. Please try again.',
    ],
];
