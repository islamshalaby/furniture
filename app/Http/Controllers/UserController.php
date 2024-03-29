<?php

namespace App\Http\Controllers;

use App\Balance_package;
use App\WalletTransaction;
use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\UserNotification;
use App\Notification;
use App\Product;
use App\ProductImage;
use App\Setting;
use App\Favorite;
use App\Category;
use App\Company;
use App\Visitor;
use JD\Cloudder\Facades\Cloudder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;




class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => ['pay_sucess','pay_error','excute_pay','my_account','my_balance','resetforgettenpassword' , 'checkphoneexistance' , 'getownerprofile', 'companyDetails']]);
    }

    public function getprofile(Request $request){
        $user = auth()->user();
        $returned_user['user_name'] = $user['name'];
		$returned_user['name'] = $user['name'];
        $returned_user['phone'] = $user['phone'];
        $returned_user['email'] = $user['email'];
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $returned_user, $request->lang );
        return response()->json($response , 200);
    }

    public function updateprofile(Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'phone' => 'required',
            "email" => 'required',
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $currentuser = auth()->user();
        $user_by_phone = User::where('phone' , '!=' , $currentuser->phone )->where('phone', $request->phone)->first();
        if($user_by_phone){
            $response = APIHelpers::createApiResponse(true , 409 ,  'رقم الهاتف موجود من قبل', '' , null, $request->lang );
            return response()->json($response , 409);
        }

        $user_by_email = User::where('email' , '!=' ,$currentuser->email)->where('email' , $request->email)->first();
        if($user_by_email){
            $response = APIHelpers::createApiResponse(true , 409 , 'البريد الإلكتروني موجود من قبل', '' , null, $request->lang );
            return response()->json($response , 409);
        }
// dd($currentuser->id);
        $userImage = $currentuser->image;
        if ($request->image) {
            $image = str_replace('data:image/png;base64,', '', $request->image);
            $image = str_replace(' ', '+', $image);
            $profileImage = $request->image;
            Cloudder::upload($profileImage, null);
            $front_imageereturned = Cloudder::getResult();
            $front_image_id = $front_imageereturned['public_id'];
            $front_image_format = $front_imageereturned['format'];    
            $front_image_new_name = $front_image_id.'.'.$front_image_format;
            $userImage = $front_image_new_name;
        }

        User::where('id' , $currentuser->id)->update([
            'name' => $request->name ,
            'phone' => $request->phone ,
            'email' => $request->email,
            'image' => $userImage  
            ]);

        $newuser = User::find($currentuser->id);
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $newuser, $request->lang );
        return response()->json($response , 200);
    }


    public function resetpassword(Request $request){
        $validator = Validator::make($request->all() , [
            'password' => 'required',
			"old_password" => 'required'
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $user = auth()->user();
		if(!Hash::check($request->old_password, $user->password)){
			$response = APIHelpers::createApiResponse(true , 406 ,  'كلمه المرور السابقه خطأ', '' , null, $request->lang );
            return response()->json($response , 406);
		}
		if($request->old_password == $request->password){
			$response = APIHelpers::createApiResponse(true , 406 ,  'لا يمكنك تعيين نفس كلمه المرور السابقه', '' , null, $request->lang );
            return response()->json($response , 406);
		}
        User::where('id' , $user->id)->update(['password' => Hash::make($request->password)]);
        $newuser = User::find($user->id);
        $response = APIHelpers::createApiResponse(false , 200 , '', '' , $newuser, $request->lang );
        return response()->json($response , 200);
    }

    public function resetforgettenpassword(Request $request){
        $validator = Validator::make($request->all() , [
            'password' => 'required',
            'phone' => 'required'
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $user = User::where('phone', $request->phone)->first();
        if(! $user){
            $response = APIHelpers::createApiResponse(true , 403 ,  'رقم الهاتف غير موجود', '' , null, $request->lang );
            return response()->json($response , 403);
        }

        User::where('phone' , $user->phone)->update(['password' => Hash::make($request->password)]);
        $newuser = User::where('phone' , $user->phone)->first();

		$token = auth()->login($newuser);
        $newuser->token = $this->respondWithToken($token);

        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $newuser, $request->lang );
        return response()->json($response , 200);
    }

    // check if phone exists before or not
    public function checkphoneexistance(Request $request)
    {
        $validator = Validator::make($request->all() , [
            'phone' => 'required'
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'حقل الهاتف اجباري', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $user = User::where('phone' , $request->phone)->first();
        if($user){
            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $user, $request->lang );
            return response()->json($response , 200);
        }

        $response = APIHelpers::createApiResponse(true , 403 ,  'الهاتف غير موجود من قبل', '' , null, $request->lang );
        return response()->json($response , 403);

    }


    // get notifications
    public function notifications(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك من الادمن', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $user_id = $user->id;
        $visitor = Visitor::where('unique_id', $request->unique_id)->select('id')->first();
        $notifications_ids = UserNotification::where('user_id' , $user_id)->where('visitor_id', $visitor->id)->orderBy('id' , 'desc')->select('notification_id')->get();
        $notifications = [];
        for($i = 0; $i < count($notifications_ids); $i++){
            $noti = Notification::select('id','title' , 'body' ,'image' , 'created_at')->find($notifications_ids[$i]['notification_id']);
            if ($noti) {
                $notifications[$i] = $noti;
            }
            
        }
        $data['notifications'] = $notifications;
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' ,$data['notifications'], $request->lang );
        return response()->json($response , 200);
    }

    // get ads count
    public function getadscount(Request $request){
        $user = auth()->user();
        $returned_user['free_ads_count'] = $user->free_ads_count;
        $returned_user['paid_ads_count'] = $user->paid_ads_count;
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $returned_user, $request->lang );
        return response()->json($response , 200);
    }

	    protected function respondWithToken($token)
    {
        return [
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 432000
        ];
    }

    // get current ads
    public function getcurrentads(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك من الادمن', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $user = auth()->user();

        $products = Product::where('user_id' , $user->id)->where('status' , 1)->orderBy('publication_date' , 'DESC')->select('id' , 'title' , 'price' , 'publication_date as date' , 'type')->simplePaginate(12);
        for($i =0 ; $i < count($products); $i++){
            $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->select('image')->first()['image'];
            $favorite = Favorite::where('user_id' , $user->id)->where('product_id' , $products[$i]['id'])->first();
            if($favorite){
                $products[$i]['favorite'] = true;
            }else{
                $products[$i]['favorite'] = false;
            }
            $date = date_create($products[$i]['date']);
            $products[$i]['date'] = date_format($date , 'd M Y');
        }
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $products, $request->lang );
        return response()->json($response , 200);
    }

    // get history date
    public function getexpiredads(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك من الادمن', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $user = auth()->user();

        $products = Product::where('user_id' , $user->id)->where('status' , 2)->orderBy('publication_date' , 'DESC')->select('id' , 'title' , 'price' , 'publication_date as date' , 'type')->simplePaginate(12);
        for($i =0 ; $i < count($products); $i++){
            $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->select('image')->first()['image'];
            $favorite = Favorite::where('user_id' , $user->id)->where('product_id' , $products[$i]['id'])->first();
            if($favorite){
                $products[$i]['favorite'] = true;
            }else{
                $products[$i]['favorite'] = false;
            }
            $date = date_create($products[$i]['date']);
            $products[$i]['date'] = date_format($date , 'd M Y');
        }
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $products, $request->lang );
        return response()->json($response , 200);
    }

    public function renewad(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', '' , null, $request->lang );
            return response()->json($response , 406);
        }
        if($user->free_ads_count == 0 && $user->paid_ads_count == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'ليس لديك رصيد إعلانات لتجديد الإعلان يرجي شراء باقه إعلانات', '' , null, $request->lang );
            return response()->json($response , 406);
        }
        $product = Product::where('id' , $request->product_id)->where('user_id' , $user->id)->first();
        if($product->status == 1){
            $response = APIHelpers::createApiResponse(true , 406 ,  'هذا الاعلان لم ينتهى بعد', 'this ad not ended yet' , null, $request->lang );
            return response()->json($response , 406);
        }
        if($product->deleted == 1){
            $response = APIHelpers::createApiResponse(true , 406 ,  'هذا الاعلان تم حذفة', 'this ad deleted before' , null, $request->lang );
            return response()->json($response , 406);
        }
        if($product){
            if($user->free_ads_count > 0){
                $count = $user->free_ads_count;
                $user->free_ads_count = $count - 1;
            }else{
                $count = $user->paid_ads_count;
                $user->paid_ads_count = $count - 1;
            }
            $user->save();
            $settings = $settings = Setting::where('id',1)->first();
            $product->publication_date = date("Y-m-d H:i:s");
            $mytime = Carbon::now();
            $today =  Carbon::parse($mytime->toDateTimeString())->format('Y-m-d H:i');
            $final_date = Carbon::createFromFormat('Y-m-d H:i', $today);
            $final_expire_date = $final_date->addDays($settings->ad_period);
            $product->expiry_date = $final_expire_date ;
            $product->status = 1;
            $product->publish = 'Y';
            $product->save();
            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $product, $request->lang );
            return response()->json($response , 200);

        }else{
            $response = APIHelpers::createApiResponse(true , 406 ,  'ليس لديك الصلاحيه لتجديد هذا الاعلان', '' , null, $request->lang );
            return response()->json($response , 406);

        }

    }

    public function deletead(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $validator = Validator::make($request->all() , [
            'product_id' => 'required',
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $product = Product::where('id' , $request->product_id)->where('user_id' , $user->id)->first();

        if($product){
            $product->delete();
            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , null, $request->lang );
            return response()->json($response , 200);
        }else{
            $response = APIHelpers::createApiResponse(true , 406 ,  'ليس لديك الصلاحيه لحذف هذا الاعلان', '' , null, $request->lang );
            return response()->json($response , 406);
        }

    }

    public function editad(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $validator = Validator::make($request->all() , [
            'product_id' => 'required',
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $product = Product::where('id' , $request->product_id)->where('user_id' , $user->id)->first();
        if($product){
            if($request->title){
                $product->title = $request->title;
            }

            if($request->description){
                $product->description = $request->description;
            }

            if($request->price){
                $product->price = $request->price;
            }

            if($request->category_id){
                $product->category_id = $request->category_id;
            }

            if($request->type){
                $product->type = $request->type;
            }

            $product->save();

            if($request->image){
                $product_image = ProductImage::where('product_id' , $request->product_id)->first();
                $image = $request->image;
                Cloudder::upload("data:image/jpeg;base64,".$image, null);
                $imagereturned = Cloudder::getResult();
                $image_id = $imagereturned['public_id'];
                $image_format = $imagereturned['format'];
                $image_new_name = $image_id.'.'.$image_format;
                $product_image->image = $image_new_name;
                $product_image->save();
            }

            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $product, $request->lang );
            return response()->json($response , 200);
        }else{
            $response = APIHelpers::createApiResponse(true , 406 ,  'ليس لديك الصلاحيه لتعديل هذا الاعلان', '' , null, $request->lang );
            return response()->json($response , 406);
        }

    }

    public function delteadimage(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $validator = Validator::make($request->all() , [
            'image_id' => 'required',
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', '' , null, $request->lang );
            return response()->json($response , 406);
        }

        $image = ProductImage::find($request->image_id);
        if($image){
            $image->delete();
            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , null, $request->lang);
            return response()->json($response , 200);

        }else{
            $response = APIHelpers::createApiResponse(true , 406 ,  'Invalid Image Id', '' , null, $request->lang );
            return response()->json($response , 406);
        }

    }

    public function getaddetails(Request $request){
        $ad_id = $request->id;
        $ad = Product::select('id' , 'title' , 'description' , 'price' , 'type' , 'category_id')->find($ad_id);
        $ad['category_name'] = Category::find($ad['category_id'])['title_ar'];
		$images = ProductImage::where('product_id' , $ad_id)->select('id' , 'image')->get()->toArray();

        $ad['image'] =  array_shift($images)['image'];
        $ad['images'] = $images;
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' ,$ad, $request->lang );
        return response()->json($response , 200);
    }

    public function getownerprofile(Request $request){
        $user_id = $request->id;
        $data['user'] = User::select('id' , 'name' , 'phone' , 'email')->find($user_id);
        $products = Product::where('status' , 1)->where('user_id' , $user_id)->orderBy('publication_date' , 'DESC')->select('id' , 'title' , 'price','type' , 'publication_date as date')->get();
        for($i =0; $i < count($products); $i++){
            $products[$i]['image'] = ProductImage::where('product_id' , $products[$i]['id'])->first()['image'];
            $date = date_create($products[$i]['date']);
            $products[$i]['date'] = date_format($date , 'd M Y');

            $user = auth()->user();
            if($user){
                $favorite = Favorite::where('user_id' , $user->id)->where('product_id' , $products[$i]['id'])->first();
                if($favorite){
                    $products[$i]['favorite'] = true;
                }else{
                    $products[$i]['favorite'] = false;
                }
            }else{
                $products[$i]['favorite'] = false;
            }

        }
        $data['products'] = $products;

        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' ,$data, $request->lang );
        return response()->json($response , 200);
    }
//nasser code
    public function my_account(Request $request){
        $user = auth()->user();
        $user_data = User::where('id',$user->id)->select('name','email','image','phone','free_balance','payed_balance', 'created_at')->first();
        if($user_data->image == null){
            $settings = Setting::where('id',1)->first();

            $user_data['image'] = $settings->logo;
        }
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $user_data , $request->lang);
        return response()->json($response , 200);
    }
    public function payments_date(Request $request){
        $user = auth()->user();
        $lang = $request->lang;

        $data = WalletTransaction::where('user_id',$user->id)
                                ->where('type','payed')
                                ->select('price','type','user_id','package_id','created_at')
                                ->orderBy('created_at','desc')
                                ->get()
                                ->map(function($wallet) use ($lang){
                                    $package = Balance_package::where('id',$wallet->package_id)->first();
                                    if($lang == 'ar'){
                                        $wallet->pakage_name =  $package->name_ar;
                                    }else{
                                        $wallet->pakage_name =  $package->name_en;
                                    }
                                    $wallet->day = $wallet->created_at->format('d');
                                    $wallet->month = $wallet->created_at->format('F');
                                    if($lang == 'ar'){
                                        if($wallet->month == 'January'){
                                            $wallet->month = 'يناير';
                                        }else if($wallet->month == 'February'){
                                            $wallet->month = 'فبراير';
                                        }else if($wallet->month == 'March'){
                                            $wallet->month = 'مارس';
                                        }else if($wallet->month == 'April'){
                                            $wallet->month = 'ابريل';
                                        }else if($wallet->month == 'May'){
                                            $wallet->month = 'مايو';
                                        }else if($wallet->month == 'June'){
                                            $wallet->month = 'يونيو';
                                        }else if($wallet->month == 'July'){
                                            $wallet->month = 'يوليو';
                                        }else if($wallet->month == 'August'){
                                            $wallet->month = 'أغسطي';
                                        }else if($wallet->month == 'September'){
                                            $wallet->month = 'سبتمبر';
                                        }else if($wallet->month == 'October'){
                                            $wallet->month = 'أكتوبر';
                                        }else if($wallet->month == 'November'){
                                            $wallet->month = 'نوفمبر';
                                        }else if($wallet->month == 'December'){
                                            $wallet->month = 'ديسمبر';
                                        }
                                    }
                                    $wallet->date = $wallet->created_at->format('d/m/Y');

                                    return $wallet;
                                });

        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

    public function my_balance(Request $request){
        $data = User::where('id',auth()->user()->id)->select('id' , 'free_balance','payed_balance')->first();
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }
// add balance to wallet
    public function addBalance(Request $request) {

        $validator = Validator::make($request->all(), [
            'package_id' => 'required|exists:balance_packages,id'
        ]);
        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , $validator->messages()->first() , $validator->messages()->first() , null , $request->lang);
            return response()->json($response , 406);
        }
        $package = Balance_package::find($request->package_id);
        $user = auth()->user();
        $root_url = $request->root();
        $path='https://api.myfatoorah.com/v2/SendPayment';
        $token="bearer GSHFK6o5YrsLRLJSPPHKDJp9XjnA6Tvr5pMhryXO8Cdwa5Lk8hqRWqIvlC8AoSD2CwFlGBJEt7j2e-MrKv_0iHb8iJO-P-6_s4KvKjgE5HfBDGYQKXiUZ4H8yfrYp9f5vSuOGVDHyeiRaTW4HWxbE9OhNS8_fPJ711xV1aIKvey96tn6ZCsXekbX6H9XvYYrG-iPuPejNaoCb9gxNrqDUzcjF_aVYmiwsVdApTGWZOwKP_ns6hQC0Th9Fjn39KuMHxzsSUNe_s1ss73YFBkxoxWxz6A9cT5ZmdDdFlFeYMpCh-nVv1cHZkUByQZBYOgwpS5nAc1bO9B-UoQduWvVxgnEZzL-2afMzKuQwRrqhasHBzrKR-KVLKvRmWD8uhdoKM1GAxRJwitN8UDosT6WB9W_MkxKBXaaV_mSK5Qvmk6-2IEk4NTt1WFTNQX82tjN4hG-hTpZLkrofxztcxeAu6sKXJHITk_7bBDu7pqXRX3ru3ChczL2dBVMI1zXakItqGyaeB60TUvEwPMzO-6u235UbeUIJJLoeIyuWmyQS39xkR2bNGaYu4yQia4CkxpLDvBiLDQ1XkIYKsCYsH-NkXfGqGhCoAjPsF8j9QRE87bTCflhii5MVL8ouOtKc5ZAjaTu_yQC9yfrq7iMLDTBkZrzdls4CHtxEKDgTVSkVEGO8yxSW7--Gq-SLF1hnw8xzhvtY9SrsyRkDLpHdyvDedULpuRkPumWpsPAai6YbJXQMyGa";
        $headers = array(
            'Authorization:' .$token,
            'Content-Type:application/json'
        );
        $call_back_url = $root_url."/api/wallet/excute_pay?user_id=".$user->id."&balance=".$request->package_id;
        $error_url = $root_url."/api/pay/error";

        $fields =array(
            "CustomerName" => $user->name,
            "NotificationOption" => "LNK",
            "InvoiceValue" => $package->price,
            "CallBackUrl" => $call_back_url,
            "ErrorUrl" => $error_url,
            "Language" => "AR",
            "CustomerEmail" => $user->email
        );

        $payload =json_encode($fields);
        $curl_session =curl_init();
        curl_setopt($curl_session,CURLOPT_URL, $path);
        curl_setopt($curl_session,CURLOPT_POST, true);
        curl_setopt($curl_session,CURLOPT_HTTPHEADER, $headers);
        curl_setopt($curl_session,CURLOPT_RETURNTRANSFER,true);
        curl_setopt($curl_session,CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl_session,CURLOPT_IPRESOLVE, CURLOPT_IPRESOLVE);
        curl_setopt($curl_session,CURLOPT_POSTFIELDS, $payload);
        $result=curl_exec($curl_session);
        curl_close($curl_session);
        $result = json_decode($result);

        $data['url'] = $result->Data->InvoiceURL;
        $response = APIHelpers::createApiResponse(false , 200 ,  '' , '' , $data , $request->lang );
        return response()->json($response , 200);
    }

    // excute pay
    public function excute_pay(Request $request) {
        $package = Balance_package::findOrFail($request->balance);
        if ($package != null) {
            $user = auth()->user();
            $selected_user = User::findOrFail($request->user_id);
            $selected_user->my_wallet = $selected_user->my_wallet + $package->amount;
            $selected_user->payed_balance = $selected_user->payed_balance + $package->amount;
            $selected_user->save();
            WalletTransaction::create([
                'price' => $package->price,
                'value' => $package->amount,
                'package_id' => $request->balance,
                'user_id' => $request->user_id
            ]);
            return redirect('api/pay/success');
        }
    }

    public function pay_error(){
        return "Please wait error ...";
    }
    public function pay_sucess(){
        return "Please wait success ...";
    }

    // company details
    public function companyDetails(Request $request) {
        $data = Company::where('id', $request->id)->select('id', 'logo', 'name_' . $request->lang . ' as name', 'user_id', 'email as company_email')->with('user')->first()->makeHidden(['user_id']);
        $data['user']['city'] = "";
        if ($request->lang == 'en') {
            if ($data->user->city) {
                $data['user']['city'] = $data->user->city->title_en;
            }
        }else {
            if ($data->user->city) {
                $data['user']['city'] = $data->user->city->title_ar;
            }
        }
        
        $data['products'] = Product::where('user_id', $data->user->id)->where('publish', 'Y')
        ->where('deleted', 0)
        ->where('status', 1)->select('id', 'title', 'price', 'main_image as image', 'pin', 'views', 'city_id', 'created_at')->orderBy('created_at', 'DESC')->with('City_api')->simplePaginate(12);
        
        
        if (count($data['products']) > 0) {
            for ($i = 0; $i < count($data['products']); $i ++) {
                $data['products'][$i]['time'] = APIHelpers::get_time_day($data['products'][$i]['created_at'], $request->lang);
                $favorite = Favorite::where('user_id', $data->user->id)->where('product_id', $data['products'][$i]['id'])->first();
                $data['products'][$i]['favorite'] = false;
                if ($favorite) {
                    $data['products'][$i]['favorite'] = true;
                }
            }
        }
        
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }


    // upload profile image
    public function uploadProfileIamge(Request $request) {
        $validator = Validator::make($request->all(), [
            'image' => 'required'
        ]);

        if ($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 , 'Missing Required Fields' , 'بعض الحقول مفقودة' , null , $request->lang);
            return response()->json($response , 406);
        }
        $user = User::where('id', auth()->user()->id)->select('id', 'image')->first();

        $image = $request->image;
        $oldImage = $user->image;
        if (!empty($oldImage)) {
            $publicId = substr($image, 0 ,strrpos($oldImage, "."));  
            Cloudder::delete($publicId);
        }
        Cloudder::upload("data:image/jpeg;base64,".$image, null);
        $imagereturned = Cloudder::getResult();
        $image_id = $imagereturned['public_id'];
        $image_format = $imagereturned['format'];    
        $image_new_name = $image_id.'.'.$image_format;

        $user->image = $image_new_name;

        $user->save();

        $response = APIHelpers::createApiResponse(false , 200 ,  '' , '' ,(object)[] , $request->lang );
        return response()->json($response , 200);
    }

}
