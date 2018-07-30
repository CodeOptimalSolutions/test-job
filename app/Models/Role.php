<?php

namespace DTApi\Models;

use DTApi\Traits\RoleHasRelations;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use RoleHasRelations;
}
