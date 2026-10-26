<?php

return [
    'create' => 'Tambah sub utang',
    'summary' => ':paid dari :total sub utang lunas · :paid_amount dari :amount',

    'pages' => [
        'debt' => [
            'create' => 'Tambah sub utang',
            'edit_item' => 'Ubah sub utang',
            'edit' => 'Ubah sub utang',
            'back_to_list' => 'Kembali ke sub utang',
            'title' => 'Sub utang',
            'subtitle' => 'Cicilan dari utang ini.',
            'back' => 'Kembali ke daftar utang',
        ],
        'receivable' => [
            'create' => 'Tambah sub piutang',
            'edit_item' => 'Ubah sub piutang',
            'edit' => 'Ubah sub piutang',
            'back_to_list' => 'Kembali ke sub piutang',
            'title' => 'Sub piutang',
            'subtitle' => 'Cicilan dari piutang ini.',
            'back' => 'Kembali ke daftar piutang',
        ],
    ],

    'mode' => [
        'title' => 'Jumlah',
        'amount' => 'Satu jumlah',
        'sub_debts' => 'Pecah jadi sub utang',
        'hint' => 'Dengan sub utang, jumlah mengikuti sub utang. Yang sudah terbayar berasal dari pembayaran.',
    ],

    'table' => [
        'name' => 'Nama',
        'amount' => 'Jumlah',
        'paid_amount' => 'Terbayar',
        'due_date' => 'Jatuh Tempo',
        'is_paid' => 'Status',
        'paid' => 'Lunas',
        'unpaid' => 'Belum lunas',
        'note' => 'Catatan',
        'actions' => 'Aksi',
        'empty' => 'Belum ada sub utang.',
        'search' => 'Cari nama atau catatan...',
        'per_page' => 'Item per halaman',
        'show_more' => 'Tampilkan lebih banyak',
        'selected' => 'dipilih',
        'select_all_matching' => 'Pilih semua :total sub utang',
        'all_selected' => 'Semua :total sub utang dipilih.',
        'clear_selection' => 'Batalkan pilihan',
        'show_less' => 'Tampilkan lebih sedikit',
    ],

    'fields' => [
        'same_due_date' => 'Sama dengan jatuh tempo utama',
        'name' => 'Nama',
        'amount' => 'Jumlah',
        'paid_amount' => 'Terbayar',
        'due_date' => 'Jatuh Tempo',
        'note' => 'Catatan',
    ],

    'form' => [
        'title' => 'Sub utang',
        'hint' => 'Pecah jumlah menjadi cicilan. Totalnya menjadi jumlah. Pembayaran ditambahkan ke tiap sub utang setelahnya.',
        'edit' => 'Ubah sub utang',
        'remove' => 'Hapus',
        'totals' => 'Total sub utang :amount',
    ],

    'actions' => [
        'payments' => 'Pembayaran',
        'delete' => 'Hapus',
        'confirm_delete' => 'Yakin ingin menghapus sub utang ini?',
        'delete_selected' => 'Hapus yang dipilih',
        'confirm_bulk_delete' => 'Yakin ingin menghapus sub utang yang dipilih?',
        'generate' => 'Generate',
        'generate_count' => 'Jumlah sub utang yang akan dibuat',
        'save' => 'Simpan',
        'cancel' => 'Batal',
        'autofill' => 'Isi otomatis',
        'edit' => 'Ubah',
    ],

    'flash' => [
        'created' => 'Sub utang berhasil ditambahkan.',
        'updated_item' => 'Sub utang berhasil diperbarui.',
        'generated' => 'Sub utang palsu berhasil dibuat.',
        'updated' => 'Sub utang berhasil disimpan.',
        'status_failed' => 'Gagal memperbarui sub utang. Silakan coba lagi.',
    ],
];
