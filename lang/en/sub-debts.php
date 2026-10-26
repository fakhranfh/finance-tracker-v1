<?php

return [
    'create' => 'Add sub debt',
    'summary' => ':paid of :total sub debts paid · :paid_amount of :amount',

    'pages' => [
        'debt' => [
            'create' => 'Add sub debt',
            'edit_item' => 'Edit sub debt',
            'edit' => 'Edit sub debts',
            'back_to_list' => 'Back to sub debts',
            'title' => 'Sub debts',
            'subtitle' => 'The instalments of this debt.',
            'back' => 'Back to debts',
        ],
        'receivable' => [
            'create' => 'Add sub receivable',
            'edit_item' => 'Edit sub receivable',
            'edit' => 'Edit sub receivables',
            'back_to_list' => 'Back to sub receivables',
            'title' => 'Sub receivables',
            'subtitle' => 'The instalments of this receivable.',
            'back' => 'Back to receivables',
        ],
    ],

    'mode' => [
        'title' => 'Amount',
        'amount' => 'A single amount',
        'sub_debts' => 'Split into sub debts',
        'hint' => 'With sub debts, the amount, the paid amount and the paid status follow the sub debts.',
    ],

    'table' => [
        'name' => 'Name',
        'amount' => 'Amount',
        'paid_amount' => 'Paid Amount',
        'due_date' => 'Due Date',
        'is_paid' => 'Status',
        'paid' => 'Paid',
        'unpaid' => 'Not paid',
        'note' => 'Note',
        'actions' => 'Actions',
        'empty' => 'No sub debts found.',
        'search' => 'Search by name or note...',
        'per_page' => 'Items per page',
        'show_more' => 'Show more',
        'selected' => 'selected',
        'select_all_matching' => 'Select all :total sub debts',
        'all_selected' => 'All :total sub debts selected.',
        'clear_selection' => 'Clear selection',
        'show_less' => 'Show less',
    ],

    'fields' => [
        'same_due_date' => 'Same as the main due date',
        'name' => 'Name',
        'amount' => 'Amount',
        'paid_amount' => 'Paid Amount',
        'due_date' => 'Due Date',
        'note' => 'Note',
    ],

    'form' => [
        'title' => 'Sub debts',
        'hint' => 'Break the amount down into instalments. Their total becomes the amount, and what has been paid becomes the paid amount.',
        'edit' => 'Edit sub debts',
        'remove' => 'Remove',
        'totals' => 'Sub debts total :amount, paid :paid',
    ],

    'actions' => [
        'delete' => 'Delete',
        'confirm_delete' => 'Are you sure you want to delete this sub debt?',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected sub debts?',
        'generate' => 'Generate',
        'generate_count' => 'Number of sub debts to generate',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'autofill' => 'Autofill',
        'edit' => 'Edit',
    ],

    'flash' => [
        'created' => 'Sub debt created successfully.',
        'updated_item' => 'Sub debt updated successfully.',
        'generated' => 'Fake sub debts generated successfully.',
        'updated' => 'Sub debts saved successfully.',
        'status_failed' => 'Could not update the sub debt. Please try again.',
    ],
];
