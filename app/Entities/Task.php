<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class Task extends Entity
{
    // Properti yang bisa diisi melalui constructor atau mass assignment
    protected $attributes = [
        'id'          => null,
        'user_id'     => null,
        'title'       => null,
        'description' => null,
        'status'      => 'pending', // Nilai default untuk status
        'due_date'    => null,
        'created_at'  => null,
        'updated_at'  => null,
        'deleted_at'  => null,
    ];
    protected $datamap = [];
    protected $dates   = ['created_at', 'updated_at', 'deleted_at', 'due_date'];
    protected $casts   = [
        'id'          => 'integer',
        'user_id'     => 'integer',
        'due_date'    => 'datetime', // Mengonversi string tanggal dari DB menjadi objek DateTime
        'created_at'  => 'datetime',
        'updated_at'  => 'datetime',
        'deleted_at'  => 'datetime',
    ];
}
