<?php

return [
    'title' => 'Manage Accounts',
    'subtitle' => 'View, create, edit, and remove accounts.',
    'create' => 'Add account',
    'edit' => 'Edit account',
    'back' => 'Back to accounts',

    'table' => [
        'name' => 'Name',
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
        'balance' => 'Balance',
    ],

    'adjust_by' => [
        'label' => 'How to change the balance',
        'record' => 'Adjust by record',
        'record_help' => 'Adds an income or expense record for the difference, so the change appears in the record list and history.',
        'balance' => 'Change balance',
        'balance_help' => 'Sets the balance directly without adding a record. Use it to correct a balance that was wrong from the start; the history will not show this change.',
    ],

    'adjustment_note' => 'Balance adjustment',

    'appearance' => [
        'title' => 'Edit color and icon',
        'color' => 'Color',
        'icon' => 'Icon',
        'icons' => [
            'cash' => 'Cash',
            'wallet' => 'Wallet',
            'bank' => 'Bank',
            'sprout' => 'Savings',
        ],
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
