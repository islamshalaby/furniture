@extends('admin.app')

@section('title' , __('messages.add'))

@section('content')
    <div class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>{{ __('messages.add') }}</h4>
                 </div>
            </div>
        </div>
        <form action="{{route('companies.store')}}" method="post" enctype="multipart/form-data" >
            @csrf
            <div class="custom-file-container" data-upload-id="myFirstImage">
                <label>{{ __('messages.upload') }} ({{ __('messages.single_image') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" required name="logo" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>
            <div class="form-group mb-4">
                <label for="name_en">{{ __('messages.company_name_en') }}</label>
                <input id="name_en" required type="text" name="name_en" class="form-control" >
            </div>
            <div class="form-group mb-4">
                <label for="name_ar">{{ __('messages.company_name_ar') }}</label>
                <input id="name_ar" required type="text" name="name_ar" class="form-control" >
            </div>
            <div class="form-group mb-4">
                <label for="email">{{ __('messages.email') }}</label>
                <input id="email" type="email" name="email" class="form-control" >
            </div>
            <div class="form-group">
                <label for="users">{{ __('messages.user') }}</label>
                <select id="users" name="user_id" class="form-control">
                    <option selected disabled>{{ __('messages.select') }}</option>
                    @foreach ($data['users'] as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <input type="submit" value="{{ __('messages.add') }}" class="btn btn-primary">
        </form>
    </div>
@endsection
