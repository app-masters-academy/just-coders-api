<?php

namespace App;

use Illuminate\Auth\Authenticatable;
use Illuminate\Support\Facades\Hash;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use AppMasters\AmLLib\Model\BaseModel;

class User extends BaseModel implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'active',
        'facebook_id',
        'google_id',
        'linkedin_id',
        'github_id',
        'twitter_id',
        'thumb_url',
        'image_url',

    ];

    /**
     * The attributes that cannot be filled on Requests
     * @var array
     */
    protected $notFillable = [
        'id',
        'created_at',
        'deleted_at',
        'updated_at',
    ];

    /**
     * The attributes that have default values
     * @var array
     */
    protected $attributes = [
        'active' => true
    ];

    /**
     * Find a user by the entry email on the database
     * @param $email
     * @return User
     */
    public static function findByEmail($email)
    {
        return self::where('email', '=', $email)->first();
    }

    public function rules($data)
    {
        return [
            'name' => 'nullable|string|max:80',
            'email' => 'required|string|max:80',
            'role' => 'required|string|max:20',
            'facebook_id' => 'nullable|string|max:64',
            'google_id' => 'nullable|string|max:64',
            'linkedin_id' => 'nullable|string|max:64',
            'github_id' => 'nullable|string|max:64',
            'twitter_id' => 'nullable|string|max:64',
            'thumb_url' => 'nullable|string|max:200',
            'image_url' => 'nullable|string|max:200',
        ];
    }

    public function getUserData()
    {
        return $this->toArray();
    }
}
