<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class ClinicScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        if (app()->bound('currentClinicId')) {
            $builder->where(
                $model->getTable() . '.clinic_id',
                app('currentClinicId')
            );
        }
    }
}
