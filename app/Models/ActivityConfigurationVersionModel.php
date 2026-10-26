<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityConfigurationVersionModel extends Model
{
    protected $table            = 'activity_configuration_versions';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'activity_id',
        'version_number',
        'configuration_snapshot',
        'effective_season_id',
        'created_by',
        'created_at',
    ];
}
