<?php

namespace App\Models;

use CodeIgniter\Model;

class SeasonModel extends Model
{
    protected $table            = 'seasons';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'season_number',
        'season_name',
        'starts_at',
        'ends_at',
        'status',
        'submissions_closed_at',
        'finalized_at',
    ];
}
