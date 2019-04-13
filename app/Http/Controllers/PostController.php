<?php

namespace App\Http\Controllers;

use AppMasters\AmLLib\Controller\Controller;
use App\Post as Model;
use AppMasters\AmLLib\Lib\Utils;
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
        $data = Model::with('user')->get()->toArray();
        return $this->responseData($data);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function timeline(Request $request)
    {
        $data = Model::orderBy('id', 'desc')->with('user')->get()->toArray();

        $data = $this->applyReturnMask($data);

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
        $entry = Model::with('user')->find($id);

        if (is_null($entry)) {
            return $this->responseError('NOT_FOUND', 404);
        }

        return $this->responseSingleData($this->applyReturnMask($entry->toArray()));
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
        return $this->read($request, $id);
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

    /**
     * @param Request $request
     * @param $id
     * @return Json
     * @internal param $int
     */
    public function like(Request $request, $id)
    {
        /** @var Model $entry */
        $entry = Model::find($id);

        if (is_null($entry)) {
            return $this->responseError('NOT_FOUND', 404);
        }

        $entry->addLike($request->auth);

        return $this->read($request, $id);
    }

    private function applyReturnMask($data)
    {
        return Utils::applyMask($data,
            ['*', 'user' => ['name', 'thumb_url']],
            ['updated_at']
        );

    }
}
