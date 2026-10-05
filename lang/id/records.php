<?php

return [
    'title' => 'Kelola Catatan',
    'subtitle' => 'Catat pemasukan, pengeluaran, dan transfer antar akun.',
    'create' => 'Tambah catatan',
    'edit' => 'Ubah catatan',
    'back' => 'Kembali ke daftar catatan',
    'no_accounts' => 'Kamu perlu membuat minimal satu akun sebelum menambah catatan.',

    'types' => [
        'income' => 'Pemasukan',
        'expense' => 'Pengeluaran',
        'transfer' => 'Transfer',
    ],

    'table' => [
        'recorded_at' => 'Tanggal',
        'type' => 'Jenis',
        'account' => 'Akun',
        'amount' => 'Jumlah',
        'note' => 'Catatan',
        'actions' => 'Aksi',
        'empty' => 'Belum ada catatan.',
        'search' => 'Cari catatan atau akun...',
        'per_page' => 'Item per halaman',
        'selected' => 'dipilih',
        'show_more' => 'Tampilkan lebih banyak',
        'show_less' => 'Tampilkan lebih sedikit',
        'select_all_matching' => 'Pilih semua :total catatan',
        'all_selected' => 'Semua :total catatan dipilih.',
        'clear_selection' => 'Batalkan pilihan',
    ],

    'fields' => [
        'type' => 'Jenis',
        'account' => 'Akun',
        'from_account' => 'Dari akun',
        'to_account' => 'Ke akun',
        'select_account' => 'Pilih akun',
        'amount' => 'Jumlah',
        'note' => 'Catatan',
        'recorded_at' => 'Tanggal',
    ],

    'actions' => [
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'confirm_delete' => 'Yakin ingin menghapus catatan ini?',
        'delete_selected' => 'Hapus yang dipilih',
        'confirm_bulk_delete' => 'Yakin ingin menghapus catatan yang dipilih?',
        'generate' => 'Generate',
        'generate_count' => 'Jumlah catatan yang akan dibuat',
        'generate_accounts' => 'Akun yang akan dibuat catatannya',
        'generate_accounts_summary' => 'Akun (:count dipilih)',
        'autofill' => 'Isi otomatis',
    ],

    'flash' => [
        'created' => 'Catatan berhasil ditambahkan.',
        'updated' => 'Catatan berhasil diperbarui.',
        'deleted' => 'Catatan berhasil dihapus.',
        'generated' => 'Catatan palsu berhasil dibuat.',
    ],
];
