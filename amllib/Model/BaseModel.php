<?php

namespace AppMasters\AmLLib\Model;

use AppMasters\AmLLib\Lib\ValidateIt;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Validator;
use Rollbar\Rollbar;

class BaseModel extends Model
{

    protected $fancyClassName;
    protected $fancyAttributes;
    protected $errors;
    protected $notFillable = [];
    protected $hiddenRelation;
    protected $visibleRelation;

    protected $guarded = ['id'];

    /**
     * Confirm if class have requested Trait
     * @param $class
     * @return bool
     */
    private static function hasTrait($class)
    {
        return in_array($class, array_keys((new \ReflectionClass(static::class))->getTraits()));
    }

    /**
     * Convert the model instance to an array.
     * Remove 'pivot' data.
     * @experimental by Tiago Gouvêa
     * DOCUMENT IT!!!
     * DOCUMENT IT!!!
     * DOCUMENT IT!!!
     * DOCUMENT IT!!!
     * Convert a instance to array
     * Clearing some relation
     * Could be better? Could be better.
     * @return array
     */
    public function toArray()
    {
        // 1 - Default attributes from lavarel (attributes and relations)
        $record = array_merge($this->attributesToArray(), $this->relationsToArray());

        if (self::hasTrait(TraitRelation::class)) {
            // 2 - Must hide relation attributes?
            if (is_array($this->hiddenRelation) && count($this->hiddenRelation) > 0) {
                $record = $this->removeHiddenRelationAttributes($record);
            }
            // 3 - Must show just some relation attributes?
            if (is_array($this->visibleRelation) && count($this->visibleRelation) > 0) {
                $record = $this->visibleRelationAttributes($record);
            }
        }

        // 4 - Ensure that all fillable attributes are on record to return
        $fillable = array_flip($this->fillable);
        $fillable = array_fill_keys(array_keys($fillable), null);
        $record = array_replace($fillable, $record);

        // 5 - Last but not least - Remove pivot from record
        // unset($record['pivot']);

        return $record;
    }


    /**
     * @experimental by Tiago Gouvêa
     * DOCUMENT IT!!!
     * @param $attributes
     * @return $this|Model
     */
    public static function create($attributes)
    {
        // Remove not fillable?
        // TRAIT TEST
        // $attributes = array_merge($this->attributes, $attributes);
        if (self::hasTrait(TraitRelation::class)) {
            $attributes = static::removeRelationAttributes($attributes);
            $model = static::query()->create($attributes);
            // Attach relation
            $model->attachRelations();

            return $model;
        } else {
            return static::query()->create($attributes);
        }
    }

    /**
     * Update the model in the database.
     *
     * @param  array $attributes
     * @param  array $options
     * @return bool
     */
    public function update(array $attributes = [], array $options = [])
    {
        if (!$this->exists) {
            return false;
        }

        // SEENS TO BE DUPLICATED! MUST BE JUST ON SAVE()!!! $handleRelationPost

        if (!isset(static::$handleRelationPost)) {
            return $this->fill($attributes)->save($options);
        } else {
            // Remove relation attributes to allow save model
            $attributesWithoutRelations = $attributes;
            foreach (static::$handleRelationPost as $relation) {
                unset($attributesWithoutRelations[$relation]);
            }

            $saved = $this->fill($attributesWithoutRelations)->save($options);

            if ($saved) {

                // Move to CERTIFY
                // Attach ids from original $attributes
                foreach (static::$handleRelationPost as $relation) {
                    if (isset($attributes[$relation])) {
                        $ids = $attributes[$relation];
                        if ($ids !== null) {
                            $ids = self::parseIds($ids);
                            $this->certifyRelationIds($relation, $ids, true, true);
                        }
                    }
                }
            } else {
                // ??
            }

        }
    }

    /**
     * @param $name
     * @param $ids
     * @param bool $findFirst
     * @param bool $removeUnused
     */
    public function certifyRelationIds($name, $ids, $findFirst = true, $removeUnused = false)
    {
        /** @var Relation $relation */
        $relation = $this->$name();
        $currentIds = $this->parseIds($relation);

        // Attach new records
        $mostAddIds = array_diff((array)$ids, $currentIds);
        $relation->attach($mostAddIds);

        // Remove ids not user anymore
        if ($removeUnused) {
            $mostRemoveIds = array_diff($currentIds, $ids);
            $relation->detach($mostRemoveIds);
        }
    }


