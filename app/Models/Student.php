<?php

namespace App\Models;

// use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Student extends Model
{
   // use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'username',
        'email',
        'phone',
        'alt_phone',
        'parent',
        'user_img',
        'status'
    ];

    protected $appends = ['user_img_url'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getUserImgUrlAttribute()
    {
        return $this->user_img
            ? asset('storage/' . $this->user_img)
            : null;
    }
}
