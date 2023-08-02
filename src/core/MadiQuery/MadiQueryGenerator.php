<?php

namespace JumaMiller\MadiLib\core\MadiQuery;

use Carbon\Carbon;

class MadiQueryGenerator extends MadiQueryUtil
{
    /*
    | --------------------------------------------------------------------------
    | @description filter the model by the given filters to the last relationship
    | @param array $filters
    | @return mixed
    | --------------------------------------------------------------------------
   */
    public function build_query($model,$relations,$filters)
    {
        //build query to the nth relationship
        $query = (new $model)->query();
        $query=self::add_relationship_to_nth_level($query,$relations);
        //parent model keys
        $model_keys = self::get_keys_from_query_object($query,$model);
        //loop through filters
        $query=static::search_by_key($query,$filters,$model_keys,$relations);
        //apply filter by date
        $query=static::filter_by_date($query,$filters,$model_keys);
        //apply sort_by and order_by
        return static::sort_by_and_order_by($query,$filters);
    }
    /*
    | --------------------------------------------------------------------------
    | @description build query to the nth relationship
    | @param array $filters
    | @return mixed
    | --------------------------------------------------------------------------
     */
    private static function add_relationship_to_nth_level($query,$relationships)
    {
        //loop through relationships
        foreach ($relationships as $relationship) {
            //if relationship has relationships
            if (isset($relationship['relationships'])) {
                //get the relationship
                $query->with([$relationship['key'] => function ($query) use ($relationship) {
                    //add relationship to nth level
                    static::add_relationship_to_nth_level($query,$relationship['relationships']);
                }]);
            } else {
                //get the relationship
                $query->with($relationship['key']);
            }
        }
        return $query;
    }
    /*
    | --------------------------------------------------------------------------
    | @description sort_by and order_by
    | @param array $filters
    | @return mixed
    | --------------------------------------------------------------------------
     */
    private function sort_by_and_order_by($query,$filters)
    {
        return $query->when((isset($filters['sort_by']) && isset($filters['order_by'])), function ($query) use ($filters) {
            if (isset($filters['sort_by']) && isset($filters['order_by'])) {
                //get sort_by from filters array
                $sort_by = $filters['sort_by'];
                //get order_by from filters array
                $order_by = $filters['order_by'];
                //sort by the given sort_by
                return $query->orderBy($sort_by, $order_by);
            }
            return $query;
        });
    }
    /*
    | --------------------------------------------------------------------------
    | @description filter by date(s)
    | @param array $filters
    | @return mixed
    | --------------------------------------------------------------------------
     */
    private function filter_by_date($query,$filters,$model_keys)
    {
        //if nit set, get the last 30 days
        $start_date=isset($filters['start_date']) ? Carbon::parse($filters['start_date'])->startOfDay() : now()->subDays(30);
        $end_date=isset($filters['end_date']) ? Carbon::parse($filters['end_date'])->endOfDay() : now();
        //
        $sort_date_by = $filters['sort_date_by'] ?? 'created_at';
        //check if sort_date_by is in model keys
        if(!in_array($sort_date_by,$model_keys)){
            $sort_date_by='created_at';
        }
        //filter by date
        return $query->whereBetween($sort_date_by, [$start_date, $end_date]);
    }
    /*
    | --------------------------------------------------------------------------
    | @description search by key
    | @param array $filters
    | @return mixed
    | --------------------------------------------------------------------------
     */
    private static function search_by_key($query,$filter,$model_keys,$relations)
    {
        //if filter has either search,q or search_value or q
        //use array in
        $searchValue = $filter['search_value'] ?? ($filter['q'] ?? ($filter['search'] ?? null));
        //if search value is not null
        return $query->where(function ($query) use ($model_keys,$searchValue,$relations) {
            foreach ($model_keys as $key) {
                //if key is not object
                $query->orWhere($key, 'like', '%' . $searchValue . '%');
            }
            //if relations[] is set, use whereHas
            if (!empty($relations)) {
                foreach ($relations as $relation) {
                    $instance = new $relation['class']();
                    $relation_model_keys= (new MadiQueryGenerator)->get_keys_from_query_object($query,$instance);
                      //if key is not object
                    $query->orWhereHas($relation['key'], function ($query) use ($searchValue,$relation_model_keys) {
                        foreach ($relation_model_keys as $key) {
                            $query->orWhere($key, 'like', '%' . $searchValue . '%');
                        }
                    });
                }
            }
        });
    }
}
