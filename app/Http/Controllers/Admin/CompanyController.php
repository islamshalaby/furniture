<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Http\Request;
use App\Company;
use App\User;
use Illuminate\Support\Facades\Auth;
use JD\Cloudder\Facades\Cloudder;

class CompanyController extends AdminController
{
    /**
     * Display a listing of the companies.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $data = Company::where('deleted', 0)->OrderBy('id', 'Desc')->get();

        return view('admin.company.index', compact('data'));
    }

    /**
     * Show the form for creating a new company.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['users'] = User::where('active', 1)->has('company', '!=', 1)->select('id', 'name')->get();

        return view('admin.company.create', compact('data'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $post = $this->validate(\request(),
        [
            'name_en' => 'required',
            'name_ar' => 'required',
            'logo' => 'required',
            'type' => 'required',
            'user_id' => '',
            'email' => '',
            'link' => ''
        ]);
        
        if ($request->logo != null) {
            $image_name = $request->file('logo')->getRealPath();
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];
            $image_new_name = $image_id . '.' . $image_format;
            $post['logo'] = $image_new_name;
        }

        Company::create($post);

        session()->flash('success', trans('messages.added_s'));
        return redirect( route('companies.index'));
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified company.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data = Company::where('id', $id)->first();
        $users = Company::where('user_id', '!=', $data['user_id'])->pluck('user_id');
        $data['users'] = User::where('active', 1)->whereNotIn('id', $users)->select('id', 'name')->get();

        return view('admin.company.edit', compact('data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $this->validate(\request(),
            [
                'name_en' => 'required',
                'name_ar' => 'required',
                'type' => 'required',
                'user_id' => '',
                'email' => '',
                'link' => ''
            ]);
        $company = Company::find($id);

        if ($request->logo != null) {
            $image_name = $request->file('logo')->getRealPath();
            if (!empty($company->logo)) {
                $publicId = substr($company->logo, 0, strrpos($company->logo, "."));
                Cloudder::delete($publicId);
            }
            Cloudder::upload($image_name, null);
            $imagereturned = Cloudder::getResult();
            $image_id = $imagereturned['public_id'];
            $image_format = $imagereturned['format'];
            $image_new_name = $image_id . '.' . $image_format;
            $data['logo'] = $image_new_name;
        }

        $company->update($data);
        session()->flash('success', trans('messages.updated_s'));
        return redirect( route('companies.index'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $company = Company::find($id);
        $company->update(['deleted' => 1]);

        session()->flash('success', trans('messages.deleted_s'));
        return redirect( route('companies.index'));
    }
}
