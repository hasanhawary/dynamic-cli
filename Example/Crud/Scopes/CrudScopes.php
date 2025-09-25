<?php

namespace Scopes;

use Illuminate\Database\Eloquent\Builder;

trait CrudScopes
{
    public function scopeByCreator(Builder $builder): void
    {
        $builder->where('created_by', auth()->id());
    }
}
