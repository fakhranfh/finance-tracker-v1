<?php

return [
    'pages' => [
        'debt' => [
            'sub_debts' => 'Sub debts',
            'title' => 'Manage Debts',
            'subtitle' => 'View, create, edit, and remove money you owe to others.',
            'create' => 'Add debt',
            'edit' => 'Edit debt',
            'back' => 'Back to debts',
        ],
        'receivable' => [
            'sub_debts' => 'Sub receivables',
            'title' => 'Manage Receivables',
            'subtitle' => 'View, create, edit, and remove money others owe you.',
            'create' => 'Add receivable',
            'edit' => 'Edit receivable',
            'back' => 'Back to receivables',
        ],
    ],

    'table' => [
        'name' => 'Name',
        'amount' => 'Amount',
        'paid_amount' => 'Paid Amount',
        'due_date' => 'Due Date',
        'notes' => 'Notes',
        'is_paid' => 'Is Paid',
        'created_at' => 'Created',
        'actions' => 'Actions',
        'empty' => 'No debts found.',
        'search' => 'Search by name or notes...',
        'per_page' => 'Items per page',
        'selected' => 'selected',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'select_all_matching' => 'Select all :total debts',
        'all_selected' => 'All :total debts selected.',
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

    'types' => [
        'debt' => 'Debt (I owe)',
        'receivable' => 'Receivable (owed to me)',
    ],

    'actions' => [
        'payments' => 'Payments',
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Are you sure you want to delete this debt?',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected debts?',
        'generate' => 'Generate',
        'generate_count' => 'Number of debts to generate',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Debt created successfully.',
        'updated' => 'Debt updated successfully.',
        'deleted' => 'Debt deleted successfully.',
        'generated' => 'Fake debts generated successfully.',
    ],
];
