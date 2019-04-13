<?php

namespace App\Http\Controllers;

use AppMasters\AmLLib\Controller\Controller;
use App\Post as Model;
use Illuminate\Http\Request;
use PHPUnit\Util\Json;


class PostController extends Controller
{
    /**
     * Controller constructor
     */
    public function __construct()
    {
        $this->model = new Model();
    }

    /**
     * Create a new entry of the Model
     * The validate method is called and test the request to see if is valid, if it is the method continue,
     * if it not, the method stop the process and return a json error
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request)
    {
        // var_dump($request->auth);
        // die();

        $request->merge(['user_id' => $request->auth->id]);
        if (!$this->validator($request))
            return $this->lastValidatorError();

        $entry = Model::create($request->all())->toArray();

        return $this->responseSingleData($entry);
    }

    /**
     * List all entries of the Model
     * @return Json
     **/
    public function list()
    {
        $data = Model::all()->toArray();
        return $this->responseData($data);
    }

    /**
     * List one entry of the Model
     * Test if the entry exist and return the accordingly response
     * @param int $id
     * @param Request $request
     * @return Json
     */
    public function read(Request $request, $id)
    {
        $entry = Model::find($id);

        if (is_null($entry)) {
            return $this->responseError('NOT_FOUND', 404);
        }

        return $this->responseSingleData($entry);
    }

    /**
     * Update one entry of the Model
     *  The validate method is called and test the request to see if is valid, if it is the method continue,
     * if it not, the method stop the process and return a json error
     * Test if the entry exist and return the accordingly response
     * @param int $id
     * @param Request $request
     * @return Json
     */
    public function update(Request $request, $id)
    {
        $request->merge(["id" => $id]);
        if (!$this->validator($request))
            return $this->lastValidatorError();

        $entry = Model::find($id);
        if (is_null($entry)) {
            return $this->responseError('NOT_FOUND', 404);
        }

        if ($entry->user_id !== $request->auth->id) {
            return $this->responseError('NOT_AUTHORIZED', 400);
        }

        $entry->update($request->all());
        return $this->responseSingleData($entry);
    }

    /**
     * Soft-delete one entry of the Model
     * Test if the entry exist and return the accordingly response
     * @param Request $request
     * @param $id
     * @return Json
     * @internal param $int
     */
    public function delete(Request $request, $id)
    {
        $entry = Model::find($id);

        if (is_null($entry)) {
            return $this->responseError('NOT_FOUND', 404);
        }

        if ($entry->user_id !== $request->auth->id) {
            return $this->responseError('NOT_AUTHORIZED', 400);
        }

        if ($entry->delete()) {
            return $this->responseSuccess(true);
        } else {
            return $this->responseError('NOT_DELETED', 404);
        }
    }
}
