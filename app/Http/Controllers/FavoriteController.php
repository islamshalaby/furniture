<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\Favorite;
use App\User;
use App\Product;
use App\ProductImage;


class FavoriteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => []]);
    }

    public function addtofavorites(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', 'تم حظر حسابك' , null, $request->lang );
            return response()->json($response , 406);
        }
        $validator = Validator::make($request->all() , [
            'product_id' => 'required',
        ]);
        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', 'بعض الحقول مفقودة' , null, $request->lang );
            return response()->json($response , 406);
        }
        $favorite = Favorite::where('product_id' , $request->product_id)->where('user_id' , $user->id)->first();
        if($favorite){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم إضافه هذا المنتج للمفضله من قبل', 'تم إضافه هذا المنتج للمفضله من قبل' , null, $request->lang );
            return response()->json($response , 406);
        }else{
            $favorite = new Favorite();
            $favorite->user_id = $user->id;
            $favorite->product_id = $request->product_id;
            $favorite->save();
            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $favorite, $request->lang);
            return response()->json($response , 200);
        }
    }

    public function removefromfavorites(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', 'تم حظر حسابك' , null, $request->lang );
            return response()->json($response , 406);
        }

        $validator = Validator::make($request->all() , [
            'product_id' => 'required',
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', 'بعض الحقول مفقودة' , null, $request->lang );
            return response()->json($response , 406);
        }

        $favorite = Favorite::where('product_id' , $request->product_id)->where('user_id', $user->id)->first();
        if($favorite){
            $favorite->delete();
            $response = APIHelpers::createApiResponse(false , 200 ,  'Deteted ', 'تم الحذف' , null, $request->lang);
            return response()->json($response , 200);
        }else{
            $response = APIHelpers::createApiResponse(true , 406 ,  'هذا المنتج غير موجود بالمفضله', 'هذا المنتج غير موجود بالمفضله' , null, $request->lang );
            return response()->json($response , 406);
        }
    }

    public function getfavorites(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', 'تم حظر حسابك' , null, $request->lang );
            return response()->json($response , 406);
        }else {
            $ads = Favorite::where('user_id', $user->id)->orderBy('id','desc')
                            ->pluck('product_id')->toArray();
            $ids_ordered = '';
            if (count($ads) > 0) {
                $ids_ordered = implode(',', $ads);
                $products = Product::whereIn('id', $ads)->where('deleted', 0)->where('publish', 'Y')->select('id', 'title', 'price', 'main_image as image', 'pin', 'views', 'city_id', 'created_at')->orderByRaw("FIELD(id, $ids_ordered)")->with('City_api')->simplePaginate(12);
            }else {
                $products = Product::whereIn('id', $ads)->where('deleted', 0)->where('publish', 'Y')->select('id', 'title', 'price', 'main_image as image', 'pin', 'views', 'city_id', 'created_at')->with('City_api')->simplePaginate(12);
            }
			
			
            
            for ($i = 0; $i < count($products); $i ++) {
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

            $response = APIHelpers::createApiResponse(false, 200, '', '', $products, $request->lang);
            return response()->json($response, 200);
        }
    }
}
