<?php

return [
    'title' => 'Kelola Pengguna',
    'subtitle' => 'Lihat, tambah, ubah, dan hapus akun pengguna.',
    'create' => 'Tambah pengguna',
    'edit' => 'Ubah pengguna',
    'back' => 'Kembali ke daftar pengguna',

    'table' => [
        'name' => 'Nama',
        'email' => 'Email',
        'role' => 'Peran',
        'created_at' => 'Bergabung',
        'actions' => 'Aksi',
        'empty' => 'Belum ada pengguna.',
        'search' => 'Cari nama atau email...',
        'per_page' => 'Item per halaman',
        'selected' => 'dipilih',
        'show_more' => 'Tampilkan lebih banyak',
        'show_less' => 'Tampilkan lebih sedikit',
        'select_all_matching' => 'Pilih semua :total pengguna',
        'all_selected' => 'Semua :total pengguna dipilih.',
        'clear_selection' => 'Batalkan pilihan',
    ],

    'fields' => [
        'name' => 'Nama',
        'email' => 'Email',
        'role' => 'Peran',
        'password' => 'Kata sandi',
        'password_confirmation' => 'Konfirmasi kata sandi',
        'password_hint' => 'Biarkan kosong untuk mempertahankan kata sandi saat ini.',
    ],

    'roles' => [
        'admin' => 'Admin',
        'user' => 'Pengguna',
    ],

    'errors' => [
        'delete_self' => 'Anda tidak dapat menghapus akun Anda sendiri.',
    ],

    'actions' => [
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'confirm_delete' => 'Yakin ingin menghapus pengguna ini?',
        'delete_selected' => 'Hapus yang dipilih',
        'confirm_bulk_delete' => 'Yakin ingin menghapus pengguna yang dipilih?',
        'generate' => 'Generate',
        'generate_count' => 'Jumlah pengguna yang akan dibuat',
        'autofill' => 'Isi otomatis',
    ],

    'flash' => [
        'created' => 'Pengguna berhasil ditambahkan.',
        'updated' => 'Pengguna berhasil diperbarui.',
        'deleted' => 'Pengguna berhasil dihapus.',
        'generated' => 'Pengguna palsu berhasil dibuat.',
    ],
];
