<?php

namespace App\Models;

use CodeIgniter\Model;

class UserActivityPlanModel extends Model
{
    protected $table            = 'user_activity_plans';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $dateFormat       = 'datetime';
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'user_id',
        'activity_id',
        'recurrence_rule',
        'timezone',
        'preferred_start_time',
        'preferred_end_time',
        'is_enabled',
    ];
}
