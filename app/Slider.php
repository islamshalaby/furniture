<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Slider extends Model
{
    protected $fillable = ['ad_id'];

    public function ad() {
        return $this->belongsTo('App\Ad', 'ad_id')->select('id', 'image', 'type', 'content', 'content_type');
    }
}
