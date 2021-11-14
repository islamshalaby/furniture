<?php
namespace App\Http\Controllers\Admin\Ads;
use App\Http\Controllers\Admin\AdminController;
use Illuminate\Http\Request;
use JD\Cloudder\Facades\Cloudder;
use Illuminate\Support\Facades\DB;
use App\Ad;
use App\User;
use App\Product;
use App\Slider;

class AdController extends AdminController{

    // type get
    public function AddGet(){
        $data['users'] = User::orderBy('created_at', 'desc')->get();
        return view('admin.ads.ad_form', ["data" => $data]);
    }

    // type post
    public function AddPost(Request $request){
        $image_name = $request->file('image')->getRealPath();
        Cloudder::upload($image_name, null);
        $imagereturned = Cloudder::getResult();
        $image_id = $imagereturned['public_id'];
        $image_format = $imagereturned['format'];
        $image_new_name = $image_id.'.'.$image_format;
        $ad = new Ad();
        $ad->image = $image_new_name;
        $ad->content = $request->content;
        if ($request->content_type == 2) {
            $ad->content = "offer";
        }
        if (!isset($post['content_type'])) {
            $content_type = 0;
        }else {
            $content_type = $ad->content_type;
        }
        $ad->content_type = $content_type;
        $ad->place = $request->place;
        if ($request->input('type') == 1) {
            $ad->type = "link";
        }elseif ($request->content_type == 2){
            $ad->type = "offer";
        }else {
            $ad->type = "id";
        }
        $ad->save();
        session()->flash('success', trans('messages.added_s'));
        return redirect('admin-panel/ads/show');
    }
    // get all ads
    public function show(Request $request){
        $data['out_link'] = Ad::where('content_type', 0)->orderBy('id' , 'desc')->get();
        $data['products'] = Ad::where('content_type', 1)->orderBy('id' , 'desc')->get();
        $data['offers'] = Ad::where('content_type', 2)->orderBy('id' , 'desc')->get();
        $data['sliders'] = Slider::orderBy('id', 'desc')->get();
        
        return view('admin.ads.ads' , ['data' => $data]);
    }

    public function updateSlider(Request $request) {
        $post = $this->validate(\request(),
            [
                'ad_id' => 'required',
            ]);
            
        
        if (isset($post['ad_id']) && count($post['ad_id']) > 0) {
            DB::table('sliders')->delete();
            foreach($post['ad_id'] as $ad) {
                $banner['ad_id'] = $ad;
                Slider::create($banner);
            }
        }
        session()->flash('success', trans('messages.updated_s'));
        return redirect()->back();
    }

    // get edit page
    public function EditGet(Request $request){
        $data['ad'] = Ad::find($request->id);
        $data['users'] = User::orderBy('created_at', 'desc')->get();

        if ($data['ad']['type'] == 'id') {
            $data['product'] = Product::find($data['ad']['content']);
        }else {
            $data['product'] = [];
        }
        // dd($data['product']);
        return view('admin.ads.ad_edit' , ['data' => $data]);
    }

