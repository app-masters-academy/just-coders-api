<?php

namespace App;

use AppMasters\AmLLib\Model\BaseModel;

/**
 * @property integer likes
 */
class Comment extends BaseModel
{

    /**
     * The attributes that are mass assignable.
     * @var array
     */
    protected $fillable = [
        'user_id',
        'post_id',
        'content',
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

    public function rules($data)
    {
        return [
            'user_id' => 'required|integer',
            'post_id' => 'required|integer',
            'content' => 'required|string|max:600',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function post()
    {
        return $this->belongsTo(Post::class);
    }

}
