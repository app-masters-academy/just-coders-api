<?php

namespace AppMasters\AmLLib\Controller;

use AppMasters\AmLLib\Lib\ValidateIt;
use AppMasters\AmLLib\Model\BaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Laravel\Lumen\Routing\Controller as BaseController;
use Laravel\Lumen\Http\Request;
use phpDocumentor\Reflection\Types\Boolean;

class Controller extends BaseController
{
    protected $lastValidationError;

    /** @var BaseModel $model */
    protected $model;
    /** @var Request $request */
    protected $request;

    public $errorMessage = [
        'PAYLOAD_INVALID' => 'Campo inválido ', // 422
        'EMPTY_PAYLOAD' => 'Dados enviados inválidos', // 422

        'NOT_FOUND' => 'Não encontrado', // 404
        'NOT_DELETED' => 'O registro não foi excluído',

        'NOT_AUTHORIZED' => 'Sem autorização',
        'INVALID_VALUE' => 'Valor inválido para este campo',

        // Auth errors
        'EMAIL_OR_PASSWORD_WRONG' => 'Email ou senha inválido', // 403
        'USER_DOES_NOT_EXISTS' => 'Este usuário não existe', // 403
        'USER_ALREADY_EXISTS' => 'Este usuário já existe', // 403
        'CODE_DOES_NOT_MATCH' => 'Código diferente do esperado', // 403

        'INTERNAL_ERROR' => 'Falha no servidor', // 500
    ];

    /**
     * Validator for the requests
     * Ask model to validate data
     * @param Request $request
     * @return boolean
     * @internal param Request $data
     */
    public function validator(Request $request)
    {
        return $this->sanitize($request) !== false;
    }

    /**
     * Sanitize request and return valid data or false
     * @param Request $request
     * @param bool $skipMissingRulesFields
     * @return array|bool
     * @internal param Request $data
     */
    public function sanitize(Request $request, $skipMissingRulesFields = false)
    {
        $data = $this->model->sanitize($request->all(), $skipMissingRulesFields);
        if ($data === false)
            $this->lastValidationError = $this->model->getErrors();
        return $data;
    }

    public function lastValidatorError($statusCode = 400)
    {
        if ($this->lastValidationError == null)
            throw new \Exception("null lastValidationError on lastValidatorError()");
        else if (is_array($this->lastValidationError))
            return response($this->lastValidationError, $statusCode);
        else if (get_class($this->lastValidationError) == Response::class)
            return $this->lastValidationError;
        else {
            throw new \Exception("Response type not expected");
        }
    }

    /**
     * Validate the given request with the given rules.
     *
     * @param array $data
     * @param  array $rules
     * @param array|null $accecptableAttributes
     * @return boolean|array
     */
    public function validateIt(array $data, array $rules, array $accecptableAttributes = null)
    {
        $data = ValidateIt::validate($data, $rules);
        if ($data === false)
            $this->lastValidationError = ValidateIt::getLastError();
        return $data;
    }

    /**
     * Used on testcases
     * @return BaseModel
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * Validate record according the rules on model
     * @todo Check for extra fields (more than existed)
     * @param $data
     * @param bool $skipMissingRulesFields
     * @param $rules
     * @return array|bool
     */
    public function sanitizingRequest($data, $skipMissingRulesFields, $rules)
    {
        $errors = null;
        $fields = [];

        // Check for not existing fields
        // From where $data is comming?
        foreach ($rules as $key => $value)
            $fields[] = $key;
        $payload = $data;

        // Check if have some data
        $invalidPayload = [];
        if ($payload == null || count($payload) == 0)
            array_push($invalidPayload, 'Empty payload');

        // Convert payload to snake case
        $payload = $this->model->toSnakeCase($payload);

        // Check for fields that didn't exists
        foreach ($payload as $key => $value) {
            if (!in_array($key, $fields)) {
                array_push($invalidPayload, 'Invalid field ' . $key);
            }
        }

        // Check for extra fields
        $extraFields = array_diff_key(array_keys($payload), $fields); //array_diff(array_keys($payload), $fields); // array_except(array_keys($payload), $fields);
        if (count($extraFields) > 0) {
            foreach ($extraFields as $extraField) {
                array_push($invalidPayload, 'Not exists field ' . $extraField);
            }
        }

        // Some invalid? Fail the validation
        if (count($invalidPayload) > 0) {
            $errors = ['field' => $invalidPayload];
            $this->lastValidationError = $errors;
            return false;
        }

        // Validate fields values
        // make a new validator object
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
            $errors = $v->errors()->messages();
            $this->lastValidationError = $errors;
            return false;
        }

