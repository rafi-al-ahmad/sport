<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id',
        'product_id',
        'stars_value',
        'content',
        'status',
    ];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'deleted_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i',
        'updated_at' => 'datetime:Y-m-d H:i',
    ];


    /**
     * The relationships that should always be loaded.
     *
     * @var array
     */
    protected $with = [
        'replies',
    ];

    
    // declare event handlers
    public static function boot() {
        parent::boot();

        static::deleting(function($model) {
             $model->replies()->delete();
        });
    }


    public function replies()
    {
        return $this->hasMany(ReviewReply::class, 'review_id', 'id');
    }
}
