<?php
namespace App\Http\Controllers;

use App\ContactImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Helpers\APIHelpers;
use App\Http\Requests\SendContactMessage;
use App\ContactUs;
use JD\Cloudder\Facades\Cloudder;


class ContactUsController extends Controller
{
    public function SendMessage(Request $request){
            $validator = Validator::make($request->all(), [
                'phone' => 'required',
                'message' => 'required'
            ]);

            if ($validator->fails()) {
                $response = APIHelpers::createApiResponse(true , 406 ,  'بعض الحقول مفقودة', 'بعض الحقول مفقودة' , null, $request->lang );
                return response()->json($response , 406);
            }
        $user = auth()->user();
        $contact = new ContactUs;
        $contact->phone = $request->phone;
        $contact->message = $request->message;
        $contact->user_id = $user->id;
        $contact->save();
        if ($request->images && count($request->images) > 0) {
            for ($i = 0; $i < count($request->images); $i ++) {
                if (strpos($request->images[$i], 'data:image') !== false) {
                    $image = $request->images[$i];
                }else {
                    $image = "data:image/png;base64," . $request->images[$i];  // your base64 encoded
                }
                
                // $image = str_replace('data:image/png;base64,', '', $image);
                Cloudder::upload($image, null);
                $front_imageereturned = Cloudder::getResult();
                $front_image_id = $front_imageereturned['public_id'];
                $front_image_format = $front_imageereturned['format'];    
                $front_image_new_name = $front_image_id.'.'.$front_image_format;
                $post['image'] = $front_image_new_name;
                $post['contact_id'] = $contact->id;
                ContactImage::create($post);
            }
        }
        $response = APIHelpers::createApiResponse(false , 200 ,  '', '' , $contact, $request->lang );
        return response()->json($response , 200);
    }
}
