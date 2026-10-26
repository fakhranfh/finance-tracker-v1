<?php

return [
    'summary' => 'Paid :paid of :amount · :remaining remaining',

    'pages' => [
        'debt' => [
            'title' => 'Payments',
            'subtitle' => 'The records that repay this debt.',
            'back' => 'Back to debts',
            'back_to_payments' => 'Back to payments',
            'create' => 'Add payment',
            'existing' => 'Use an existing record',
            'existing_hint' => 'Pick an expense record that does not repay another debt yet.',
        ],
        'receivable' => [
            'title' => 'Payments received',
            'subtitle' => 'The records that settle this receivable.',
            'back' => 'Back to receivables',
            'back_to_payments' => 'Back to payments received',
            'create' => 'Add payment received',
            'existing' => 'Use an existing record',
            'existing_hint' => 'Pick an income record that does not settle another receivable yet.',
        ],
    ],

    'table' => [
        'recorded_at' => 'Date',
        'account' => 'Account',
        'category' => 'Category',
        'amount' => 'Amount',
        'note' => 'Note',
        'sub_debt' => 'Sub debt',
        'actions' => 'Actions',
        'empty' => 'No payments yet.',
        'search' => 'Search by note, account or category...',
        'per_page' => 'Items per page',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
    ],

    'existing' => [
        'empty' => 'No matching records are available.',
    ],

    'fields' => [
        'sub_debt' => 'Sub debt',
        'select_sub_debt' => 'Select a sub debt',
        'account' => 'Account',
        'select_account' => 'Select an account',
        'category' => 'Category',
        'no_category' => 'No category',
        'amount' => 'Amount',
        'recorded_at' => 'Date',
        'note' => 'Note',
    ],

    'actions' => [
        'open_record' => 'Open record',
        'remove' => 'Remove payment',
        'confirm_remove' => 'Stop counting this record as a payment? The record itself is kept.',
        'use' => 'Use this record',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Payment added successfully.',
        'used' => 'Record added as a payment.',
        'removed' => 'Payment removed. The record is kept.',
    ],

    'validation' => [
        'sub_debt_required' => 'Choose which sub debt this payment is for.',
        'exceeds_remaining' => 'This is more than what is still owed (:remaining).',
    ],
];