    /**
     * Get all of the IDs from the given mixed value.
     *
     * @param  mixed $value
     * @return array
     */
    public static function parseIds($value)
    {
        if ($value instanceof Model) {
            // return [$value->{$this->relatedKey}];
            throw new Exception("Model on parseIds");
        } else if ($value instanceof Collection) {
            // return $value->pluck($this->relatedKey)->all();
            throw new Exception("Collection on parseIds");
        } else if ($value instanceof BaseCollection) {
            return $value->toArray();
        } else if (is_array($value)) {
            return $value;
            //$relation->pluck('id')->toArray()
        } else if ($value instanceof BelongsToMany) {
            return $value->get()->pluck('id')->toArray();
        } else {
            throw new Exception("Type not expecet on parseIds: " . get_class($value));
        }

        return (array)$value;
    }


    /// ********** RelationPost ************ ///
    private function hasHandleRelationPost()
    {
        return isset(static::$handleRelationPost) && count(static::$handleRelationPost) > 0;
    }

    private function getHandleRelationPost()
    {
        return static::$handleRelationPost;
    }


    /// ********** RelationShowHide VisibleHidden ************ ///


    /**
     * Return fillable array minus hidden minus relation post
     * @return array $hidden
     */
    public function getSelectable()
    {
        $fields = $this->fillable;
        // $fields = array_fill_keys(array_keys($fillable), null);
        if ($this->hidden)
            $fields = array_diff($fields, $this->hidden);

        if ($this->hasHandleRelationPost())
            $fields = array_diff($fields, $this->getHandleRelationPost());
        return $fields;
    }


    /**
     * @param $data
     * @return array
     */
    public function rules($data)
    {
        return [];
    }

    /**
     * Return a fancy name to be used on template system
     */
    public function getFancyName()
    {
        return $this->fancyClassName;
    }

    /**
     * Return a fancy name to be used on template system
     */
    public function getFancyAttributes()
    {
        return $this->fancyAttributes;
    }

    /*********************** Instance methods **********************************/
    /*********************** Instance methods **********************************/
    /*********************** Instance methods **********************************/

    /**
     * Calculate fields on instance entities.
     * Must override this method for that
     */
    public function calcFields()
    {
    }

