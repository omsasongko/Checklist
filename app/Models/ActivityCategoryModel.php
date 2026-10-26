<?php

namespace App\Models;

use CodeIgniter\Model;

class ActivityCategoryModel extends Model
{
    protected $table            = 'activity_categories';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'category_code',
        'name',
        'icon',
        'sort_order',
        'is_active',
    ];
}
