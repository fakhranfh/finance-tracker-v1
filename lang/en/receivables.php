<?php

return [
    'title' => 'Manage Receivables',
    'subtitle' => 'View, create, edit, and remove receivables.',
    'create' => 'Add receivable',
    'edit' => 'Edit receivable',
    'back' => 'Back to receivables',

    'table' => [
        'name' => 'Name',
        'amount' => 'Amount',
        'paid_amount' => 'Paid Amount',
        'due_date' => 'Due Date',
        'notes' => 'Notes',
        'is_paid' => 'Is Paid',
        'created_at' => 'Created',
        'actions' => 'Actions',
        'empty' => 'No receivables found.',
        'search' => 'Search by name or notes...',
        'per_page' => 'Items per page',
        'selected' => 'selected',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'select_all_matching' => 'Select all :total receivables',
        'all_selected' => 'All :total receivables selected.',
        'clear_selection' => 'Clear selection',
    ],

    'fields' => [
        'name' => 'Name',
        'amount' => 'Amount',
        'paid_amount' => 'Paid Amount',
        'due_date' => 'Due Date',
        'notes' => 'Notes',
        'is_paid' => 'Is Paid',
    ],

    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Are you sure you want to delete this receivable?',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected receivables?',
        'generate' => 'Generate',
        'generate_count' => 'Number of receivables to generate',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Receivable created successfully.',
        'updated' => 'Receivable updated successfully.',
        'deleted' => 'Receivable deleted successfully.',
        'generated' => 'Fake receivables generated successfully.',
    ],
];
