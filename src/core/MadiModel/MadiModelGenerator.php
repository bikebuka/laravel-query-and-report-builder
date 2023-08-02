<?php
/*
    | --------------------------------------------------------------------------
    | @author Miller Juma
    | @description Generate related models
    | --------------------------------------------------------------------------
 */
namespace JumaMiller\MadiLib\core\MadiModel;

use Illuminate\Support\Facades\Log;

class MadiModelGenerator
{
    /*
     | --------------------------------------------------------------------------
     | @description The related models
     | --------------------------------------------------------------------------
    */
    public array $related_models=[];
    /*
     | --------------------------------------------------------------------------
     | @description The constructor
     | --------------------------------------------------------------------------
    */
    public function __construct(array $related_models)
    {
        $this->generate_relation_helper($related_models);
    }
    /*
     | --------------------------------------------------------------------------
     | @description Generate related models
     | @param $key
     | @param $enabled
     | @param $type
     | @param $name
     | @param $class
     | @param $foreignKey
     | @param $localKey
     | @return mixed
     | --------------------------------------------------------------------------
    */
    public function generate_relationships($enabled,$key,$class, $type='belongsTo', $foreignKey='id', $localKey='id',$columns=[],$relationships=[]): void
    {
        $this->related_models[$key]= [
            'enabled' => $enabled,
            'type' => $type,
            'key' => $key,
            'class' => $class,
            'foreignKey' => $foreignKey,
            'localKey' => $localKey,
            'columns' => $columns,
            'relationships' => $relationships
        ];
    }
    /*
     | --------------------------------------------------------------------------
     | @description Generate related models
     | @param array $relations
     | @return mixed
     | --------------------------------------------------------------------------
    */
    public function generate_relation_helper(array $relations): void
    {
        foreach ($relations as $relation) {
            if (!$this->validate_relations([$relation])) {
                Log::error('Invalid relation', [$relation]);
                continue;
            }
            // Assuming each $relation is an array containing related model information
            $this->generate_relationships(
                $relation['enabled'],
                $relation['key'],
                $relation['class'],
                $relation['type'],
                $relation['foreignKey'],
                $relation['localKey'],
                $relation['columns'],
                $relation['relationships']
            );
        }
    }
    /*
     | --------------------------------------------------------------------------
     | @param $key
     | @description check if the key is valid
     | @return bool
     | --------------------------------------------------------------------------
     */
    public static function validate_relations($relations): array
    {
        return array_filter($relations, function ($value) {
            // Make sure the keys exist.
            if (!isset($value['enabled']) || !isset($value['type']) || !isset($value['class'])) {
                return false;
            }
            Log::info($value['columns']);
            //check for columns key,should be empty array or array of strings
            if (empty($value['columns']) && !is_array($value['columns'])) {
                return false;
            }
            // Make sure the keys have the expected data types (strings).
            if (!is_bool($value['enabled']) || !is_string($value['type']) || !is_string($value['class'])) {
                return false;
            }
            // validate $value['relationships'] to nth level
            $isValid = $value['enabled'] && $value['type'] && class_exists($value['class']);
            // Base case: If there are no more nested relationships, stop recursion.
            if (!isset($value['relationships']) || !is_array($value['relationships'])) {
                unset($value['relationships']);
                return $isValid;
            }
            // Recursive case: Validate nested relationships.
            $nestedValidations = array_map('static::validate_relations', $value['relationships']);
            return $isValid && !in_array(false, $nestedValidations, true);
        });
    }
    /*
        | --------------------------------------------------------------------------
        | @description Generate related models
        | @param $model
        | @param $relations
        | @return mixed
        | --------------------------------------------------------------------------
     */
    public function generate_valid_relations_to_nth($model,$relation)
    {
        // Resolve the current relationship
        $relationship = $model->{$relation['type']}($relation['class'], $relation['foreignKey'], $relation['localKey']);
        // Check for nested relationships and do recursive loop to nth level
        //exit when no more nested relationships
        if (!empty($relation['relationships']) && is_array($relation['relationships'])) {
            foreach ($relation['relationships'] as $nestedRelation) {
                //instance of the related model
                $relatedModel = new $relation['class'];
                // recursive call to nth level
                $relationship->with($nestedRelation['key'], function () use ($nestedRelation, $relatedModel) {
                    $this->generate_valid_relations_to_nth($relatedModel, $nestedRelation);
                });
            }
        }
        return $relationship;
    }
}
