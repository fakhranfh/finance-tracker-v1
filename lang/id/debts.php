<?php

return [
    'title' => 'Kelola Utang',
    'subtitle' => 'Lihat, tambah, ubah, dan hapus utang.',
    'create' => 'Tambah utang',
    'edit' => 'Ubah utang',
    'back' => 'Kembali ke daftar utang',

    'table' => [
        'name' => 'Pemberi Utang',
        'amount' => 'Jumlah',
        'paid_amount' => 'Terbayar',
        'due_date' => 'Jatuh Tempo',
        'notes' => 'Catatan',
        'is_paid' => 'Lunas',
        'created_at' => 'Dibuat',
        'actions' => 'Aksi',
        'empty' => 'Belum ada utang.',
        'search' => 'Cari pemberi utang atau catatan...',
        'per_page' => 'Item per halaman',
        'selected' => 'dipilih',
        'show_more' => 'Tampilkan lebih banyak',
        'show_less' => 'Tampilkan lebih sedikit',
        'select_all_matching' => 'Pilih semua :total utang',
        'all_selected' => 'Semua :total utang dipilih.',
        'clear_selection' => 'Batalkan pilihan',
    ],

    'fields' => [
        'name' => 'Pemberi Utang',
        'amount' => 'Jumlah',
        'paid_amount' => 'Terbayar',
        'due_date' => 'Jatuh Tempo',
        'notes' => 'Catatan',
        'is_paid' => 'Lunas',
    ],

    'actions' => [
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'confirm_delete' => 'Yakin ingin menghapus utang ini?',
        'delete_selected' => 'Hapus yang dipilih',
        'confirm_bulk_delete' => 'Yakin ingin menghapus utang yang dipilih?',
        'generate' => 'Generate',
        'generate_count' => 'Jumlah utang yang akan dibuat',
        'autofill' => 'Isi otomatis',
    ],

    'flash' => [
        'created' => 'Utang berhasil ditambahkan.',
        'updated' => 'Utang berhasil diperbarui.',
        'deleted' => 'Utang berhasil dihapus.',
        'generated' => 'Utang palsu berhasil dibuat.',
    ],
];
