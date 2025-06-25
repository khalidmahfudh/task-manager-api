<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UsersSeeder extends Seeder
{
    public function run()
    {
        $user_data = [
            [
                'name'     => 'Administrator',
                'email'    => 'admin@example.com',
                'password' => password_hash('password', PASSWORD_BCRYPT), 
                'role'     => 'admin',
            ],
            [
                'name'     => 'John Doe',
                'email'    => 'john.doe@example.com',
                'password' => password_hash('password', PASSWORD_BCRYPT),
                'role'     => 'user',
            ],
        ];

        $this->db->table('users')->insertBatch($user_data);
    }
}
