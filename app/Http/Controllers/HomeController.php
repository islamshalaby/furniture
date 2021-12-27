<?php

namespace App\Http\Controllers;

use App\Balance_package;
use App\Main_ad;
use App\Plan_details;
use App\Setting;
use App\User;
use Illuminate\Http\Request;
use App\Helpers\APIHelpers;
use App\Category;
use App\Ad;
use App\Company;
use App\Product;
use App\ProductImage;
use App\Favorite;
use App\HomeSection;
use App\HomeElement;
use Carbon\Carbon;
use App\Slider;


class HomeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['balance_packages', 'gethome', 'getHomeAds', 'check_ad', 'main_ad', 'getSlider', 'getHomeCompanies', 'getHomeCategories', 'getOffersBanners']]);
    }

    public function gethome(Request $request)
    {
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

        $data['slider'] = Ad::select('id', 'image', 'type', 'content')->where('place', 1)->get();
        $data['ads'] = Ad::select('id', 'image', 'type', 'content')->where('place', 2)->get();
        $data['categories'] = Category::select('id', 'image', 'title_ar as title')->where('deleted', 0)->get();
        $data['offers'] = Product::where('offer', 1)->where('status', 1)->where('deleted', 0)->where('publish', 'Y')->select('id', 'title', 'price', 'type')->get();
        for ($i = 0; $i < count($data['offers']); $i++) {
            $data['offers'][$i]['image'] = ProductImage::where('product_id', $data['offers'][$i]['id'])->select('image')->first()['image'];
            $user = auth()->user();
            if ($user) {
                $favorite = Favorite::where('user_id', $user->id)->where('product_id', $data['offers'][$i]['id'])->first();
                if ($favorite) {
                    $data['offers'][$i]['favorite'] = true;
                } else {
                    $data['offers'][$i]['favorite'] = false;
                }
            } else {
                $data['offers'][$i]['favorite'] = false;
            }
            // $data['offers'][$i]['favorite'] = false;

        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function getSlider(Request $request) {
        $data = Slider::select('id', 'image', 'type', 'content', 'content_type')->orderBy('id', 'desc')->get();

        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function getOffersBanners(Request $request) {
        $data = Ad::select('id' ,'image' , 'type' , 'content')->inRandomOrder()->get();

        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function getHomeAds(Request $request)
    {
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
        $home_data = HomeSection::where('section_type', 1)->orderBy('sort' , 'Asc')->get();
        $data = [];
        for($i = 0; $i < count($home_data); $i++){
            $element = [];
            $element['id'] = $home_data[$i]['id'];
            $element['type'] = $home_data[$i]['type'];
            if($request->lang == 'en'){
                $element['title'] = $home_data[$i]['title_en'];
            }else{
                $element['title'] = $home_data[$i]['title_ar'];
            }
            $ids = HomeElement::where('home_id' , $home_data[$i]['id'])->pluck('element_id');
            
            if($home_data[$i]['type'] == 1){
                
                $element['data'] = Ad::select('id' ,'image' , 'type' , 'content')->whereIn('id' , $ids)->get();

                array_push($data , $element);

            }elseif($home_data[$i]['type'] == 2){
                
                if($request->lang == 'en'){
                    $element['data'] = Category::select('id' ,'image' , 'title_en as title')->where('deleted' , 0)->whereIn('id' , $ids)->limit(5)->get()->toArray();
                    
                }else{
                    $element['data'] = Category::select('id' ,'image' , 'title_ar as title')->where('deleted' , 0)->whereIn('id' , $ids)->has('products', '>', 0)->limit(5)->get()->toArray();
                }
                
                array_push($data , $element);

            }elseif($home_data[$i]['type'] == 3){

                if($request->lang == 'en'){
                    $element['data'] = Company::select('id' ,'logo' , 'name_en as title')->where('deleted' , 0)->whereIn('id' , $ids)->limit(5)->get();
                }else{
                    $element['data'] = Company::select('id' ,'logo' , 'name_ar as title')->where('deleted' , 0)->whereIn('id' , $ids)->limit(5)->get(); 
                }                

                array_push($data , $element);

            }elseif($home_data[$i]['type'] == 5){

                $element['data'] = Ad::select('id' ,'image' , 'type' , 'content')->whereIn('id' , $ids)->get();

                array_push($data , $element);

            }
        }
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

//nasser code
    // main ad page
    public function main_ad(Request $request)
    {
        $data = Main_ad::select('image')->where('deleted', '0')->inRandomOrder()->take(1)->get();
        if (count($data) == 0) {
            $response = APIHelpers::createApiResponse(true, 406, 'no ads available',
                'لا يوجد اعلانات', null, $request->lang);
            return response()->json($response, 406);
        }
        foreach ($data as $image) {
            $image['image'] = $image->image;
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $image, $request->lang);
        return response()->json($response, 200);
    }

    public function check_ad(Request $request)
    {
        $ads = Main_ad::select('image')->where('deleted', '0')->get();
        if (count($ads) > 0) {
            $data['show_ad'] = true;
        } else {
            $data['show_ad'] = false;
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    public function balance_packages(Request $request)
    {
        if ($request->lang == 'en') {
            $data['packages'] = Balance_package::where('status', 'show')->select('id', 'name_en as title', 'price', 'amount', 'desc_en as desc')->orderBy('title', 'desc')->get();
        } else {
            $data['packages'] = Balance_package::where('status', 'show')->select('id', 'name_ar as title', 'price', 'amount', 'desc_ar as desc')->orderBy('title', 'desc')->get();
        }
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // get home companies
    public function getHomeCompanies(Request $request) {
        $data = Company::where('deleted', 0)->select('id', 'logo', 'name_' . $request->lang . ' as name', 'type', 'link')->orderBy('id', 'desc')->get()
        ->map(function ($row) use ($request) {
            if ($row->type == 2) {
                $row->link = $request->root() . '/api/company_details/' . $row->id . '/' . $request->lang . '/v1';
            }
            return $row;
        });

        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // get companies
    public function getCompanies(Request $request) {
        $data = Company::select('id', 'logo', 'name_' . $request->lang . ' as name', 'type', 'link')->orderBy('id', 'desc')->paginate(20)
        ;
        foreach($data as $row) {
            if ($row->type == 2) {
                $row->link = $request->root() . '/api/company_details/' . $row->id . '/' . $request->lang . '/v1';
            }
        }

        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }

    // get home categories
    public function getHomeCategories(Request $request) {
        $categories = Category::where('deleted', 0)->where('is_show', 1)->select('id', 'image', 'title_' . $request->lang . ' as title')->get();
        $data = [];
        if (count($categories) > 0) {
            for($i = 0; $i < count($categories); $i ++) {
                $categories[$i]['type'] = "categories";
                $categories[$i]['sub_categories'] = $categories[$i]->subCategories($request->lang)->get()->makeHidden('subCategoriesTwo');
                $categories[$i]['next_level'] = false;
                if (count($categories[$i]['sub_categories']) > 0) {
                    for ($n = 0; $n < count($categories[$i]['sub_categories']); $n ++) {
                        $categories[$i]['sub_categories'][$n]['type'] = 1;
                        if (count($categories[$i]['sub_categories'][$n]->subCategoriesTwo) > 0) {
                            $categories[$i]['sub_categories'][$n]['type'] = 2;
                        }
                    }
                    $categories[$i]['next_level'] = true;
                }
                array_push($data, $categories[$i]);
                if ($i == 0) {
                    $pushed = (object)[
                        "id" => $i + 1,
                        "type" => "offers",
                        "title" => "Our Offers"
                    ];
                    if ($request->lang == 'ar') {
                        $pushed = (object)[
                            "id" => $i + 1,
                            "type" => "offers",
                            "title" => "عروضنا"
                        ];
                    }
                    
                    $pushed->offers = Ad::select('id' ,'image' , 'type' , 'content')->inRandomOrder()->get();
                    array_push($data, $pushed);
                }
            }
        }
        
        $response = APIHelpers::createApiResponse(false, 200, '', '', $data, $request->lang);
        return response()->json($response, 200);
    }
}
