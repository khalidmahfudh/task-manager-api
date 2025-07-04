<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\TaskModel;

class TaskSeeder extends Seeder
{
    public function run()
    {
        $taskModel = new TaskModel();

        // Asumsi Anda punya user dengan ID 1 dan 2. Sesuaikan jika berbeda.
        $tasks = [
            [
                'user_id'     => 1,
                'title'       => 'Selesaikan Laporan Proyek',
                'description' => 'Siapkan data dan grafik untuk laporan akhir proyek.',
                'status'      => 'in-progress',
                'due_date'    => '2025-07-10 17:00:00',
            ],
            [
                'user_id'     => 1,
                'title'       => 'Perbaiki Bug di Modul Autentikasi',
                'description' => 'Ada masalah pada proses login yang perlu segera ditangani.',
                'status'      => 'pending',
                'due_date'    => '2025-07-08 12:00:00',
            ],
            [
                'user_id'     => 2,
                'title'       => 'Meeting dengan Klien A',
                'description' => 'Diskusi progres proyek dan umpan balik.',
                'status'      => 'completed',
                'due_date'    => '2025-07-01 10:00:00',
            ],
            [
                'user_id'     => 2,
                'title'       => 'Riset Teknologi Baru',
                'description' => 'Jelajahi opsi framework frontend terbaru untuk proyek selanjutnya.',
                'status'      => 'in-progress',
                'due_date'    => null, // Tanpa due date
            ],
        ];

        foreach ($tasks as $task) {
            $taskModel->insert($task);
        }

        $this->call('UserSeeder'); // Opsional: Panggil UserSeeder jika Anda ingin memastikan ada user default
    }
}
