<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Session;
use JD\Cloudder\Facades\Cloudder;
use App\Category_option_value;
use Illuminate\Http\Request;
use App\Helpers\APIHelpers;
use App\Product_feature;
use App\Plan_details;
use App\Product_view;
use App\ProductImage;
use Carbon\Carbon;
use App\Favorite;
use App\Setting;
use App\Product;
use App\User;
use App\Plan;
use App\City;
use App\Area;
use App\Ad;
use App\Category;
use App\SubCategory;

class ProductController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['filter','offer_ads', 'republish_ad','areas', 'cities', 'third_step_excute_pay', 'save_third_step_with_money', 'update_ad', 'select_ad_data', 'delete_my_ad', 'save_third_
        step', 'save_second_step', 'save_first_step', 'getdetails', 'last_seen', 'getOffersPage', 'getoffers', 'getproducts', 'getsearch', 'getFeatureOffers', 'getCitiesForFilter']]);
        //        --------------------------------------------- begin scheduled functions --------------------------------------------------------

        $expired = Product::where('status', 1)->whereDate('expiry_date', '<', Carbon::now())->get();
        foreach ($expired as $row) {
            $product = Product::find($row->id);
            $product->status = 2;
            $product->re_post = '0';
            $product->save();
        }

        $not_special = Product::where('status', 1)->where('is_special', '1')->whereDate('expire_special_date', '<', Carbon::now())->get();
        foreach ($not_special as $row) {
            $product_special = Product::find($row->id);
            $product_special->is_special = '0';
            $product_special->save();
        }
        $mytime = Carbon::now();
        $today = Carbon::parse($mytime->toDateTimeString())->format('Y-m-d H:i');
        $re_post_ad = Product::where('status', 1)->where('re_post', '1')->whereDate('re_post_date', '<', Carbon::now())->get();
        foreach ($re_post_ad as $row) {

            $product_re_post = Product::find($row->id);
            $product_re_post->created_at = Carbon::now();
            // to generate new next repost date ...
            $re_post = Plan_details::where('plan_id', $row->plan_id)->where('type', 're_post')->first();
            $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
            $final_expire_re_post_date = $final_pin_date->addDays($re_post->expire_days);

            $product_re_post->re_post_date = $final_expire_re_post_date;
            $product_re_post->save();
        }

        $pin_ad = Product::where('status', 1)->where('pin', '1')->whereDate('expire_pin_date', '<', Carbon::now())->get();
        foreach ($pin_ad as $row) {
            $product_pined = Product::find($row->id);
            $product_pined->pin = '0';
            $product_pined->save();
        }

        $pin_ad = Setting::where('id', 1)->whereDate('free_loop_date', '<', Carbon::now())->first();
        if ($pin_ad != null) {
            if ($pin_ad->is_loop_free_balance == 'y') {
                $all_users = User::where('active', 1)->get();
                foreach ($all_users as $row) {
                    $user = User::find($row->id);
                    $user->my_wallet = $user->my_wallet + $pin_ad->free_loop_balance;
                    $user->free_balance = $user->free_balance + $pin_ad->free_loop_balance;
                    $user->save();
                }
                $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                $final_free_loop_date = $final_pin_date->addDays($pin_ad->free_loop_period);
                $pin_ad->free_loop_date = $final_free_loop_date;
                $pin_ad->save();
            }
        }
//        --------------------------------------------- end scheduled functions --------------------------------------------------------


    }

    public function create(Request $request)
    {
        $user = auth()->user();
        if ($user->active == 0) {
            $response = APIHelpers::createApiResponse(true, 406, 'تم حظر حسابك', null);
            return response()->json($response, 406);
        }

        if ($user->free_ads_count == 0 && $user->paid_ads_count == 0) {
            $response = APIHelpers::createApiResponse(true, 406, 'ليس لديك رصيد إعلانات لإضافه إعلان جديد يرجي شراء باقه إعلانات', null);
            return response()->json($response, 406);
        }

        $validator = Validator::make($request->all(), [
            'category_id' => 'required',
            "type" => "required",
            "title" => "required",
            "description" => "required",
            "price" => "required",
            "image" => "required"
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', null);
            return response()->json($response, 406);
        }

        if ($user->free_ads_count > 0) {
            $count = $user->free_ads_count;
            $user->free_ads_count = $count - 1;
        } else {
            $count = $user->paid_ads_count;
            $user->paid_ads_count = $count - 1;
        }

        $user->save();

        $ad_period = Setting::find(1)['ad_period'];

        $product = new Product();
        $product->title = $request->title;
        $product->description = $request->description;
        $product->price = $request->price;
        $product->category_id = $request->category_id;
        $product->type = $request->type;
        $product->user_id = $user->id;
        $product->publication_date = date("Y-m-d H:i:s");
        $product->expiry_date = date('Y-m-d H:i:s', strtotime('+' . $ad_period . ' days'));

        $product->save();

        $image = $request->image;
        Cloudder::upload("data:image/jpeg;base64," . $image, null);
        $imagereturned = Cloudder::getResult();
        $image_id = $imagereturned['public_id'];
        $image_format = $imagereturned['format'];
        $image_new_name = $image_id . '.' . $image_format;
        $product_image = new ProductImage();
        $product_image->image = $image_new_name;
        $product_image->product_id = $product->id;
        $product_image->save();

        $product->image = $image_new_name;

        $response = APIHelpers::createApiResponse(false, 200, '', $product);
        return response()->json($response, 200);
    }

    public function uploadimages(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required',
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', null);
            return response()->json($response, 406);
        }

        $image = $request->image;
        Cloudder::upload("data:image/jpeg;base64," . $image, null);
        $imagereturned = Cloudder::getResult();
        $image_id = $imagereturned['public_id'];
        $image_format = $imagereturned['format'];
        $image_new_name = $image_id . '.' . $image_format;
        $product_image = new ProductImage();
        $product_image->image = $image_new_name;
        $product_image->product_id = $request->product_id;
        $product_image->save();
        $response = APIHelpers::createApiResponse(false, 200, '', $product_image);
        return response()->json($response, 200);
    }

    public function getdetails(Request $request)
    {
        Session::put('local_api',$request->lang);
        $user = auth()->user();
        $lang = $request->lang;
        Session::put('lang', $lang);
        $data = Product::with('Product_user')->with('City_api')
            ->select('id', 'title', 'main_image', 'description', 'price', 'publication_date as date', 'views', 'address', 'latitude', 'longitude', 'user_id', 'city_id', 'category_id')
            ->find($request->id)->makeHidden(['city_id', 'category_id']);
        if ($data->price == null) {
            if ($lang == 'ar') {
                $data->price = 'اسأل البائع';
            } else {
                $data->price = 'Ask the seller';
            }
        }
        $user_ip_address = $request->ip();
        if ($user == null) {
            $prod_view = Product_view::where('ip', $user_ip_address)->where('product_id', $data->id)->first();
            if ($prod_view == null) {
                $data_view['ip'] = $user_ip_address;
                $data_view['product_id'] = $data->id;
                Product_view::create($data_view);
                $views = Product_view::where('product_id', $data->id)->count();
                $product = Product::where('id', $request->id)->select('id', 'views')->first();
                $product->views = $views;
                $product->save();
            }
        } else {
            $prod_view = Product_view::where('ip', $user_ip_address)->where('product_id', $data->id)->first();
            if ($prod_view == null) {
                $data_view['user_id'] = $data->user_id;
                $data_view['ip'] = $user_ip_address;
                $data_view['product_id'] = $data->id;
                Product_view::create($data_view);
                $views = Product_view::where('product_id', $data->id)->count();
                $product = Product::where('id', $request->id)->select('id', 'views')->first();
                $product->views = $views;
                $product->save();
            } else {
                $prod_view->user_id = $user->id;
                $prod_view->save();
            }
        }
        // $features = Product_feature::where('product_id', $request->id)
        //     ->select('id', 'type', 'product_id', 'target_id', 'option_id')
        //     ->get();
        // $feature_data = null;
        // foreach ($features as $key => $feature) {
        //     $feature_data[$key]['image'] = $feature->Option->image;
        //     if ($feature->type == 'manual') {
        //         $feature_data[$key]['title'] = $feature->Option->title . ' : ' . $feature->target_id;
        //     } else if ($feature->type == 'option') {
        //         $feature_data[$key]['title'] = $feature->Option->title . ' : ' . $feature->Option_value->value;
        //     }
        // }
        if ($user) {
            $favorite = Favorite::where('user_id', $user->id)->where('product_id', $data->id)->first();
            if ($favorite) {
                $data->favorite = true;
            } else {
                $data->favorite = false;
            }
        } else {
            $data->favorite = false;
        }
        $date = date_create($data->date);
        $data->date = APIHelpers::get_month_day($date,$request->lang);
        $data->likes = Favorite::where('product_id', $data->id)->count();


        //to get ad images in array
        $images = ProductImage::where('product_id', $data->id)->pluck('image')->toArray();
        $images[count($images)] = $data->main_image;
        $data->images = $images;
        $user_ids[] = null;
        $user_other_ads = Product::where('id', '!=', $data->id)
            ->where('status', 1)
            ->where('publish', 'Y')
            ->where('deleted', 0)
            ->where('user_id', $data->user_id)
            ->with('city_api')
            ->select('id', 'title', 'price', 'main_image as image', 'created_at', 'views', 'city_id')
            ->limit(3)
            ->get()->makeHidden(['created_at', 'city_id'])
            ->map(function ($ads) use ($lang) {
                if ($ads->price == null) {
                    if ($lang == 'ar') {
                        $ads->price = 'اسأل البائع';
                    } else {
                        $ads->price = 'Ask the seller';
                    }
                }
                $ads->time = APIHelpers::get_time_day($ads->created_at , $lang);
                return $ads;
            });
        foreach ($user_other_ads as $key => $row) {
            $user_ids[$key] = $row->id;
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $row->id)->first();
                if ($favorite) {
                    $row->favorite = true;
                } else {
                    $row->favorite = false;
                }
            } else {
                $row->favorite = false;
            }
        }
        $related = Product::where('category_id', $data->category_id)
            ->where('id', '!=', $data->id)
            ->wherenotin('id', $user_ids)
            ->with('city_api')
            ->where('status', 1)
            ->where('publish', 'Y')
            ->where('deleted', 0)
            ->select('id', 'title', 'price', 'main_image as image', 'created_at', 'views', 'city_id')
            ->limit(3)
            ->get()->makeHidden(['created_at', 'city_id'])
            ->map(function ($ads) use ($lang) {
                if ($ads->price == null) {
                    if ($lang == 'ar') {
                        $ads->price = 'اسأل البائع';
                    } else {
                        $ads->price = 'Ask the seller';
                    }
                }
                $ads->time = APIHelpers::get_time_day($ads->created_at , $lang);
                return $ads;
            });
        for ($i = 0; $i < count($related); $i++) {
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $related[$i]['id'])->first();
                if ($favorite) {
                    $related[$i]['favorite'] = true;
                } else {
                    $related[$i]['favorite'] = false;
                }
            } else {
                $related[$i]['favorite'] = false;
            }
        }

        
        $response = APIHelpers::createApiResponse(false, 200, '', '', array('product' => $data, 'user_other_ads' => $user_other_ads, 'related' => $related), $request->lang);
        return response()->json($response, 200);
    }

    // islam code
    // get offers page
    public function getOffersPage(Request $request) {
        $allData['banner'] = Ad::select('id' ,'image' , 'type' , 'content')->where('type', '!=', 'offer')->inRandomOrder()->first();
        $products = Product::where('offer', 1)->where('status', 1)
        ->where('publish', 'Y')
        ->where('deleted', 0)
        ->select('id', 'title', 'price', 'main_image as image', 'created_at', 'views', 'city_id')
        ->with('city_api')
        ->simplePaginate(12);
        $data = $products->makeHidden(['created_at', 'city_id']);
        $products->data = $data;
        $user = auth()->user();
        for ($i =0; $i < count($products); $i ++) {
            $products[$i]['time'] = APIHelpers::get_time_day($products[$i]['created_at'], $request->lang);
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
        }
        $allData['products'] = $products;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $allData, $request->lang);
        return response()->json($response, 200);
    }

    public function getoffers(Request $request)
    {
        $products = Product::where('offer', 1)->select('id', 'title', 'price', 'type', 'publication_date as date')->orderBy('publication_date', 'DESC')->where('status', 1)->where('deleted', 0)->where('publish', 'Y')->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            $date = date_create($products[$i]['date']);
            $products[$i]['date'] = date_format($date, 'd M Y');
            $products[$i]['image'] = ProductImage::where('product_id', $products[$i]['id'])->select('image')->first()['image'];
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $products, $request->lang);
        return response()->json($response, 200);
    }

    public function getproducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, 'بعض الحقول مفقودة', 'بعض الحقول مفقودة', null, $request->lang);
            return response()->json($response, 406);
        }

        if ($request->type) {
            $type = [$request->type];
        } else {
            $type = [1, 2];
        }

        $products = Product::where('status', 1)->where('deleted', 0)->where('publish', 'Y')->whereIn('type', $type)->where('category_id', $request->category_id)->select('id', 'title', 'price', 'type', 'publication_date as date')->orderBy('publication_date', 'DESC')->simplePaginate(12);

        for ($i = 0; $i < count($products); $i++) {
            $date = date_create($products[$i]['date']);
            $products[$i]['date'] = date_format($date, 'd M Y');
            $products[$i]['image'] = ProductImage::where('product_id', $products[$i]['id'])->first()['image'];
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $products, $request->lang);
        return response()->json($response, 200);

    }

    public function getsearch(Request $request)
    {
        Session::put('local_api',$request->lang);
        $lang = $request->lang;
        $validator = Validator::make($request->all(), [
            'search' => 'required'
        ]);
        $search = $request->search;
        $products = Product::where('publish', 'Y')
            ->where('deleted', 0)
            ->where('status', 1)
            ->select('id', 'title', 'price', 'main_image as image', 'area_id', 'city_id', 'created_at', 'pin')
            ->Where(function ($query) use ($search) {
                $query->Where('title', 'like', '%' . $search . '%');
            })
            ->with('Area')
            ->with('City_api')
            ->orderBy('pin', 'desc')
            ->orderBy('created_at', 'desc')
            ->simplePaginate(12);
        $data = $products->makeHidden(['created_at', 'area_id', 'city_id']);
        $products->data = $data;
        for ($i = 0; $i < count($products); $i++) {
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
            $products[$i]['time'] =APIHelpers::get_time_day($products[$i]['created_at'],$lang);
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $products, $request->lang);
        return response()->json($response, 200);

    }

    public function filter(Request $request)
    {
        $lang = $request->lang ;
        $result = Product::query();
        $result = $result->where('publish', 'Y')->where('status', 1)->where('deleted', 0);
        if ($request->from_price != null && $request->to_price != null) {
            $result = $result->whereRaw('price BETWEEN ' . $request->from_price . ' AND ' . $request->to_price . '');
        }
        if ($request->area_id != null) {
            $result = $result->where('area_id', $request->area_id);
        }
        if ($request->category_id != null) {
            $result = $result->where('category_id', $request->category_id);
        }
        if ($request->sub_category_level1_id != null) {
            $result = $result->where('sub_category_id', $request->sub_category_level1_id);
        }
        if ($request->sub_category_level2_id != null) {
            $result = $result->where('sub_category_two_id', $request->sub_category_level2_id);
        }
        if ($request->sub_category_level3_id != null) {
            $result = $result->where('sub_category_three_id', $request->sub_category_level3_id);
        }
        if ($request->sub_category_level4_id != null) {
            $result = $result->where('sub_category_four_id', $request->sub_category_level4_id);
        }
        if ($request->sub_category_level5_id != null) {
            $result = $result->where('sub_category_five_id', $request->sub_category_level5_id);
        }
        if($request->options != null){
            $product_ids[] = null;
            foreach ($request->options as $key => $row){
                $product_ids = Product_feature::where('option_id',$row['option_id'])->where('target_id',$row['option_value'])->pluck('product_id')->toArray();
            }
            $result = $result->whereIn('id', $product_ids);
        }
        $products = $result->select('id', 'title', 'price', 'main_image as image', 'pin', 'created_at')
                        ->orderBy('pin', 'desc')
                        ->orderBy('created_at', 'desc')
                        ->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            if ($products[$i]['price'] == null) {
                if ($lang == 'ar') {
                    $products[$i]['price'] = 'اسأل البائع';
                } else {
                    $products[$i]['price'] = 'Ask the seller';
                }
            }
            $views = Product_view::where('product_id', $products[$i]['id'])->get()->count();
            $products[$i]['views'] = $views;
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
            $products[$i]['time'] = APIHelpers::get_month_day($products[$i]['created_at'], $request->lang);
        }
        $data['products'] = $products;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $products, $request->lang);
        return response()->json($response, 200);
    }
    public function getFeatureOffers(Request $request)
    {
        $products = Product::where('feature', 1)
            ->select('id', 'title', 'price')
            ->orderBy('publication_date', 'DESC')
            ->where('status', 1)
            ->where('deleted', 0)
            ->where('publish', 'Y')
            ->simplePaginate(12);
        for ($i = 0; $i < count($products); $i++) {
            $products[$i]['image'] = ProductImage::where('product_id', $products[$i]['id'])->select('image')->first()['image'];
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $products, $request->lang);
        return response()->json($response, 200);
    }
    //nasser code
    //to create ad you need 3 steps
    public function save_first_step(Request $request)
    {
        $input = $request->all();
        $validator = Validator::make($input, [
            'category_id' => 'required',
            'sub_category_id' => 'required',
            'title' => 'required',
            'main_image' => 'required',
            // 'images' => 'required',
            'city_id' => 'required|exists:cities,id',
            // 'area_id' => 'exists:areas,id',
            'share_location' => 'required',
            // 'options' => 'required',
            'plan_id' => 'required',
        ]);
        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, $validator->messages()->first(), $validator->messages()->first(), (object)[], $request->lang);
            return response()->json($response, 406);
        } else {
            $user = auth()->user();
            if ($user != null) {
                $input['user_id'] = $user->id;
                $selected_plan = Plan::where('id', $request->plan_id)->first();
                $plan_price = $selected_plan->price;
                if ($plan_price <= $user->my_wallet) {
                    //to select expire days of selected plane
                    $plan_detail = Plan_details::where('plan_id', $request->plan_id)->where('type', 'expier_num')->first();
                    $expire_days = $plan_detail->expire_days;
                    $user->my_wallet = $user->my_wallet - $plan_price;
                    if ($user->free_balance >= $plan_price) {
                        $user->free_balance = $user->free_balance - $plan_price;
                    } else if ($user->payed_balance >= $plan_price) {
                        $user->payed_balance = $user->payed_balance - $plan_price;
                    } else {
                        $free_balance = $user->free_balance;  //70
                        $payed_balance = $user->payed_balance;  //30
                        $price = $plan_price; //100
                        $after_min_free = $price - $free_balance;  // 100 - 70 = 30
                        if ($after_min_free <= $payed_balance && $after_min_free > 0) {
                            // 30 <= 30 && 30 < 0
                            $user->free_balance = 0;
                            $user->payed_balance = $user->payed_balance - $after_min_free;
                        } else if ($after_min_free > $payed_balance && $after_min_free > 0) {
                            $after_min_payed = $price - $payed_balance;
                            $user->free_balance = $user->free_balance - $after_min_payed;
                            $user->payed_balance = 0;
                        }
                    }
                    $user->save();
                    //to get the expire_date of ad
                    $mytime = Carbon::now();
                    $today = Carbon::parse($mytime->toDateTimeString())->format('Y-m-d H:i');
                    $pin = Plan_details::where('plan_id', $request->plan_id)->where('type', 'pin')->first();
                    if ($pin != null) {
                        $expire_pin_date = $pin->expire_days;
                        $input['pin'] = 1;
                        //to create expire pin date
                        $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                        $final_expire_pin_date = $final_pin_date->addDays($expire_pin_date);
                        $input['expire_pin_date'] = $final_expire_pin_date;
                    }
                    $re_post = Plan_details::where('plan_id', $request->plan_id)->where('type', 're_post')->first();
                    if ($re_post != null) {
                        $expire_re_post_date = $re_post->expire_days;
                        $input['re_post'] = '1';
                        //to create expire pin date
                        $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                        $final_expire_re_post_date = $final_pin_date->addDays($expire_re_post_date);
                        $input['re_post_date'] = $final_expire_re_post_date;
                    }
                    $special = Plan_details::where('plan_id', $request->plan_id)->where('type', 'special')->first();
                    if ($special != null) {
                        $expire_special_date = $special->expire_days;
                        $input['is_special'] = '1';
                        //to create expire pin date
                        $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                        $final_expire_special_date = $final_pin_date->addDays($expire_special_date);
                        $input['expire_special_date'] = $final_expire_special_date;
                    }
                    $final_today = Carbon::createFromFormat('Y-m-d H:i', $today);
                    $expire_date = $final_today->addDays($expire_days);
                    $input['publish'] = 'Y';
                    $input['plan_id'] = $request->plan_id;
                    $input['publication_date'] = $today;
                    $input['expiry_date'] = $expire_date;
                    //save second step of creation ...
                    $image = $request->main_image;
                    Cloudder::upload("data:image/jpeg;base64," . $image, null);
                    $imagereturned = Cloudder::getResult();
                    $image_id = $imagereturned['public_id'];
                    $image_format = $imagereturned['format'];
                    $image_new_name = $image_id . '.' . $image_format;
                    $input['main_image'] = $image_new_name;
                    //create final
                    $ad_data = Product::create($input);

                    //save product feature ...
                    // foreach ($request->options as $key => $option) {
                    //     if (is_numeric($option['option_value'])) {
                    //         $option_values = Category_option_value::where('id', $option['option_value'])->first();
                    //         if ($option_values != null) {
                    //             $feature_data['type'] = 'option';
                    //         } else {
                    //             $feature_data['type'] = 'manual';
                    //         }
                    //     } else {
                    //         $feature_data['type'] = 'manual';
                    //     }
                    //     $feature_data['product_id'] = $ad_data->id;
                    //     $feature_data['target_id'] = $option['option_value'];
                    //     $feature_data['option_id'] = $option['option_id'];
                    //     Product_feature::create($feature_data);
                    // }
                    if ($request->images && count($request->images) > 0) {
                        foreach ($request->images as $image) {
                            Cloudder::upload("data:image/jpeg;base64," . $image, null);
                            $imagereturned = Cloudder::getResult();
                            $image_id = $imagereturned['public_id'];
                            $image_format = $imagereturned['format'];
                            $image_name = $image_id . '.' . $image_format;
    
                            $data['product_id'] = $ad_data->id;
                            $data['image'] = $image_name;
                            ProductImage::create($data);
                        }
                    }
                    
                    $response = APIHelpers::createApiResponse(false, 200, 'your ad added successfully', 'تم أنشاء الاعلان بنجاح', (object)[], $request->lang);
                    return response()->json($response, 200);
                } else {
                    $response = APIHelpers::createApiResponse(true, 406, 'Your wallet does not contain enough amount to create an ad',
                        'محفظتك لا تحتوى على المبلغ الكافى لانشاء الاعلانا', (object)[], $request->lang);
                    return response()->json($response, 406);
                }
            } else {
                $response = APIHelpers::createApiResponse(true, 406, '', 'يجب تسجيل الدخول اولا', (object)[], $request->lang);
                return response()->json($response, 406);
            }
        }
    }
    public function save_second_step(Request $request)
    {
        $input = $request->all();
        
        $validator = Validator::make($input, [
            'ad_id' => 'required|exists:products,id',
            'main_image' => 'required',
            'images' => 'required',
        ]);
        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, $validator->messages()->first(), $validator->messages()->first(), $validator->messages()->first(), $request->lang);
            return response()->json($response, 406);
        } else {
            if (auth()->user() != null) {
                $image = $request->main_image;
                Cloudder::upload("data:image/jpeg;base64," . $image, null);
                $imagereturned = Cloudder::getResult();
                $image_id = $imagereturned['public_id'];
                $image_format = $imagereturned['format'];
                $image_new_name = $image_id . '.' . $image_format;
                $product = Product::where('id', $request->ad_id)->first();
                $product->main_image = $image_new_name;
                $product->save();

                foreach ($request->images as $image) {
                    Cloudder::upload("data:image/jpeg;base64," . $image, null);
                    $imagereturned = Cloudder::getResult();
                    $image_id = $imagereturned['public_id'];
                    $image_format = $imagereturned['format'];
                    $image_name = $image_id . '.' . $image_format;

                    $data['product_id'] = $request->ad_id;
                    $data['image'] = $image_name;
                    ProductImage::create($data);
                }
                $response = APIHelpers::createApiResponse(false, 200, 'image saved successfully', 'تم حفظ الصور بنجاح', null, $request->lang);
                return response()->json($response, 200);
            } else {
                $response = APIHelpers::createApiResponse(true, 406, '', 'يجب تسجيل الدخول اولا', null, $request->lang);
                return response()->json($response, 406);
            }
        }
    }

    public function save_third_step(Request $request, $ad_id, $plan_id)
    {
        //when user have enghe money in wallet
        if (auth()->user() != null) {
            $user = User::where('id', auth()->user()->id)->first();
            $selected_plan = Plan::where('id', $plan_id)->first();
            $plan_ads_number = $selected_plan->ads_count;
            $plan_price = $selected_plan->price;
            if ($plan_price <= $user->my_wallet) {
                //to select expire days of selected plane
                $plan_detail = Plan_details::where('plan_id', $plan_id)->where('type', 'expier_num')->first();
                $expire_days = $plan_detail->expire_days;

                $user->my_wallet = $user->my_wallet - $plan_price;
                if ($user->free_balance >= $plan_price) {
                    $user->free_balance = $user->free_balance - $plan_price;
                } else if ($user->payed_balance >= $plan_price) {
                    $user->payed_balance = $user->payed_balance - $plan_price;
                } else {
                    $free_balance = $user->free_balance;  //70
                    $payed_balance = $user->payed_balance;  //30
                    $price = $plan_price; //100
                    $after_min_free = $price - $free_balance;  // 100 - 70 = 30
                    if ($after_min_free <= $payed_balance && $after_min_free > 0) {
                        // 30 <= 30 && 30 < 0
                        $user->free_balance = 0;
                        $user->payed_balance = $user->payed_balance - $after_min_free;
                    } else if ($after_min_free > $payed_balance && $after_min_free > 0) {
                        $after_min_payed = $price - $payed_balance;
                        $user->free_balance = $user->free_balance - $after_min_payed;
                        $user->payed_balance = 0;
                    }
                }
                $user->save();

                //to get the expire_date of ad
                $mytime = Carbon::now();
                $today = Carbon::parse($mytime->toDateTimeString())->format('Y-m-d H:i');
                $ad_data = null;
                $pin = Plan_details::where('plan_id', $plan_id)->where('type', 'pin')->first();
                if ($pin != null) {
                    $expire_pin_date = $pin->expire_days;
                    $ad_data['pin'] = 1;
                    //to create expire pin date
                    $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                    $final_expire_pin_date = $final_pin_date->addDays($expire_pin_date);
                    $ad_data['expire_pin_date'] = $final_expire_pin_date;
                }
                $re_post = Plan_details::where('plan_id', $plan_id)->where('type', 're_post')->first();
                if ($re_post != null) {
                    $expire_re_post_date = $re_post->expire_days;
                    $ad_data['re_post'] = '1';
                    //to create expire pin date
                    $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                    $final_expire_re_post_date = $final_pin_date->addDays($expire_re_post_date);
                    $ad_data['re_post_date'] = $final_expire_re_post_date;
                }
                $special = Plan_details::where('plan_id', $plan_id)->where('type', 'special')->first();
                if ($special != null) {
                    $expire_special_date = $special->expire_days;
                    $ad_data['is_special'] = '1';
                    //to create expire pin date
                    $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                    $final_expire_special_date = $final_pin_date->addDays($expire_special_date);
                    $ad_data['expire_special_date'] = $final_expire_special_date;
                }

                $final_today = Carbon::createFromFormat('Y-m-d H:i', $today);
                $expire_date = $final_today->addDays($expire_days);

                $ad_data['publish'] = 'Y';
                $ad_data['plan_id'] = $plan_id;
                $ad_data['publication_date'] = $today;
                $ad_data['expiry_date'] = $expire_date;
                Product::where('id', $ad_id)->update($ad_data);

                $response = APIHelpers::createApiResponse(false, 200, 'your ad added successfully', 'تم أنشاء الاعلان بنجاح', null, $request->lang);
                return response()->json($response, 200);
            } else {
                $response = APIHelpers::createApiResponse(true, 406, 'Your wallet does not contain enough amount to create an ad',
                    'محفظتك لا تحتوى على المبلغ الكافى لانشاء الاعلانا', null, $request->lang);
                return response()->json($response, 406);
            }
        } else {
            $response = APIHelpers::createApiResponse(true, 406, '', 'يجب تسجيل الدخول اولا', null, $request->lang);
            return response()->json($response, 406);
        }
    }

    // add balance to wallet
    public function save_third_step_with_money(Request $request)
    {
        $plan = Plan::where('id', $request->plan_id)->first();
        if ($plan == null) {
            $response = APIHelpers::createApiResponse(true, 406, 'يجب اختيار خطة صحيحة', 'plan not found ', null, $request->lang);
            return response()->json($response, 406);
        }

        $products = Product::where('id', $request->ad_id)->first();

        if ($products == null) {
            $response = APIHelpers::createApiResponse(true, 406, 'يجب اختيار اعلان صحيحة', 'Ad not found ', null, $request->lang);
            return response()->json($response, 406);
        }
        $user = auth()->user();
        $root_url = $request->root();
        $path = 'https://api.myfatoorah.com/v2/SendPayment';
        $token = "bearer GSHFK6o5YrsLRLJSPPHKDJp9XjnA6Tvr5pMhryXO8Cdwa5Lk8hqRWqIvlC8AoSD2CwFlGBJEt7j2e-MrKv_0iHb8iJO-P-6_s4KvKjgE5HfBDGYQKXiUZ4H8yfrYp9f5vSuOGVDHyeiRaTW4HWxbE9OhNS8_fPJ711xV1aIKvey96tn6ZCsXekbX6H9XvYYrG-iPuPejNaoCb9gxNrqDUzcjF_aVYmiwsVdApTGWZOwKP_ns6hQC0Th9Fjn39KuMHxzsSUNe_s1ss73YFBkxoxWxz6A9cT5ZmdDdFlFeYMpCh-nVv1cHZkUByQZBYOgwpS5nAc1bO9B-UoQduWvVxgnEZzL-2afMzKuQwRrqhasHBzrKR-KVLKvRmWD8uhdoKM1GAxRJwitN8UDosT6WB9W_MkxKBXaaV_mSK5Qvmk6-2IEk4NTt1WFTNQX82tjN4hG-hTpZLkrofxztcxeAu6sKXJHITk_7bBDu7pqXRX3ru3ChczL2dBVMI1zXakItqGyaeB60TUvEwPMzO-6u235UbeUIJJLoeIyuWmyQS39xkR2bNGaYu4yQia4CkxpLDvBiLDQ1XkIYKsCYsH-NkXfGqGhCoAjPsF8j9QRE87bTCflhii5MVL8ouOtKc5ZAjaTu_yQC9yfrq7iMLDTBkZrzdls4CHtxEKDgTVSkVEGO8yxSW7--Gq-SLF1hnw8xzhvtY9SrsyRkDLpHdyvDedULpuRkPumWpsPAai6YbJXQMyGa";
        $headers = array(
            'Authorization:' . $token,
            'Content-Type:application/json'
        );
        $call_back_url = $root_url . "/api/ad/save_third_step/excute_pay?user_id=" . $user->id . "&plan_id=" . $request->plan_id . "&ad_id=" . $request->ad_id;
        $error_url = $root_url . "/api/pay/error";
        $fields = array(
            "CustomerName" => $user->name,
            "NotificationOption" => "LNK",
            "InvoiceValue" => $plan->price,
            "CallBackUrl" => $call_back_url,
            "ErrorUrl" => $error_url,
            "Language" => "AR",
            "CustomerEmail" => $user->email
        );
        $payload = json_encode($fields);
        $curl_session = curl_init();
        curl_setopt($curl_session, CURLOPT_URL, $path);
        curl_setopt($curl_session, CURLOPT_POST, true);
        curl_setopt($curl_session, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_session, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl_session, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_session, CURLOPT_IPRESOLVE, CURLOPT_IPRESOLVE);
        curl_setopt($curl_session, CURLOPT_POSTFIELDS, $payload);
        $result = curl_exec($curl_session);
        curl_close($curl_session);
        $result = json_decode($result);
        $data['url'] = $result->Data->InvoiceURL;
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // excute pay
    public function third_step_excute_pay(Request $request)
    {
        //after customer pay the price of plan ..
        $plan_id = $request->plan_id;
        if (auth()->user() != null) {
            $user = User::where('id', $request->user_id)->first();
            $selected_plan = Plan::where('id', $plan_id)->first();
            $plan_ads_number = $selected_plan->ads_count;
            $plan_price = $selected_plan->price;
            //to select expire days of selected plane
            $plan_detail = Plan_details::where('plan_id', $plan_id)->where('type', 'expier_num')->first();
            $expire_days = $plan_detail->expire_days;
            //to get the expire_date of ad
            $mytime = Carbon::now();
            $today = Carbon::parse($mytime->toDateTimeString())->format('Y-m-d H:i');
            $ad_data = null;
            $pin = Plan_details::where('plan_id', $plan_id)->where('type', 'pin')->first();
            if ($pin != null) {
                $expire_pin_date = $pin->expire_days;
                $ad_data['pin'] = 1;
                //to create expire pin date
                $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                $final_expire_pin_date = $final_pin_date->addDays($expire_pin_date);
                $ad_data['expire_pin_date'] = $final_expire_pin_date;
            }
            $re_post = Plan_details::where('plan_id', $plan_id)->where('type', 're_post')->first();
            if ($re_post != null) {
                $expire_re_post_date = $re_post->expire_days;
                $ad_data['re_post'] = '1';
                //to create expire pin date
                $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                $final_expire_re_post_date = $final_pin_date->addDays($expire_re_post_date);
                $ad_data['re_post_date'] = $final_expire_re_post_date;
            }
            $special = Plan_details::where('plan_id', $plan_id)->where('type', 'special')->first();
            if ($special != null) {
                $expire_special_date = $special->expire_days;
                $ad_data['is_special'] = '1';
                //to create expire pin date
                $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                $final_expire_special_date = $final_pin_date->addDays($expire_special_date);
                $ad_data['expire_special_date'] = $final_expire_special_date;
            }

            $final_today = Carbon::createFromFormat('Y-m-d H:i', $today);
            $expire_date = $final_today->addDays($expire_days);

            $ad_data['publish'] = 'Y';
            $ad_data['plan_id'] = $plan_id;
            $ad_data['publication_date'] = $today;
            $ad_data['expiry_date'] = $expire_date;
            Product::where('id', $request->ad_id)->update($ad_data);

            return redirect('api/pay/success');
        } else {
            return redirect('api/pay/error');
        }
    }

    public function republish_ad(Request $request)
    {
        $user = auth()->user();
        if ($user == null) {
            $response = APIHelpers::createApiResponse(true, 406, 'you should login first', 'يجب تسجيل الدخول اولا', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($user->active == 0) {
            $response = APIHelpers::createApiResponse(true, 406, 'your account un actived', 'تم حظر حسابك', null, $request->lang);
            return response()->json($response, 406);
        }
        $plan = Plan::where('id', $request->plan_id)->first();
        if ($user->my_wallet < $plan->price) {
            $response = APIHelpers::createApiResponse(true, 406, 'you don`t have enough balance to republish ad , please buy ads package', 'ليس لديك رصيد إعلانات لتجديد الإعلان يرجي شراء باقه إعلانات', null, $request->lang);
            return response()->json($response, 406);
        }
        $product = Product::where('id', $request->product_id)->where('user_id', $user->id)->first();
        if ($product->status == 1) {
            $response = APIHelpers::createApiResponse(true, 406, 'this ad not ended yet', 'هذا الاعلان لم ينتهى بعد', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($product->deleted == 1) {
            $response = APIHelpers::createApiResponse(true, 406, 'this ad deleted before', 'هذا الاعلان تم حذفة', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($product) {
            $plan_price = $plan->price;
            //to select expire days of selected plane
            $plan_detail = Plan_details::where('plan_id', $request->plan_id)->where('type', 'expier_num')->first();
            $expire_days = $plan_detail->expire_days;

            $user->my_wallet = $user->my_wallet - $plan_price;
            if ($user->free_balance >= $plan_price) {
                $user->free_balance = $user->free_balance - $plan_price;
            } else if ($user->payed_balance >= $plan_price) {
                $user->payed_balance = $user->payed_balance - $plan_price;
            } else {
                $free_balance = $user->free_balance;  //70
                $payed_balance = $user->payed_balance;  //30
                $price = $plan_price; //100
                $after_min_free = $price - $free_balance;  // 100 - 70 = 30
                if ($after_min_free <= $payed_balance && $after_min_free > 0) {
                    // 30 <= 30 && 30 < 0
                    $user->free_balance = 0;
                    $user->payed_balance = $user->payed_balance - $after_min_free;
                } else if ($after_min_free > $payed_balance && $after_min_free > 0) {
                    $after_min_payed = $price - $payed_balance;
                    $user->free_balance = $user->free_balance - $after_min_payed;
                    $user->payed_balance = 0;
                }
            }
            $user->save();
            //to get the expire_date of ad
            $mytime = Carbon::now();
            $today = Carbon::parse($mytime->toDateTimeString())->format('Y-m-d H:i');
            $pin = Plan_details::where('plan_id', $request->plan_id)->where('type', 'pin')->first();
            if ($pin != null) {
                $expire_pin_date = $pin->expire_days;
                $product->pin = 1;
                //to create expire pin date
                $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                $final_expire_pin_date = $final_pin_date->addDays($expire_pin_date);
                $product->expire_pin_date = $final_expire_pin_date;
            }
            $re_post = Plan_details::where('plan_id', $request->plan_id)->where('type', 're_post')->first();
            if ($re_post != null) {
                $expire_re_post_date = $re_post->expire_days;
                $product->re_post = '1';
                //to create expire pin date
                $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                $final_expire_re_post_date = $final_pin_date->addDays($expire_re_post_date);
                $product->re_post_date = $final_expire_re_post_date;
            }
            $special = Plan_details::where('plan_id', $request->plan_id)->where('type', 'special')->first();
            if ($special != null) {
                $expire_special_date = $special->expire_days;
                $product->is_special = '1';
                //to create expire pin date
                $final_pin_date = Carbon::createFromFormat('Y-m-d H:i', $today);
                $final_expire_special_date = $final_pin_date->addDays($expire_special_date);
                $product->expire_special_date = $final_expire_special_date;
            }

            $final_today = Carbon::createFromFormat('Y-m-d H:i', $today);
            $expire_date = $final_today->addDays($expire_days);
            $product->plan_id = $request->plan_id;
            $product->expiry_date = $expire_date;
            $product->status = 1;
            $product->publish = 'Y';
            $product->save();
            $response = APIHelpers::createApiResponse(false, 200, 'republish done', 'تم اعادة النشر بنجاح', null, $request->lang);
            return response()->json($response, 200);

        } else {
            $response = APIHelpers::createApiResponse(true, 406, 'ليس لديك الصلاحيه لتجديد هذا الاعلان', '', null, $request->lang);
            return response()->json($response, 406);
        }
    }
    public function select_ended_ads(Request $request)
    {
        $ads['ended_ads'] = Product::where('status', 2)
            ->where('deleted', 0)
            ->where('user_id', auth()->user()->id)
            ->select('id', 'title', 'price', 'main_image as image', 'pin', 'views', 'city_id', 'created_at', 'plan_id')
            ->orderBy('created_at', 'desc')
            ->with('City_api')->simplePaginate(12);
        $ended_ads = $ads['ended_ads']->makeHidden(['city_id', 'created_at']);
        $ads['ended_ads']->data = $ended_ads;
        $ads['current_ads'] = Product::where('status', 1)
            ->where('publish', 'Y')
            ->where('deleted', 0)
            ->where('user_id', auth()->user()->id)
            ->select('id', 'title', 'price', 'main_image as image', 'pin', 'views', 'city_id', 'created_at', 'plan_id')
            ->orderBy('created_at', 'desc')
            ->with('City_api')->simplePaginate(12);
        $current_ads = $ads['current_ads']->makeHidden(['city_id', 'created_at']);
        $ads['current_ads']->data = $current_ads;
        $user = auth()->user();
        for ($i = 0; $i < count($ads['current_ads']); $i ++) {
            $ads['current_ads'][$i]['time'] =APIHelpers::get_time_day($ads['current_ads'][$i]['created_at'],$request->lang);
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $ads['current_ads'][$i]['id'])->first();
                if ($favorite) {
                    $ads['current_ads'][$i]['favorite'] = true;
                } else {
                    $ads['current_ads'][$i]['favorite'] = false;
                }
            } else {
                $ads['current_ads'][$i]['favorite'] = false;
            }
        }
        for ($n = 0; $n < count($ads['ended_ads']); $n ++) {
            $ads['ended_ads'][$n]['time'] =APIHelpers::get_time_day($ads['ended_ads'][$n]['created_at'],$request->lang);
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $ads['ended_ads'][$n]['id'])->first();
                if ($favorite) {
                    $ads['ended_ads'][$n]['favorite'] = true;
                } else {
                    $ads['ended_ads'][$n]['favorite'] = false;
                }
            } else {
                $ads['ended_ads'][$n]['favorite'] = false;
            }
        }
        
        $response = APIHelpers::createApiResponse(false, 200, '', '', $ads, $request->lang);
        return response()->json($response, 200);
        
    }

    public function last_seen(Request $request)
    {
        $user = auth()->user();
        
        if ($user == null) {
            $response = APIHelpers::createApiResponse(true, 406, 'you should login first', 'يجب تسجيل الدخول اولا', null, $request->lang);
            return response()->json($response, 406);
        }

        $ads = Product_view::where('user_id', auth()->user()->id)
            ->orderby('id', 'desc')
            ->pluck('product_id')->toArray();
        
        
        $products = Product::whereIn('id', $ads)->where('deleted', 0)->where('publish', 'Y')->select('id', 'title', 'price', 'main_image as image', 'pin', 'views', 'city_id', 'created_at')->with('City_api')->simplePaginate(12);
        // dd($ads);
        for ($i = 0; $i < count($products); $i ++) {
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $products[$i]['id'])->first();
                if ($favorite) {
                    $products[$i]['favorite'] = true;
                } else {
                    $products[$i]['favorite'] = false;
                }
            } else {
                $products[$i]['favorite'] = false;
            }
        }
        
        
        $response = APIHelpers::createApiResponse(false, 200, '', '', $products, $request->lang);
        return response()->json($response, 200);
        
    }

    public function offer_ads(Request $request)
    {
        $user = auth()->user();
        $lang = $request->lang;
        if ($user == null) {
            $response = APIHelpers::createApiResponse(true, 406, 'you should login first', 'يجب تسجيل الدخول اولا', null, $request->lang);
            return response()->json($response, 406);
        }
        if ($lang == 'ar') {
            $offer_image = Setting::where('id', 1)->first()->offer_image;
        } else {
            $offer_image = Setting::where('id', 1)->first()->offer_image_en;
        }
        $ads = Product::where('offer', 1)
            ->where('status', 1)
            ->where('deleted', 0)
            ->where('publish', 'Y')
            ->orderBy('created_at', 'desc')
            ->get();
        $inc = 0;
        $data = [];
        foreach ($ads as $key => $row) {
            $data[$inc]['id'] = $row->id;
            $data[$inc]['title'] = $row->title;
            $data[$inc]['image'] = $row->main_image;
            $data[$inc]['price'] = $row->price;
            $data[$inc]['description'] = $row->description;
            $favorite = Favorite::where('user_id', $user->id)->where('product_id', $row->id)->first();
            if ($favorite) {
                $data[$inc]['favorite'] = true;
            } else {
                $data[$inc]['favorite'] = false;
            }
            $inc = $inc + 1;
        }
        
        $response = APIHelpers::createApiResponse(false, 200, '', '', array('offer_image' => $offer_image, 'data' => $data), $request->lang);
        return response()->json($response, 200);
        
    }

    public function select_current_ads(Request $request)
    {
        $ads = Product::where('status', 1)
            ->where('publish', 'Y')
            ->where('deleted', 0)
            ->where('user_id', auth()->user()->id)
            ->select('id', 'title', 'price', 'main_image', 'views', 'pin', 'publication_date')
            ->orderBy('pin', 'desc')
            ->orderBy('created_at', 'desc')
            ->simplePaginate(12)
            ->map(function ($ads) {
                $ads->views = Product_view::where('product_id', $ads->id)->count();
                return $ads;
            });
        if (count($ads) == 0) {
            $response = APIHelpers::createApiResponse(false, 200, 'no ended ads yet !', ' !لا يوجد اعلانات منتهيه حتى الان', null, $request->lang);
            return response()->json($response, 200);
        } else {
            $response = APIHelpers::createApiResponse(false, 200, '', '', $ads, $request->lang);
            return response()->json($response, 200);
        }
    }

    public function select_all_ads(Request $request)
    {
        $ads = Product::where('status', 1)
            ->where('publish', 'Y')
            ->where('deleted', 0)
            ->where('user_id', auth()->user()->id)
            ->select('id', 'title', 'price', 'main_image', 'pin', 'user_id')
            ->orderBy('pin', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();
        if (count($ads) == 0) {
            $response = APIHelpers::createApiResponse(false, 200, 'no  ads until now !', ' !لا يوجد اعلانات حتى الان', null, $request->lang);
            return response()->json($response, 200);
        } else {
            $response = APIHelpers::createApiResponse(false, 200, '', '', $ads, $request->lang);
            return response()->json($response, 200);
        }
    }

    public function delete_my_ad(Request $request, $id)
    {
        $user = auth()->user();
        if ($user != null) {
            $product = Product::where('id', $id)->first();
            if ($product != null) {
                if ($product->user_id == $user->id) {
                    $product->deleted = 1;
                    $product->save();
                    $response = APIHelpers::createApiResponse(false, 200, 'deleted', 'تم الحذف بنجاح', null, $request->lang);
                    return response()->json($response, 200);
                } else {
                    $response = APIHelpers::createApiResponse(true, 406, 'this not your ad', 'لا تمتلك هذا الاعلان !!', null, $request->lang);
                    return response()->json($response, 406);
                }
            } else {
                $response = APIHelpers::createApiResponse(true, 406, 'no ad of this id', 'لا يوجد اعلان بهذا ال id', null, $request->lang);
                return response()->json($response, 406);
            }
        } else {
            $response = APIHelpers::createApiResponse(true, 406, 'you should login first ', 'يجب تسجيل الدخول أولا !!', null, $request->lang);
            return response()->json($response, 406);
        }
    }
    public function select_ad_data(Request $request, $id)
    {
        Session::put('local_api',$request->lang);
        $data['ad'] = Product::where('id', $id)
            ->with('City_api')
            ->with('Area_api')
            ->select('id', 'category_id', 'sub_category_id', 'title', 'price', 'description', 'main_image','city_id','area_id','share_location','latitude','longitude', 'address')
            ->first();
            
        $category = Category::where('id', $data['ad']['category_id'])->select('title_' . $request->lang . ' as title')->first();
        $sub_category = SubCategory::where('id', $data['ad']['sub_category_id'])->select('title_' . $request->lang . ' as title')->first();
        $data['ad']['category_name'] = $category->title;
        $data['ad']['sub_category_name'] = $sub_category->title;
        
        $data['ad']['ad_images'] = ProductImage::where('product_id', $id)->select('id', 'image', 'product_id')->get();
        $response = APIHelpers::createApiResponse(false, 200, 'data shown', 'تم أظهار البيانات', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function remove_main_image(Request $request, $id)
    {
        $data['main_image'] = null;
        $final_data = Product::where('id', $id)->update($data);

        if ($final_data == 1) {
            $data_f['status'] = true;
            $response = APIHelpers::createApiResponse(false, 200, 'data updated', 'تم تعديل البيانات', $data_f, $request->lang);
            return response()->json($response, 200);
        } else {
            $data_f['status'] = false;
            $response = APIHelpers::createApiResponse(true, 406, 'not updated', 'لم يتم التعديل', $data_f, $request->lang);
            return response()->json($response, 406);
        }
    }

    public function remove_product_image(Request $request, $image_id)
    {

        $final_data = ProductImage::where('id', $image_id)->delete();
        if ($final_data == 1) {
            $data_f['status'] = true;
            $response = APIHelpers::createApiResponse(false, 200, 'data deleted', 'تم الحذف البيانات', $data_f, $request->lang);
            return response()->json($response, 200);
        } else {
            $data_f['status'] = false;
            $response = APIHelpers::createApiResponse(true, 406, 'not deleted', 'لم يتم الحذف', $data_f, $request->lang);
            return response()->json($response, 406);
        }
    }

    public function update_ad(Request $request, $id)
    {
        $input = $request->except(['ios', 'ad_id']);
        $product = Product::where('id',$id)->first();
        if($product == null){
            $response = APIHelpers::createApiResponse(true , 406 , 'ad not exists' ,'لا يوجد اعلان بهذا ال id' , null , $request->lang);
            return response()->json($response , 406);
        }
        $validator = Validator::make($input, [
            'category_id' => 'required',
            'sub_category_id' => 'required',
            'sub_category_two_id' => '',
            'sub_category_three_id' => '',
            'sub_category_four_id' => '',
            'sub_category_five_id' => '',
            'title' => 'required',
            'city_id' => 'required',
            'area_id' => 'required',
            'share_location' => 'required',
            'latitude' => 'required',
            'longitude' => 'required',
            // 'options' => 'required',
            'price' => 'required|numeric',
            'description' => '',
            'main_image' => '',
            'images' => ''
        ]);
        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true, 406, $validator->messages()->first(), $validator->messages()->first(), null, $request->lang);
            return response()->json($response, 406);
        } else {
            $user = auth()->user();
            if ($user != null) {
                $input['user_id'] = $user->id;
            } else {
                $response = APIHelpers::createApiResponse(true, 406, 'you should login first', 'يجب تسجيل الدخول اولا', null, $request->lang);
                return response()->json($response, 406);
            }
            if ($request->main_image != null) {
                $image = $request->main_image;
                Cloudder::upload("data:image/jpeg;base64," . $image, null);
                $imagereturned = Cloudder::getResult();
                $image_id = $imagereturned['public_id'];
                $image_format = $imagereturned['format'];
                $image_new_name = $image_id . '.' . $image_format;
                $input['main_image'] = $image_new_name;
            }
            
            if ($request->images != null) {
                if ($request->ios && count($product->images) > 0) {
                    $product->images()->delete();
                }
                foreach ($request->images as $image) {
                    Cloudder::upload("data:image/jpeg;base64," . $image, null);
                    $imagereturned = Cloudder::getResult();
                    $image_id = $imagereturned['public_id'];
                    $image_format = $imagereturned['format'];
                    $image_name = $image_id . '.' . $image_format;
                    $data['product_id'] = $id;
                    $data['image'] = $image_name;
                    ProductImage::create($data);
                }
            }
            unset($input['images']);
            unset($input['options']);
            $updated = Product::where('id', $id)->update($input);
            if($request->options != null){
                Product_feature::where('product_id',$id)->delete();
                foreach ($request->options as $key => $option){
                    if(is_numeric($option['option_value'])) {
                        $option_values = Category_option_value::where('id', $option['option_value'])->first();
                        if ($option_values != null) {
                            $feature_data['type'] = 'option';
                        } else {
                            $feature_data['type'] = 'manual';
                        }
                    }else{
                        $feature_data['type'] = 'manual';
                    }
                    $feature_data['product_id'] = $id ;
                    $feature_data['target_id'] = $option['option_value'];
                    $feature_data['option_id'] =  $option['option_id'];
                    Product_feature::create($feature_data);
                }
            }
            if ($updated == 1) {
                $final_data['status'] = true;
                $response = APIHelpers::createApiResponse(false, 200, 'updated successfuly', 'تم التعديل بنجاح', $final_data, $request->lang);
                return response()->json($response, 200);
            } else {
                $data_f['status'] = false;
                $response = APIHelpers::createApiResponse(true, 406, 'not updated', 'لم يتم التعديل', $data_f, $request->lang);
                return response()->json($response, 406);
            }
        }
    }

    public function cities(Request $request)
    {
        Session::put('api_lang', $request->lang);
        if ($request->lang == 'en') {
            $cities = City::with('Areas')
                ->where('deleted', '0')
                ->select('id', 'title_en as title')
                ->get();
        } else {
            $cities = City::with('Areas')
                ->where('deleted', '0')
                ->select('id', 'title_ar as title')
                ->get();
        }
        
        $response = APIHelpers::createApiResponse(false, 200, '', '', array('cities' => $cities), $request->lang);
        return response()->json($response, 200);
    }
    public function areas(Request $request)
    {
        Session::put('api_lang', $request->lang);
        if ($request->lang == 'en') {
            $areas = Area::where('deleted', '0')
                ->select('id', 'title_en as title')
                ->get()->toArray();
        } else {
            $areas = Area::where('deleted', '0')
                ->select('id', 'title_ar as title')
                ->get()->toArray();
        }
        $title = 'All';
        if ($request->lang == 'ar') {
            $title = 'الكل';
        }
        $all = new \StdClass;
        $all->id = 0;
        $all->title = $title;
        array_unshift($areas, $all);
        $response = APIHelpers::createApiResponse(false, 200, '', '', array('areas' => $areas), $request->lang);
        return response()->json($response, 200);
    }

    // get cities for filter
    public function getCitiesForFilter(Request $request) {
        $cities = City::where('deleted', '0')
                ->select('id', 'title_' . $request->lang . ' as title')
                ->get()->toArray();
        $title = 'All';
        if ($request->lang == 'ar') {
            $title = 'الكل';
        }
        $all = new \StdClass;
        $all->id = 0;
        $all->title = $title;
        array_unshift($cities, $all);

        $response = APIHelpers::createApiResponse(false, 200, '', '', $cities, $request->lang);
        return response()->json($response, 200);
    }
}
