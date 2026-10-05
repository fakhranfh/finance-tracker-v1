<?php

return [
    'title' => 'Manage Records',
    'subtitle' => 'Track your income, expenses and transfers between accounts.',
    'create' => 'Add record',
    'edit' => 'Edit record',
    'back' => 'Back to records',
    'no_accounts' => 'You need at least one account before adding a record.',

    'types' => [
        'income' => 'Income',
        'expense' => 'Expense',
        'transfer' => 'Transfer',
    ],

    'table' => [
        'recorded_at' => 'Date',
        'type' => 'Type',
        'account' => 'Account',
        'amount' => 'Amount',
        'note' => 'Note',
        'actions' => 'Actions',
        'empty' => 'No records found.',
        'search' => 'Search by note or account...',
        'per_page' => 'Items per page',
        'selected' => 'selected',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'select_all_matching' => 'Select all :total records',
        'all_selected' => 'All :total records selected.',
        'clear_selection' => 'Clear selection',
    ],

    'filters' => [
        'type' => 'Type',
        'all_types' => 'All types',
        'account' => 'Account',
        'all_accounts' => 'All accounts',
        'accounts_selected' => ':count accounts selected',
        'search_accounts' => 'Search accounts...',
        'select_all' => 'Select all',
        'deselect_all' => 'Deselect all',
        'date_from' => 'From date',
        'date_to' => 'To date',
    ],

    'fields' => [
        'type' => 'Type',
        'account' => 'Account',
        'from_account' => 'From account',
        'to_account' => 'To account',
        'select_account' => 'Select an account',
        'amount' => 'Amount',
        'note' => 'Note',
        'recorded_at' => 'Date',
    ],

    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Are you sure you want to delete this record?',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected records?',
        'generate' => 'Generate',
        'generate_count' => 'Number of records to generate',
        'generate_accounts' => 'Accounts to generate records for',
        'generate_accounts_summary' => 'Accounts (:count selected)',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Record created successfully.',
        'updated' => 'Record updated successfully.',
        'deleted' => 'Record deleted successfully.',
        'generated' => 'Fake records generated successfully.',
    ],
];
