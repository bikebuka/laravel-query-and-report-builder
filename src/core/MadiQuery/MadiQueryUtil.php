<?php

namespace JumaMiller\MadiLib\core\MadiQuery;

class MadiQueryUtil
{
    /*
     | --------------------------------------------------------------------------
     | @description filter the model by the given filters to the last relationship
     | @param array $filters
     | @return mixed
     | --------------------------------------------------------------------------
    */
    public function get_keys_from_query_object($query,$model): array
    {
        $instance = (new $model);
        return $query->getConnection()->getSchemaBuilder()->getColumnListing($instance->getTable());
    }
}
