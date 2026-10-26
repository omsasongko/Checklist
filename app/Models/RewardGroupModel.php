<?php

namespace App\Models;

use CodeIgniter\Model;

class RewardGroupModel extends Model
{
    protected $table            = 'reward_groups';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'group_code',
        'name',
        'description',
        'default_daily_rp_budget',
        'is_active',
    ];
}
