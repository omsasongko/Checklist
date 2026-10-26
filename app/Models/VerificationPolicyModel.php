<?php

namespace App\Models;

use CodeIgniter\Model;

class VerificationPolicyModel extends Model
{
    protected $table            = 'verification_policies';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useTimestamps    = false;
    protected $skipValidation   = true;
    protected $allowedFields    = [
        'policy_code',
        'verification_methods',
        'requires_evidence',
        'requires_approval',
        'minimum_duration_seconds',
        'is_active',
    ];
}
