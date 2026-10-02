<?php

return [
    'title' => 'Manage Accounts',
    'subtitle' => 'View, create, edit, and remove accounts.',
    'create' => 'Add account',
    'edit' => 'Edit account',
    'back' => 'Back to accounts',

    'table' => [
        'name' => 'Name',
        'initial_balance' => 'Initial Balance',
        'balance' => 'Balance',
        'created_at' => 'Created',
        'actions' => 'Actions',
        'empty' => 'No accounts found.',
        'search' => 'Search by name...',
        'per_page' => 'Items per page',
        'selected' => 'selected',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'select_all_matching' => 'Select all :total accounts',
        'all_selected' => 'All :total accounts selected.',
        'clear_selection' => 'Clear selection',
    ],

    'fields' => [
        'name' => 'Name',
        'initial_balance' => 'Initial Balance',
    ],

    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Are you sure you want to delete this account? All records involving it will be deleted too.',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected accounts? All records involving them will be deleted too.',
        'generate' => 'Generate',
        'generate_count' => 'Number of accounts to generate',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Account created successfully.',
        'updated' => 'Account updated successfully.',
        'deleted' => 'Account deleted successfully.',
        'generated' => 'Fake accounts generated successfully.',
    ],
];
