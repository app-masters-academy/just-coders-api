<?php

namespace App;

use AppMasters\AmLLib\Model\BaseModel;

class Post extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id',
        'content',
        'likes',
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

    protected $attributes = [
        'likes' => 0
    ];

    public function rules($data)
    {
        return [
            'user_id' => 'required|integer',
            'content' => 'required|string|max:600',
        ];
    }

}
