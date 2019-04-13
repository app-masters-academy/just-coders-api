<?php

namespace App\Http\Controllers;

use AppMasters\AmLLib\Controller\Controller;
use App\User;
use Firebase\JWT\JWT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use App\User as Model;

class AuthController extends Controller
{
    /**
     * The request instance.
     * @var \Illuminate\Http\Request
     */
    protected $request;

    /**
     * Create a new controller instance.
     *
     * @param  \Illuminate\Http\Request $request
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->model = new Model();
    }

    /**
     * @param Request $request
     * @return mixed
     * @internal param Model $user
     */
    public function loginSocial(Request $request)
    {
        $data = $this->sanitizingRequest($this->request->all(), false, [
            'email' => 'required|email',
            'name' => 'required|string|max:80',
            'network' => 'required|string|in:facebook,google,linkedin,github,twitter',
            'id' => 'required',
            'photo' => 'nullable|url'
        ]);
        if ($data === false)
            return $this->lastValidatorError(422);

        $email = filter_var($request->get('email'), FILTER_SANITIZE_EMAIL);

        // Find the user by email
        $user = User::where('email', $email)->first();
        if ($user) {
            return $this->responseUserData($user);
        }

        // Create user
        $user = new User([
            'email' => $request->get('email'),
            'password' => Hash::make($request->get('password')),
            'name' => $request->get('name'),
            'thumb_url' => $request->get('photo'),
            'image_url' => $request->get('photo'),
            $request->get('network') . '_id' => $request->get('id'),
            'role' => 'user'
        ]);

        if ($user->save()) {
            return $this->responseUserData($user);
        } else {
            return $this->responseError('INTERNAL_ERROR', 500);
        }
    }

    public function githubCallback(Request $request){

    }

    /**
     * Create a new token.
     *
     * @param  \App\User $user
     * @return string
     */
    protected function jwt(User $user)
    {
        $payload = [
            'iss' => "lumen-jwt", // Issuer of the token
            'sub' => $user->id, // Subject of the token
            'iat' => time(), // Time when JWT was issued.
            'exp' => time() + 60 * 60 * 24 // Expiration time
        ];

        // As you can see we are passing `JWT_SECRET` as the second parameter that will
        // be used to decode the token in the future.
        return JWT::encode($payload, env('JWT_SECRET'));
    }

    private function responseUserData($user)
    {
        return response()->json([
            'token' => $this->jwt($user),
            'user' => $user->getUserData()
        ], 200);
    }


}
