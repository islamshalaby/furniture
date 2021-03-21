@extends('admin.app')

@section('title' , __('messages.add_new_ad'))

@push('scripts')

    <script>
        $("select#users").on("change", function () {
            $('select#products').html("")
            var userId = $(this).find("option:selected").val();

            $.ajax({
                url : "fetchproducts/" + userId,
                type : 'GET',
                success : function (data) {
                    $('.productsParent').show()
                    $('select#products').prop("disabled", false)
                    data.forEach(function (product) {
                        $('select#products').append(
                            "<option value='" + product.id + "'>" + product.title + "</option>"
                        )
                    })
                }
            })
        })
        $("#content_type").on("change", function() {
            if ($(this).val() == 1) {
                $("select#users").parent('.form-group').show()
            }else {
                $("select#users").parent('.form-group').hide()
                $('select#products').prop('disabled', true)
                $('select#products').parent('.form-group').hide()
            }
        })
        $("#ad_type").on("change", function() {
            if(this.value == 1) {
                $(".outside").show()
                $('.productsParent').hide()
                $('select#products').prop("disabled", true)
                $(".outside input").prop("disabled", false)
                $(".inside").hide()
                $("#content_type").parent('.form-group').hide()
                $("#content_type").prop('disabled', true)
            }else {
                $(".outside").hide()
                $(".outside input").prop("disabled", true)
                $("#content_type").parent('.form-group').show()
                $("#content_type").prop('disabled', false)
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
                        <h4>{{ __('messages.add_new_ad') }}</h4>
                 </div>
        </div>
        <form action="" method="post" enctype="multipart/form-data" >
            @csrf

            <div class="custom-file-container" data-upload-id="myFirstImage">
                <label>{{ __('messages.upload') }} ({{ __('messages.single_image') }}) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                <label class="custom-file-container__custom-file" >
                    <input type="file" required name="image" class="custom-file-container__custom-file__custom-file-input" accept="image/*">
                    <input type="hidden" name="MAX_FILE_SIZE" value="10485760" />
                    <span class="custom-file-container__custom-file__custom-file-control"></span>
                </label>
                <div class="custom-file-container__image-preview"></div>
            </div>
            
            <div class="form-group">
                <label for="sel1">{{ __('messages.ad_type') }}</label>
                <select id="ad_type" name="type" class="form-control">
                    <option selected>{{ __('messages.select') }}</option>
                    <option value="1">{{ __('messages.outside_the_app') }}</option>
                    <option value="2">{{ __('messages.inside_the_app') }}</option>
                </select>
            </div>
            <div style="display: none" class="form-group mb-4 outside">
                <label for="link">{{ __('messages.link') }}</label>
                <input required type="text" name="content" class="form-control" id="link" placeholder="{{ __('messages.link') }}" value="" >
            </div>
            <div style="display: none" class="form-group">
                <label for="content_type">{{ __('messages.type') }}</label>
                <select id="content_type" name="content_type" class="form-control">
                    <option disabled selected>{{ __('messages.select') }}</option>
                    <option value="1">{{ __('messages.products') }}</option>
                    <option value="2">{{ __('messages.offers') }}</option>
                </select>
            </div>
            <div style="display: none" class="form-group inside">
                <label for="users">{{ __('messages.user') }}</label>
                <select id="users" class="form-control">
                    <option selected disabled>{{ __('messages.select') }}</option>
                    @foreach ($data['users'] as $user)
                    <option value="{{ $user->id }}">{{ $user->name }}</option>
                    @endforeach
                </select>
            </div>
            <div style="display: none" class="form-group productsParent">
                <label for="products">{{ __('messages.product') }}</label>
                <select id="products" class="form-control" name="content">
                </select>
            </div>

            <input type="submit" value="{{ __('messages.add') }}" class="btn btn-primary">
        </form>
    </div>
@endsection