    /**
     * Create a new model instance that is existing.
     * Overwrote to implement calcFields.
     * @param  array $attributes
     * @param  string|null $connection
     * @return Model
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        /* @var $instance BaseModel */
        $instance = parent::newFromBuilder($attributes, $connection);
        $instance->calcFields();
        return $instance;
    }

    /**
     * @param array $options
     * @return bool
     * @throws \Exception
     */
    public function save(array $options = [])
    {
        // Remove not fillable

        if (!$this->validate()) {
            Rollbar::critical("Validation Error on " . static::class . " BaseModel.save() (must be validated before)", ['errors' => $this->errors, 'attributes' => $this->attributes]);
            throw new \Exception("Unexpected Validation Error on " . static::class . " BaseModel.save() (must be validated before) - " . json_encode($this->errors));
            return false;
        }

        $saved = parent::save($options);
        if (!$saved) {
            // what must happen?
            Rollbar::critical("Not saved on " . static::class . " BaseModel.save()", ['errors' => $this->errors, 'attributes' => $this->attributes]);
            throw new \Exception("Not saved on " . static::class . " BaseModel.save() - " . json_encode($this->errors));
        }
        // var_dump($saved);
        return $saved;
    }

    /**
     * ALIAS to sanitize - Validate record according the rules on model
     * @param null $data
     * @return bool - validated
     */
    public function validate($data = null)
    {
        return $this->sanitize($data) !== false;
    }

    /**
     * Validate object according to model rules
     * @param null $data
     * @param bool $skipMissingRulesFields
     * @return array|bool|mixed|null
     */
    public function sanitize($data = null, $skipMissingRulesFields = false)
    {

        // var_dump($data);
        // die();
        $this->errors = null;

        // 1 - Determine what data will be validated

        // If this has attributes, merge with data
        // if ($data != null && is_array($data) && isset($this->attributes))
        //     $data = array_merge($this->attributes, $data);

        // When receiving from "outher model", handle just fillable fields
        if ($data != null) {
            $payload = $data;
        } else {
            // This object is a instance, on model
            $payload = $this->attributes;
        }

        // 2 - Whats the rules
        $rules = $this->rules($payload);

        // 3 - What attributes must be accepted other than that on rules
        $acceptableAttributes = array_merge($this->getAllAttributes(), $this->relations);

        // ValidateIt!
        $data = ValidateIt::validate($payload, $rules, $acceptableAttributes);
        if ($data == false) {
            // var_dump($rules);
            // var_dump($payload);
            // die();

            $this->errors = ValidateIt::getLastError();
        }

        return $data;
    }

    /**
     * Validate record according the rules on model
     * @todo Check for extra fields (more than existed)
     * @param null $data
     * @return array|bool
     */
    public function sanitize_old($data = null, $skipMissingRulesFields = false)
    {
        $this->errors = null;
        // if ($this->alreadyValidated) return $this->validated;
        // $this->alreadyValidated = true;
        // $this->validated= false;

        // Check for not existing fields
        // From where $data is comming?

        if ($data != null && is_array($data) && isset($this->attributes))
            $data = array_merge($this->attributes, $data);

        if (!is_null($data)) {
            // When receiving from "outher model", handle just fillable fields
            // I CANT REMEBER WHY!
            // $fields = $this->fillable; // COMMENTED
            $fields = $this->getAllAttributes();
            $payload = $data;
        } else {
            // This object is a instance, on model
            $fields = $this->getAllAttributes();
            $payload = $this->attributes;
        }
        $relations = $this->relations;

        // Check if have some data
        $invalidPayload = [];
        if (is_null($payload) || count($payload) == 0)
            array_push($invalidPayload, 'Empty payload');

        // Convert payload to snake case
        $payload = $this->toSnakeCase($payload);
        // TODO ! Method to convert $data to snake_case first!

        // Check for fields that didn't exists
        foreach ($payload as $key => $value) {
            if (!(in_array($key, $fields) || in_array($key, $relations))) {
                array_push($invalidPayload, 'Invalid field ' . $key);

                var_dump($key);
                var_dump(array_keys($payload));
                var_dump($payload);
                var_dump($fields);
            }
        }
        // if (count($invalidPayload) > 0) {
        //     var_dump("INVALID PAYLOAD");
        //     var_dump($fields);
        //     var_dump($invalidPayload);
        // }

        // Check for extra fields
        $extraFields = array_diff_key(array_keys($payload), $fields); //array_diff(array_keys($payload), $fields); // array_except(array_keys($payload), $fields);
        if (count($extraFields) > 0) {
            foreach ($extraFields as $extraField) {
                array_push($invalidPayload, 'Not exists field ' . $extraField);
            }
        }

        // Some invalid? Fail the validation
        if (count($invalidPayload) > 0) {
            $this->errors = ['field' => $invalidPayload];
            return false;
        }

        // return true;

        // Validate fields values
        // make a new validator object
        $rules = $this->rules($payload);
        if ($skipMissingRulesFields) {
            $newRules = [];
            foreach (array_keys($payload) as $array_key) {
                if (isset($rules[$array_key]))
                    $newRules[$array_key] = $rules[$array_key];
            }
            $rules = $newRules;
        }
        $v = Validator::make($payload, $rules);

        // check for failure
        if ($v->fails()) {
            $this->errors = $v->errors()->messages();

            if (php_sapi_name() == "cli") {
                var_dump("BaseModel.sanitize() FAILS on " . static::class);
                var_dump($this->errors);
            }
            // var_dump($payload);
            // set errors and return false
            // var_dump($this->errors);

            return false;
        }

        // $this->validated= true;

        return $payload;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getErrorsString()
    {
        return join(',', $this->errors);
    }

    /**
     * Return fillable, hidden and notFillable attributes. All attributes.
     * @return array
     */
    public function getAllAttributes()
    {
        $fields = array_merge(
            $this->getFillable(),
            $this->getHidden(),
            $this->notFillable);
        if (isset(static::$handleRelationPost))
            $fields = array_merge($fields, array_keys(static::$handleRelationPost));

        return $fields;
    }

    public function getSnakeAttributes($a)
    {
        return $a;
    }

    public function getAttributes()
    {
        $attributes = parent::getAttributes();
        $attributes = $this->toSnakeCase($attributes);
        return $attributes;
    }

    /**
     * Convert all array keys to snake_case
     * @param $payload
     * @return mixed
     */
    public function toSnakeCase($payload)
    {
        foreach ($payload as $key => $value) {
            $snakeKey = snake_case($key);
            if ($snakeKey != $key) {
                $payload[$snakeKey] = $value;
                unset($payload[$key]);
            }
        }
        return $payload;
    }

    public function __get($key)
    {
        //  Translate field names from someLikeThis to some_like_this
        $snakeKey = snake_case($key);
        if ($snakeKey != $key) {
            $key = $snakeKey;
        }
        return parent::__get($key);
    }

    public function __set($key, $value)
    {
        //  Translate field names from someLikeThis to some_like_this
        $snakeKey = snake_case($key);
        if ($snakeKey != $key)
            $key = $snakeKey;
        return parent::__set($key, $value);
    }

    public function delete()
    {
        try {
            return parent::delete();
        } catch (Illuminate\Database\QueryException $e) {
            $this->errors = [$e->getMessage()];
            return false;
        } catch (\PDOException $e) {
            $this->errors = [$e->getMessage()];
            return false;
        } catch (\Exception $e) {
            $this->errors = [$e->getMessage()];
            return false;
        }
    }

    public function getProtected($protected)
    {
        return $this->$protected;
    }


}
