<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivitySessionModel extends Model
{
    protected $table            = 'activity_sessions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'activity_id',
        'session_code',
        'session_name',
        'scheduled_start',
        'scheduled_end',
        'xp_value',
        'rp_value',
        'sort_order',
        'is_active',
    ];
}
