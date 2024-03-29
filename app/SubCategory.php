<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class SubCategory extends Model
{
    protected $fillable = ['title_en', 'title_ar', 'image', 'deleted', 'brand_id', 'category_id', 'is_show'];

    public function brand() {
        return $this->belongsTo('App\Brand', 'brand_id');
    }

    public function category() {
        return $this->belongsTo('App\Category', 'category_id');
    }

    public function products() {
        return $this->hasMany('App\Product', 'sub_category_id')->where('deleted', 0);
    }

    public function subCategoriesTwo() {
        return $this->hasMany('App\SubTwoCategory', 'sub_category_id')->where('deleted', 0);
    }
}
