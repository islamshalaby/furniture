<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
//    protected $dates = ['publication_date'];
    protected $fillable = ['title','description', 'price','category_id','sub_category_id','sub_category_two_id','expire_special_date',
        'sub_category_three_id','sub_category_four_id','user_id', 'type','publication_date','re_post_date','is_special',
        'views', 'offer', 'status', 'expiry_date','main_image','expire_pin_date','created_at','plan_id','publish',
        'sub_category_five_id','choose_it','city_id','area_id','address','latitude','longitude','share_location','deleted'];
    public function category() {
        return $this->belongsTo('App\Category', 'category_id');
    }
    public function user() {
        return $this->belongsTo('App\User', 'user_id');
    }
    public function Product_user() {
        return $this->belongsTo('App\User', 'user_id')->select('id','image','name','email', 'phone');
    }
    public function sub_category() {
        return $this->belongsTo('App\SubCategory', 'sub_category_id');
    }
    public function subTwo_category() {
        return $this->belongsTo('App\SubTwoCategory', 'sub_category_two_id');
    }
    public function Plan() {
        return $this->belongsTo('App\Plan', 'plan_id');
    }
    public function images() {
        return $this->hasMany('App\ProductImage', 'product_id');
    }
    public function Features() {
        return $this->hasMany('App\Product_feature', 'product_id');
    }
    public function Views() {
        return $this->hasMany('App\Category_option_value', 'product_id');
    }
    public function City() {
        return $this->belongsTo('App\City', 'city_id');
    }
    public function Area() {
        return $this->belongsTo('App\Area', 'area_id')->select('id', 'title_' . session('local_api') . ' as title');
    }

    public function City_api() {
        if(session('local_api') == 'ar'){
            return $this->belongsTo('App\City', 'city_id')->select('id','title_ar as title');
        }else{
            return $this->belongsTo('App\City', 'city_id')->select('id','title_en as title');
        }
    }
    public function Area_api() {
        if(session('local_api') == 'ar'){
            return $this->belongsTo('App\Area', 'area_id')->select('id','title_ar as title');
        }else{
            return $this->belongsTo('App\Area', 'area_id')->select('id','title_en as title');
        }
    }
}
