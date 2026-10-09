<?php

return [
    'title' => 'Kelola Akun',
    'subtitle' => 'Lihat, tambah, ubah, dan hapus akun.',
    'create' => 'Tambah akun',
    'edit' => 'Ubah akun',
    'back' => 'Kembali ke daftar akun',

    'table' => [
        'name' => 'Nama',
        'balance' => 'Saldo',
        'created_at' => 'Dibuat',
        'actions' => 'Aksi',
        'empty' => 'Belum ada akun.',
        'search' => 'Cari nama...',
        'per_page' => 'Item per halaman',
        'selected' => 'dipilih',
        'show_more' => 'Tampilkan lebih banyak',
        'show_less' => 'Tampilkan lebih sedikit',
        'select_all_matching' => 'Pilih semua :total akun',
        'all_selected' => 'Semua :total akun dipilih.',
        'clear_selection' => 'Batalkan pilihan',
    ],

    'fields' => [
        'name' => 'Nama',
        'balance' => 'Saldo',
    ],

    'adjust_by' => [
        'label' => 'Cara mengubah saldo',
        'record' => 'Sesuaikan lewat catatan',
        'record_help' => 'Menambah catatan pemasukan atau pengeluaran sebesar selisihnya, sehingga perubahan muncul di daftar catatan dan riwayat.',
        'balance' => 'Ubah saldo',
        'balance_help' => 'Mengatur saldo langsung tanpa menambah catatan. Cocok untuk memperbaiki saldo yang salah sejak awal; riwayat tidak akan menampilkan perubahan ini.',
    ],

    'adjustment_note' => 'Penyesuaian saldo',

    'appearance' => [
        'title' => 'Ubah warna dan ikon',
        'color' => 'Warna',
        'icon' => 'Ikon',
        'icons' => [
            'cash' => 'Tunai',
            'wallet' => 'Dompet',
            'bank' => 'Bank',
            'sprout' => 'Tabungan',
        ],
    ],

    'order' => [
        'start' => 'Atur urutan',
        'move_up' => 'Naikkan',
        'move_down' => 'Turunkan',
        'title' => 'Atur urutan akun',
    ],

    'actions' => [
        'view' => 'Lihat',
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'confirm_delete' => 'Yakin ingin menghapus akun ini? Semua catatan yang terkait juga akan terhapus.',
        'delete_selected' => 'Hapus yang dipilih',
        'confirm_bulk_delete' => 'Yakin ingin menghapus akun yang dipilih? Semua catatan yang terkait juga akan terhapus.',
        'generate' => 'Generate',
        'generate_count' => 'Jumlah akun yang akan dibuat',
        'autofill' => 'Isi otomatis',
    ],

    'summary' => [
        'title' => 'Ringkasan akun',
        'not_found' => 'Akun ini tidak ditemukan.',
        'subtitle' => 'Pemasukan, pengeluaran, dan transfer untuk akun ini.',
        'range' => 'Periode',
        'from' => 'Dari',
        'to' => 'Sampai',
        'apply' => 'Terapkan',
        'presets' => [
            'this_month' => 'Bulan ini',
            'last_month' => 'Bulan lalu',
            'last_30_days' => '30 hari terakhir',
            'this_year' => 'Tahun ini',
            'all' => 'Semua waktu',
            'custom' => 'Rentang kustom',
        ],
        'income' => 'Total pemasukan',
        'expense' => 'Total pengeluaran',
        'transfer' => 'Transfer',
        'transfer_in' => 'Masuk',
        'transfer_out' => 'Keluar',
        'net' => 'Selisih',
        'records' => ':count catatan',
        'records_title' => 'Catatan',
    ],

    'flash' => [
        'created' => 'Akun berhasil ditambahkan.',
        'updated' => 'Akun berhasil diperbarui.',
        'deleted' => 'Akun berhasil dihapus.',
        'generated' => 'Akun palsu berhasil dibuat.',
    ],
];
