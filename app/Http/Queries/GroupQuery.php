<?php

namespace App\Http\Queries;

use App\Models\Group;
use Spatie\QueryBuilder\QueryBuilder;
use Spatie\QueryBuilder\AllowedFilter;

class GroupQuery extends QueryBuilder
{
    public function __construct()
    {
        parent::__construct(Group::query());

        $this->allowedIncludes('user', 'file')
            ->allowedFilters([
                'name',
                AllowedFilter::exact('group_id'),
//                AllowedFilter::scope('withOrder')->default('recentReplied'),
            ]);
    }
}
