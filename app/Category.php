<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['image', 'title_en', 'title_ar', 'deleted', 'is_show'];

    public function products() {
        return $this->hasMany('App\Product', 'category_id')->where('deleted', 0);
    }

    public function subCats() {
        return $this->hasMany('App\SubCategory', 'category_id')->select('id', 'image')->where('deleted', 0);
    }

    public function subCategories($lang) {
        return $this->hasMany('App\SubCategory', 'category_id')->select('id', 'image', 'title_' . $lang . ' as title')->where('deleted', 0)->where('is_show', 1);
    }

    public function plans() {
        return $this->hasMany('App\Plan', 'cat_id')->where('deleted', 0);
    }
}
