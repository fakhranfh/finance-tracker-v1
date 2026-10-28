<?php

return [
    'summary' => 'Terbayar :paid dari :amount · sisa :remaining',

    'pages' => [
        'debt' => [
            'title' => 'Pembayaran',
            'subtitle' => 'Record yang melunasi utang ini.',
            'back' => 'Kembali ke daftar utang',
            'back_to_payments' => 'Kembali ke pembayaran',
            'create' => 'Tambah pembayaran',
            'edit' => 'Ubah pembayaran',
            'existing' => 'Pakai record yang sudah ada',
            'existing_hint' => 'Pilih record pengeluaran yang belum melunasi utang lain.',
        ],
        'receivable' => [
            'title' => 'Penerimaan',
            'subtitle' => 'Record yang melunasi piutang ini.',
            'back' => 'Kembali ke daftar piutang',
            'back_to_payments' => 'Kembali ke penerimaan',
            'create' => 'Tambah penerimaan',
            'edit' => 'Ubah penerimaan',
            'existing' => 'Pakai record yang sudah ada',
            'existing_hint' => 'Pilih record pemasukan yang belum melunasi piutang lain.',
        ],
    ],

    'table' => [
        'recorded_at' => 'Tanggal',
        'account' => 'Akun',
        'category' => 'Kategori',
        'amount' => 'Jumlah',
        'note' => 'Catatan',
        'sub_debt' => 'Sub utang',
        'actions' => 'Aksi',
        'empty' => 'Belum ada pembayaran.',
        'search' => 'Cari catatan, akun, atau kategori...',
        'per_page' => 'Item per halaman',
        'show_more' => 'Tampilkan lebih banyak',
        'show_less' => 'Tampilkan lebih sedikit',
    ],

    'existing' => [
        'empty' => 'Tidak ada record yang cocok.',
    ],

    'fields' => [
        'sub_debt' => 'Sub utang',
        'select_sub_debt' => 'Pilih sub utang',
        'account' => 'Akun',
        'select_account' => 'Pilih akun',
        'category' => 'Kategori',
        'no_category' => 'Tanpa kategori',
        'amount' => 'Jumlah',
        'recorded_at' => 'Tanggal',
        'note' => 'Catatan',
    ],

    'actions' => [
        'open_record' => 'Buka record',
        'edit' => 'Ubah pembayaran',
        'remove' => 'Hapus pembayaran',
        'confirm_remove' => 'Hapus pembayaran ini? Record yang tersimpan untuknya tetap ada.',
        'use' => 'Pakai record ini',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'autofill' => 'Isi otomatis',
    ],

    'flash' => [
        'created' => 'Pembayaran berhasil ditambahkan.',
        'updated' => 'Pembayaran berhasil diperbarui.',
        'used' => 'Record berhasil dijadikan pembayaran.',
        'removed' => 'Pembayaran dihapus. Recordnya, jika ada, tetap ada.',
    ],

    'record_prompt' => [
        'title' => 'Simpan juga sebagai record?',
        'body' => 'Simpan juga pembayaran ini sebagai record di akun yang dipilih? Saldo akun hanya berubah jika Anda melakukannya.',
        'yes' => 'Ya, buat record',
        'no' => 'Tidak, simpan saja',
    ],

    'validation' => [
        'sub_debt_required' => 'Pilih sub utang yang dibayar.',
        'exceeds_remaining' => 'Jumlah ini melebihi sisa yang harus dibayar (:remaining).',
    ],
];
