<?php

return [
    'title' => 'Kelola Piutang',
    'subtitle' => 'Lihat, tambah, ubah, dan hapus piutang.',
    'create' => 'Tambah piutang',
    'edit' => 'Ubah piutang',
    'back' => 'Kembali ke daftar piutang',

    'table' => [
        'name' => 'Peminjam',
        'amount' => 'Jumlah',
        'paid_amount' => 'Diterima',
        'due_date' => 'Jatuh Tempo',
        'notes' => 'Catatan',
        'is_paid' => 'Lunas',
        'created_at' => 'Dibuat',
        'actions' => 'Aksi',
        'empty' => 'Belum ada piutang.',
        'search' => 'Cari peminjam atau catatan...',
        'per_page' => 'Item per halaman',
        'selected' => 'dipilih',
        'show_more' => 'Tampilkan lebih banyak',
        'show_less' => 'Tampilkan lebih sedikit',
        'select_all_matching' => 'Pilih semua :total piutang',
        'all_selected' => 'Semua :total piutang dipilih.',
        'clear_selection' => 'Batalkan pilihan',
    ],

    'fields' => [
        'name' => 'Peminjam',
        'amount' => 'Jumlah',
        'paid_amount' => 'Diterima',
        'due_date' => 'Jatuh Tempo',
        'notes' => 'Catatan',
        'is_paid' => 'Lunas',
    ],

    'actions' => [
        'edit' => 'Ubah',
        'delete' => 'Hapus',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'confirm_delete' => 'Yakin ingin menghapus piutang ini?',
        'delete_selected' => 'Hapus yang dipilih',
        'confirm_bulk_delete' => 'Yakin ingin menghapus piutang yang dipilih?',
        'generate' => 'Generate',
        'generate_count' => 'Jumlah piutang yang akan dibuat',
        'autofill' => 'Isi otomatis',
    ],

    'flash' => [
        'created' => 'Piutang berhasil ditambahkan.',
        'updated' => 'Piutang berhasil diperbarui.',
        'deleted' => 'Piutang berhasil dihapus.',
        'generated' => 'Piutang palsu berhasil dibuat.',
    ],
];
