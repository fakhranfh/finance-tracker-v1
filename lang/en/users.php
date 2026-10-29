<?php

return [
    'title' => 'Manage Users',
    'subtitle' => 'View, create, edit, and remove user accounts.',
    'create' => 'Add user',
    'edit' => 'Edit user',
    'back' => 'Back to users',

    'table' => [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'created_at' => 'Joined',
        'actions' => 'Actions',
        'empty' => 'No users found.',
        'search' => 'Search by name or email...',
        'per_page' => 'Items per page',
        'selected' => 'selected',
        'show_more' => 'Show more',
        'show_less' => 'Show less',
        'select_all_matching' => 'Select all :total users',
        'all_selected' => 'All :total users selected.',
        'clear_selection' => 'Clear selection',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email',
        'role' => 'Role',
        'password' => 'Password',
        'password_confirmation' => 'Confirm password',
        'password_hint' => 'Leave blank to keep the current password.',
    ],

    'roles' => [
        'admin' => 'Admin',
        'user' => 'User',
    ],

    'errors' => [
        'delete_self' => 'You cannot delete your own account.',
    ],

    'actions' => [
        'edit' => 'Edit',
        'delete' => 'Delete',
        'save' => 'Save',
        'cancel' => 'Cancel',
        'confirm_delete' => 'Are you sure you want to delete this user?',
        'delete_selected' => 'Delete selected',
        'confirm_bulk_delete' => 'Are you sure you want to delete the selected users?',
        'generate' => 'Generate',
        'generate_count' => 'Number of users to generate',
        'autofill' => 'Autofill',
    ],

    'flash' => [
        'created' => 'User created successfully.',
        'updated' => 'User updated successfully.',
        'deleted' => 'User deleted successfully.',
        'generated' => 'Fake users generated successfully.',
    ],
];
