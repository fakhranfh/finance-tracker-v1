<?php

return [
    'title' => 'Terminal',
    'subtitle' => 'Jalankan perintah bash dari browser.',

    'unlock' => [
        'heading' => 'Masukkan kata sandi terminal',
        'password' => 'Kata sandi terminal',
        'submit' => 'Buka',
    ],

    'forgot' => [
        'link' => 'Lupa kata sandi?',
        'sent' => 'Tautan reset dikirim ke :email. Berlaku selama 60 menit.',
    ],

    'mail' => [
        'subject' => 'Atur ulang kata sandi terminal',
        'intro' => 'Anda meminta reset kata sandi terminal. Gunakan tombol di bawah untuk memilih kata sandi baru.',
        'action' => 'Atur ulang kata sandi terminal',
        'expires' => 'Tautan ini kedaluwarsa dalam :minutes menit.',
        'ignore' => 'Jika bukan Anda yang meminta, abaikan email ini.',
    ],

    'password' => [
        'setup_heading' => 'Atur kata sandi terminal',
        'setup_hint' => 'Kata sandi terminal belum diatur. Pilih satu untuk melindungi terminal.',
        'reset_heading' => 'Atur ulang kata sandi terminal',
        'change_heading' => 'Ubah kata sandi terminal',
        'current' => 'Kata sandi saat ini',
        'new' => 'Kata sandi baru',
        'confirmation' => 'Konfirmasi kata sandi baru',
        'save' => 'Simpan kata sandi',
        'saved' => 'Kata sandi terminal disimpan.',
    ],

    'console' => [
        'command' => 'Perintah',
        'placeholder' => 'php artisan list',
        'run' => 'Jalankan',
        'lock' => 'Kunci',
        'welcome' => 'Ketik perintah bash, mis. "php artisan migrate:status" atau "git status". Ketik "clear" untuk mengosongkan layar.',
        'exit_code' => 'kode keluar :code',
    ],

    'errors' => [
        'wrong_password' => 'Kata sandi terminal salah.',
        'not_configured' => 'Kata sandi terminal belum diatur.',
        'locked' => 'Terminal terkunci.',
        'timeout' => 'Perintah melebihi :seconds detik dan dihentikan.',
        'artisan_only' => 'Server ini tidak bisa membuka shell, jadi hanya perintah "php artisan ..." yang bisa dijalankan.',
        'blocked' => 'Perintah ini tidak bisa dijalankan di sini karena tidak pernah selesai.',
        'invalid_token' => 'Tautan reset ini tidak valid atau sudah kedaluwarsa.',
        'failed' => 'Terjadi kesalahan.',
    ],
];