    // post edit ad
    public function EditPost(Request $request){
        $ad = Ad::find($request->id);
        if($request->file('image')){
            $image = $ad->image;
            $publicId = substr($image, 0 ,strrpos($image, "."));
            // Cloudder::delete($publicId);
            $image_name = $request->file('image')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];
            $image_new_name = $image_id.'.'.$image_format;
            $ad->image = $image_new_name;
        }
        if ($request->input('type') == 1) {
            $ad->type = "link";
        }elseif ($request->content_type == 2){
            $ad->type = "offer";
        }else {
            $ad->type = "id";
        }
        $ad->content = $request->content;
        if ($request->content_type == 2) {
            $ad->content = "offer";
        }
        $ad->content_type = $request->content_type;
        $ad->place = $request->place;
        // dd($ad);
        $ad->save();
        session()->flash('success', trans('messages.updated_s'));
        return redirect('admin-panel/ads/show');
    }

    public function details(Request $request){
        $data['ad'] = Ad::find($request->id);
        if ($data['ad']['type'] == 'id') {
            $data['product'] = Product::findOrFail($data['ad']['content']);
        }else {
            $data['product'] = [];
        }
        return view('admin.ads.ad_details' , ['data' => $data]);
    }

    public function delete(Request $request){
        $ad = Ad::find($request->id);
        if($ad){
            $ad->delete();
        }
        return redirect('admin-panel/ads/show');
    }

    public function fetch_products($userId) {
        $row = User::findOrFail($userId);
        if ($row) {
            $row = $row->products;
        }
        $data = json_decode($row);

        return response($data, 200);
    }

    // get sliders
    public function getSliders() {
        $data['sliders'] = Slider::orderBy('id', 'desc')->get();

        return view('admin.ads.sliders', compact('data'));
    }

    // get add slider
    public function getAddSlider() {
        $data['users'] = User::orderBy('created_at', 'desc')->get();

        return view('admin.ads.slider_form', compact('data'));
    }

    // post add slider
    public function postAddSlider(Request $request) {
        $data = $this->validate(\request(),
            [
                'type' => 'required',
                'content' => 'required',
                'image' => 'required'
            ]);

        $image_name = $request->file('image')->getRealPath();
        Cloudder::upload($image_name, null);
        $imagereturned = Cloudder::getResult();
        $image_id = $imagereturned['public_id'];
        $image_format = $imagereturned['format'];
        $image_new_name = $image_id.'.'.$image_format;
        $data['image'] = $image_new_name;
        if ($request->content_type == 2) {
            $data['content'] = "offer";
        }
        if (!isset($post['content_type'])) {
            $content_type = 0;
        }else {
            $content_type = $data['content_type'];
        }
        $data['content_type'] = $content_type;
        if ($request->input('type') == 1) {
            $data['type'] = "link";
        }else {
            $data['type'] = "id";
        }
        Slider::create($data);

        session()->flash('success', trans('messages.added_s'));
        return redirect()->route('ads.sliders');
    }

    // get edit slider
    public function getEditSlider(Request $request) {
        $data['ad'] = Slider::find($request->id);
        $data['users'] = User::orderBy('created_at', 'desc')->get();

        if ($data['ad']['type'] == 'id') {
            $data['product'] = Product::find($data['ad']['content']);
        }else {
            $data['product'] = [];
        }
        
        return view('admin.ads.slider_edit' , ['data' => $data]);
    }

    // update slider
    public function updatesSlider(Request $request) {
        $data = $this->validate(\request(),
            [
                'type' => 'required',
                'content' => 'required',
                // 'image' => 'required'
            ]);
        $ad = Slider::find($request->id);
        if($request->file('image')){
            $image = $ad->image;
            $publicId = substr($image, 0 ,strrpos($image, "."));
            // Cloudder::delete($publicId);
            $image_name = $request->file('image')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];
            $image_new_name = $image_id.'.'.$image_format;
            $ad->image = $image_new_name;
        }
        if ($request->input('type') == 1) {
            $ad->type = "link";
        }elseif ($request->content_type == 2){
            $ad->type = "offer";
        }else {
            $ad->type = "id";
        }
        $ad->content = $request->content;
        if ($request->content_type == 2) {
            $ad->content = "offer";
        }
        $ad->content_type = $request->content_type;
        $ad->place = $request->place;
        
        $ad->save();
        session()->flash('success', trans('messages.updated_s'));
        return redirect()->route('ads.sliders');
    }

    // get slider
    public function getSlider() {
        $data['ads'] = Ad::orderBy('id', 'desc')->get();
        $data['slider'] = Slider::pluck('ad_id')->toArray();

        return view('admin.ads.slider', compact('data'));
    }

    // delete slider
    public function deleteSlider(Request $request) {
        $data = Slider::where('id', $request->id)->first();

        if ($data) {
            $data->delete();
        }

        return redirect()->back()
        ->with('success', __('messages.deleted_s'));
    }

    // slider details
    public function sliderDetails(Request $request) {
        $data['ad'] = Slider::find($request->id);
        if ($data['ad']['type'] == 'id') {
            $data['product'] = Product::findOrFail($data['ad']['content']);
        }else {
            $data['product'] = [];
        }
        return view('admin.ads.slider_details' , ['data' => $data]);
    }
}
