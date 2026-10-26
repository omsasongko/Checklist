<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityModel extends Model
{
    protected $table            = 'activities';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = true;
    protected $createdField     = 'created_at';
    protected $updatedField     = 'updated_at';
    protected $dateFormat       = 'datetime';
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'category_id',
        'reward_group_id',
        'activity_code',
        'name',
        'description',
        'activity_type',
        'repeat_type',
        'verification_policy_id',
        'default_xp',
        'default_rp',
        'minimum_duration_seconds',
        'cooldown_seconds',
        'maximum_daily_completions',
        'maximum_weekly_completions',
        'schedule_policy',
        'created_by_user_id',
        'is_active',
    ];
}
