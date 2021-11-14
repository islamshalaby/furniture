<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// use Request;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\Plan;
use App\User;
use Illuminate\Support\Facades\Http;




class PlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api' , ['except' => [ 'select_all_plans','getpricing' , 'excute_pay' , 'pay_sucess' , 'pay_error']]);
    }

    public function getpricing(Request $request){
        $plans = Plan::select('id' , 'ads_count' , 'price')->get();
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $plans, $request->lang );
        return response()->json($response , 200);
    }

    public function buyplan(Request $request){
        $user = auth()->user();
        if($user->active == 0){
            $response = APIHelpers::createApiResponse(true , 406 ,  'تم حظر حسابك', 'تم حظر حسابك' , null, $request->lang );
            return response()->json($response , 406);
        }

        $validator = Validator::make($request->all() , [
            'plan_id' => 'required',
        ]);

        if($validator->fails()) {
            $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', 'بعض الحقول مفقودة' , null, $request->lang );
            return response()->json($response , 406);
        }

        $plan = Plan::find($request->plan_id);
            $root_url = $request->root();

            $path='https://api.myfatoorah.com/v2/SendPayment';
            $token="bearer GSHFK6o5YrsLRLJSPPHKDJp9XjnA6Tvr5pMhryXO8Cdwa5Lk8hqRWqIvlC8AoSD2CwFlGBJEt7j2e-MrKv_0iHb8iJO-P-6_s4KvKjgE5HfBDGYQKXiUZ4H8yfrYp9f5vSuOGVDHyeiRaTW4HWxbE9OhNS8_fPJ711xV1aIKvey96tn6ZCsXekbX6H9XvYYrG-iPuPejNaoCb9gxNrqDUzcjF_aVYmiwsVdApTGWZOwKP_ns6hQC0Th9Fjn39KuMHxzsSUNe_s1ss73YFBkxoxWxz6A9cT5ZmdDdFlFeYMpCh-nVv1cHZkUByQZBYOgwpS5nAc1bO9B-UoQduWvVxgnEZzL-2afMzKuQwRrqhasHBzrKR-KVLKvRmWD8uhdoKM1GAxRJwitN8UDosT6WB9W_MkxKBXaaV_mSK5Qvmk6-2IEk4NTt1WFTNQX82tjN4hG-hTpZLkrofxztcxeAu6sKXJHITk_7bBDu7pqXRX3ru3ChczL2dBVMI1zXakItqGyaeB60TUvEwPMzO-6u235UbeUIJJLoeIyuWmyQS39xkR2bNGaYu4yQia4CkxpLDvBiLDQ1XkIYKsCYsH-NkXfGqGhCoAjPsF8j9QRE87bTCflhii5MVL8ouOtKc5ZAjaTu_yQC9yfrq7iMLDTBkZrzdls4CHtxEKDgTVSkVEGO8yxSW7--Gq-SLF1hnw8xzhvtY9SrsyRkDLpHdyvDedULpuRkPumWpsPAai6YbJXQMyGa";

            $headers = array(
                'Authorization:' .$token,
                'Content-Type:application/json'
            );
            $price = $plan->price;
            $call_back_url = $root_url."/api/excute_pay?user_id=".$user->id."&plan_id=".$request->plan_id;
            $error_url = $root_url."/api/pay/error";
            $fields =array(
                "CustomerName" => $user->name,
                "NotificationOption" => "LNK",
                "InvoiceValue" => $price,
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
            $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $result->Data->InvoiceURL, $request->lang );
            return response()->json($response , 200);

    }

    public function excute_pay(Request $request){
        $plan = Plan::find($request->plan_id);
        $new_ads_count = $plan->ads_count;
        $user = User::find($request->user_id);
        $paid_ads = $user->paid_ads_count;
        $user->paid_ads_count = $paid_ads + $new_ads_count;
        $user->save();
        return redirect('api/pay/success');
    }

    public function pay_sucess(){
        return "Please wait ...";
    }

    public function pay_error(){
        return "Please wait ...";
    }

    //Nasser Code
    public function select_all_plans(Request $request,$cat_id) {
        $lang = $request->lang;
        $plan = Plan::where('cat_id' , $cat_id)->first();
            $data['plans'] = Plan::with('Details')
                ->where('deleted','0')
                ->where('status' , 'show')
                ->where('cat_id' , $cat_id)
                ->orwhere('cat_id' , 'all')
                ->select('id' ,'title_ar as title' , 'title_en' ,'cat_id','price')
                ->get()
                ->map(function($plans) use ($lang) {
                    if($lang == 'en'){
                        foreach($plans->Details as $plan_detail){
                            $plan_detail->title = $plan_detail->title_en;
                        }
                        $plans->title = $plans->title_en;
                    }
                    return $plans;
                });
        $response = APIHelpers::createApiResponse(false , 200 , '' , '' , $data , $request->lang);
        return response()->json($response , 200);
    }

}
