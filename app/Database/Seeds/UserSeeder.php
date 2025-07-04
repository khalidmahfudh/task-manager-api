<?php

namespace App\Database\Seeds;

use App\Entities\User;
use App\Models\UserModel;
use CodeIgniter\CLI\CLI;
use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $userModel = new UserModel();

        // HATI-HATI: Ini akan menghapus semua data di tabel 'users'!
        // $this->db->table('users')->truncate(); 

        $users = [
            [
                'name'     => 'Administrator',
                'email'    => 'admin@example.com',
                'password' => 'password',
                'role'     => 'admin',
            ],
            [
                'name'     => 'John Doe',
                'email'    => 'john.doe@example.com',
                'password' => 'password',
                'role'     => 'user',
            ],
        ];

        // foreach ($users as $userData) {
        //     $userEntity = new \App\Entities\User($userData);
        //     $userModel->save($userEntity);
        // }

        foreach ($users as $userData) {
            // 1. Cek apakah user dengan email ini sudah ada
            $existingUser = $userModel->where('email', $userData['email'])->first();

            // 2. Jika user BELUM ADA, baru masukkan
            if (!$existingUser) {
                // Buat instance User Entity dari data
                $userEntity = new User($userData);
                // Simpan Entity ke database
                $userModel->save($userEntity);
                CLI::write('User ' . $userData['email'] . ' seeded successfully.', 'green');
            } else {
                // Jika user SUDAH ADA, lewati (skip)
                CLI::write('User ' . $userData['email'] . ' already exists, skipping.', 'yellow');
            }
        }
    }
}
