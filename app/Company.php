<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['logo', 'name_en', 'name_ar','email' , 'user_id', 'deleted', 'type', 'link'];

    public function user() {
        return $this->belongsTo('App\User', 'user_id')->select('id', 'phone', 'email', 'name');
    }
}