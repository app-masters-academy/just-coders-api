<?php

namespace App;

use AppMasters\AmLLib\Model\BaseModel;

/**
 * @property integer likes
 */
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

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    public function addLike($user)
    {
        $this->likes++;
        $this->update();
    }

    /**
     * @param \Illuminate\Http\Request $request
     * @param $postId
     * @return Comment|\Illuminate\Database\Eloquent\Model
     */
    public function addComment(\Illuminate\Http\Request $request, $postId)
    {
        return Comment::create(
            ['user_id' => $request->auth->id,
                'post_id' => $postId,
                'content' => $request->get('content')
            ]
        );
    }

}
