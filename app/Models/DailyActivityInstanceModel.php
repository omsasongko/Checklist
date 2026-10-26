<?php

namespace App\Models;

use CodeIgniter\Model;

class DailyActivityInstanceModel extends Model
{
    protected $table            = 'daily_activity_instances';
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
        'activity_session_id',
        'occurrence_number',
        'occurrence_identity',
        'activity_date',
        'competition_date',
        'season_id',
        'configuration_version_id',
        'completion_status',
        'verification_status',
        'reward_status',
        'scheduled_at',
        'scheduled_end_at',
        'completed_at',
    ];
}
