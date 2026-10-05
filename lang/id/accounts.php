<?php

return [
    'title' => 'Kelola Akun',
    'subtitle' => 'Lihat, tambah, ubah, dan hapus akun.',
    'create' => 'Tambah akun',
    'edit' => 'Ubah akun',
    'back' => 'Kembali ke daftar akun',

    'table' => [
        'name' => 'Nama',
        'initial_balance' => 'Saldo awal',
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
        'initial_balance' => 'Saldo awal',
        'balance' => 'Saldo saat ini',
    ],

    'adjust_by' => [
        'label' => 'Cara mengubah saldo',
        'initial_balance' => 'Ubah saldo awal',
        'record' => 'Sesuaikan lewat catatan',
        'initial_balance_help' => 'Mengubah saldo awal. Catatan yang ada tetap, jadi saldo saat ini ikut bergeser sebesar selisih yang sama.',
        'record_help' => 'Saldo awal tetap. Sistem menambah catatan pemasukan atau pengeluaran sebesar selisihnya, sehingga riwayat menunjukkan penyesuaian ini.',
    ],

    'adjustment_note' => 'Penyesuaian saldo',

    'actions' => [
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

    'flash' => [
        'created' => 'Akun berhasil ditambahkan.',
        'updated' => 'Akun berhasil diperbarui.',
        'deleted' => 'Akun berhasil dihapus.',
        'generated' => 'Akun palsu berhasil dibuat.',
    ],
];