        return $payload;
    }

    public function validateAuth()
    {
        // if ($request->user() != null)
        //     $request->merge1(['updated_by' => $request->user()->id]);
    }

    /**
     * Check if request has a slug, if not, create then from $titleField
     * @param Request $request
     * @param string $titleField
     */
    public function certifySlug(Request &$request, string $titleField)
    {
        if ($request->get('slug') == null && $request->get($titleField) != null)
            $request->merge(['slug' => str_slug($request->get($titleField))]);
    }


    /***********
     * Response methods
     */

    /**
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseData($data)
    {
        // if ($data)
        if ($data != null && !is_array($data))
            $data = $data->toArray();

        $response = ["data" => $data];

        if (is_array($data))
            $response['count'] = count($data);

        return response()->json($response, 200, [], JSON_NUMERIC_CHECK);
    }
    /**
     * @param $data
     * @return \Illuminate\Http\JsonResponse
     */
    public function responseSingleData($data)
    {
        // if ($data)
        if ($data != null && !is_array($data))
            $data = $data->toArray();

        $response = ["data" => $data];

        return response()->json($response, 200, [], JSON_NUMERIC_CHECK);
    }

    public function responseError($key, $statusCode)
    {
        if (is_array($key)) {
            return response(['error' => $key], $statusCode);
        } else {
            return response(['error' => ["[$key] " . $this->errorMessage[$key]]], $statusCode);
        }
    }

    public function responseErrorWithMessage($key, $message, $statusCode)
    {
        if (is_array($key)) {
            return response(['error' => $key], $statusCode);
        } else {
            return response(['error' => ["[$key] " . $this->errorMessage[$key] . ' - ' . $message]], $statusCode);
        }
    }

    public function responseFieldError(string $field, string $key, int $statusCode)
    {
        return response(['error' => [$field => ["[$key] " . $this->errorMessage[$key]]]], $statusCode);
    }

    public function responseFieldWithMessage(string $field, string $errorMessage, int $statusCode)
    {
        return response([$field => [$errorMessage]], $statusCode);
    }

    public function responseSuccess(bool $success, $extra = null)
    {
        return response(['success' => $success, 'extra' => $extra]);
    }

    public function responseMixed(array $response)
    {
        return response($response);
    }


    //**************** WIP - GENERICS RELATION ********************/


    /**
     * List relation records in a #manyToMany scope
     * @param string $relation
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function listRelation(string $relation)
    {
        // Required information
        $parentId = $this->request->route('id');

        /** @var Model $entry */
        $entry = $this->model->find($parentId);
        if ($entry == null)
            return $this->responseError('NOT_FOUND', 404);

        $relation = $entry->$relation();

        return $this->responseData($relation->get());
    }

    /**
     * Get one record in a #manyToMany scope
     * @param string $relationName
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function readRelation(string $relationName)
    {
        // Required information
        $parentId = $this->request->route('id');
        $childId = $this->request->route('childId');
        if ($childId == null)
            throw new \InvalidArgumentException("childId cannot be null on that route");

        /** @var Event $entry */
        $entry = $this->model->find($parentId);
        if ($entry == null)
            return $this->responseError('NOT_FOUND', 404);

        return $this->responseData($entry->$relationName()->find($childId));
    }


    /**
     * Create or update a relation in a #manyToMany scope
     * @param string $relationName
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     */
    public function syncRelation(string $relationName)
    {
        // Required information
        $rulesName = $relationName . "Rules"; // TagsRules() on model

        $data = $this->sanitizingRequest($this->request->all(), true, $this->model->$rulesName());
        if ($data === false)
            return $this->lastValidatorError();

        // Required information
        $parentId = $this->request->route('id');

        // Find parent record
        /** @var Model $entry */
        $entry = $this->model->find($parentId);
        if ($entry == null)
            return $this->responseError('NOT_FOUND', 404);

        // Required information
        /** @var BelongsToMany $relation */
        $relatedModel = $entry->$relationName()->getRelated();
        $relatedPivotKey = $entry->$relationName()->getRelatedPivotKeyName();
        if (isset($data[$relatedPivotKey]))
            $childId = $data[$relatedPivotKey]; // some like 'tag_id'
        else
            $childId = $this->request->route('childId');

        // Find child record
        if ($relatedModel->find($childId) == null)
            return $this->responseError('PAYLOAD_INVALID', 404); // @todo could send field name

        // Have more fields on data, more than the parent_id?
        if (count($data) > 1) {
            unset($data[$relatedPivotKey]);
        }

        //Attaching the tag on event
        $entry->$relationName()->syncWithoutDetaching([$childId => $data]);

        return $this->responseData($entry->$relationName()->find($childId));
    }


    /**
     * Delete a relationship in a #manyToMany scope
     * @param string $relationName
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory
     * @throws \Exception
     */
    public function detachRelation(string $relationName)
    {
        // Validate request
        $childId = $this->request->route('childId'); // some like 'tag_id'
        if ($childId == null)
            throw new \InvalidArgumentException("childId cannot be null on that route");

        // Required information
        $parentId = $this->request->route('id');

        // Find parent record
        /** @var Model $entry */
        $entry = $this->model->find($parentId);
        if ($entry == null)
            return $this->responseError('NOT_FOUND', 404);

        // Required information
        /** @var BelongsToMany $relation */
        $relatedModel = $entry->$relationName()->getRelated();
        $relatedPivotKey = $entry->$relationName()->getRelatedPivotKeyName();

        // Find child record
        if ($relatedModel->find($childId) == null)
            return $this->responseError('PAYLOAD_INVALID', 404); // @todo could send field name

        //Detaching the user on event
        $entry->$relationName()->detach($childId);
        return $this->responseSuccess(true);
    }


    /**
     * Create a relation
     * @param string $relationName
     * @param string $controllerClassName
     * @return \Illuminate\Http\Response|\Laravel\Lumen\Http\ResponseFactory|\PHPUnit\Util\Json
     */
    public function createChild(string $relationName, string $controllerClassName)
    {
        // Validate relation
        // Required information
        $parentId = $this->request->route('id');

        /** @var Event $entry */
        $entry = $this->model->find($parentId);
        if ($entry == null)
            return $this->responseError('NOT_FOUND', 404);

        /** @var HasMany $relation */
        $relation = $entry->$relationName();
        if (get_class($relation) != HasMany::class)
            throw new \InvalidArgumentException("createChild works just for HasMany relations, not for " . get_class($relation));

        // Required information
        $foreignKeyName = $entry->$relationName()->getForeignKeyName();
        $childId = $this->request->route('childId'); // some like 'tag_id'

        // Add parent reference on request data
        //Adding the content id on request
        $this->request->merge([$foreignKeyName => $parentId]);

        $priceController = new $controllerClassName();
        return $priceController->create($this->request);
    }
}
