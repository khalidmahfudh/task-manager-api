<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\User;
class UserModel extends Model
{
    protected $table            = 'users';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = User::class;
    protected $useSoftDeletes   = true;
    protected $protectFields    = true;
    protected $allowedFields    = ['name', 'email', 'password', 'role'];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';
    protected $deletedField  = 'deleted_at';

    // Validation
    protected $validationRules      = [];
    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert   = [];
    protected $afterInsert    = [];
    protected $beforeUpdate   = [];
    protected $afterUpdate    = [];
    protected $beforeFind     = [];
    protected $afterFind      = [];
    protected $beforeDelete   = [];
    protected $afterDelete    = ['softDeleteUserTasks'];

    /**
     * Callback untuk meng-soft delete semua tasks yang terkait dengan user yang dihapus.
     * Dipanggil setelah operasi delete() pada UserModel.
     *
     * @param array $data Data yang berisi ID user yang dihapus.
     * @return array
     */
    protected function softDeleteUserTasks(array $data)
    {
        if (isset($data['id']) && is_array($data['id'])) {
            $taskModel = new \App\Models\TaskModel(); // Instansiasi TaskModel
            foreach ($data['id'] as $userId) {
                // Lakukan soft delete pada semua tasks yang user_id-nya sama dengan $userId
                $taskModel->where('user_id', $userId)->delete();
                log_message('info', 'Soft deleted tasks for user ID: ' . $userId . ' due to user soft delete.');
            }
        }
        return $data; // Penting: selalu kembalikan $data
    }
}
