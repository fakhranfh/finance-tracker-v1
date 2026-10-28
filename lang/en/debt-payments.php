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
            'edit' => 'Edit payment',
            'existing' => 'Use an existing record',
            'existing_hint' => 'Pick an expense record that does not repay another debt yet.',
        ],
        'receivable' => [
            'title' => 'Payments received',
            'subtitle' => 'The records that settle this receivable.',
            'back' => 'Back to receivables',
            'back_to_payments' => 'Back to payments received',
            'create' => 'Add payment received',
            'edit' => 'Edit payment received',
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
        'edit' => 'Edit payment',
        'remove' => 'Remove payment',
        'confirm_remove' => 'Remove this payment? A record saved for it is kept.',
        'use' => 'Use this record',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'Payment added successfully.',
        'updated' => 'Payment updated successfully.',
        'used' => 'Record added as a payment.',
        'removed' => 'Payment removed. Its record, if any, is kept.',
    ],

    'record_prompt' => [
        'title' => 'Save as a record too?',
        'body' => 'Also save this payment as a record on the chosen account? The account balance only changes if you do.',
        'yes' => 'Yes, create record',
        'no' => 'No, just save',
    ],

    'validation' => [
        'sub_debt_required' => 'Choose which sub debt this payment is for.',
        'exceeds_remaining' => 'This is more than what is still owed (:remaining).',
    ],
];
