@extends('admin.app')

@section('title' , __('messages.edit'))
@push('scripts')
    <script>
        $("#show_type").on("change", function() {
            console.log($(this).val())
            if ($(this).val() == 1) {
                $("#link").prop('disabled', false)
                $("#link").parent(".form-group").show()
                $("#email").prop('disabled', true)
                $("#email").parent(".form-group").hide()
                $("#users_select").prop('disabled', true)
                $("#users_select").parent(".form-group").hide()
            }else {
                $("#link").prop('disabled', true)
                $("#link").parent(".form-group").hide()
                $("#email").prop('disabled', false)
                $("#email").parent(".form-group").show()
                $("#users_select").prop('disabled', false)
                $("#users_select").parent(".form-group").show()
            }
        })
    </script>
@endpush
@section('content')
    <div class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>{{ __('messages.edit') }}</h4>
                 </div>
            </div>
        </div>
        <form action="{{route('companies.update',$data->id)}}" method="post" enctype="multipart/form-data" >
            @csrf
            <input name="_method" type="hidden" value="PUT">
            <div class="form-group mb-4">
                <label for="">{{ __('messages.current_image') }}</label><br>
                <img src="https://res.cloudinary.com/{{ cloudinary_app_name() }}/image/upload/w_100,q_100/v1581928924/{{ $data['logo'] }}"  />
            </div>
            <div class="custom-file-container" data-upload-id="myFirstImage">
                <label>{{ __('messages.change_image') }} ({{ __('messages.single_image') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" name="logo" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>
            <div class="form-group mb-4">
                <label for="name_en">{{ __('messages.company_name_en') }}</label>
                <input id="name_en" required type="text" name="name_en" value="{{ $data['name_en'] }}" class="form-control" >
            </div>
            <div class="form-group mb-4">
                <label for="name_ar">{{ __('messages.company_name_ar') }}</label>
                <input id="name_ar" required type="text" name="name_ar" value="{{ $data['name_ar'] }}" class="form-control" >
            </div>
            <div class="form-group">
                <label for="show_type">{{ __('messages.show_type') }}</label>
                <select id="show_type" name="type" class="form-control">
                    <option selected>{{ __('messages.select') }}</option>
                    <option {{ $data['type'] == 1 ? 'selected' : '' }} value="1">{{ __('messages.outside_the_app') }}</option>
                    <option {{ $data['type'] == 2 ? 'selected' : '' }} value="2">{{ __('messages.inside_the_app') }}</option>
                </select>
            </div>
            <div style="{{ $data['type'] == 2 ? 'display: none' : '' }}" class="form-group mb-4 outside">
                <label for="link">{{ __('messages.link') }}</label>
                <input required {{ $data['type'] == 2 ? 'disabled' : '' }} type="text" name="link" class="form-control" id="link" placeholder="{{ __('messages.link') }}" value="{{ $data['type'] == 1 ? $data['link'] : '' }}" >
            </div>
            <div style="{{ $data['type'] == 1 ? 'display: none' : '' }}" class="form-group mb-4">
                <label for="email">{{ __('messages.email') }}</label>
                <input {{ $data['type'] == 1 ? 'disabled' : '' }} id="email" type="email" name="email" value="{{ $data['email'] }}" class="form-control" >
            </div>
            <div style="{{ $data['type'] == 1 ? 'display: none' : '' }}" class="form-group">
                <label for="users_select">{{ __('messages.user') }}</label>
                <select {{ $data['type'] == 1 ? 'disabled' : '' }} id="users_select" name="user_id" class="form-control">
                    <option selected disabled>{{ __('messages.select') }}</option>
                    @foreach ($data['users'] as $user)
                    <option {{ $data['user_id'] == $user->id ? 'selected' : '' }} value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <input type="submit" value="{{ __('messages.edit') }}" class="btn btn-primary">
        </form>
    </div>
@endsection
