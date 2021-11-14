@extends('admin.app')

@section('title' , __('messages.edit_slider_ads') )


@section('content')
    <div class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>{{ __('messages.edit_slider_ads') }}</h4>
                 </div>
        </div>
        <form action="{{route('ads.update.slider')}}" method="post" >
            @csrf

            <div id="ads_check" class="row" >
                                        
                @if (count($data['ads']) > 0)
                @foreach ($data['ads'] as $ad)
                <div class="col-md-6" >
                    <div >
                        <label class="new-control new-checkbox new-checkbox-text checkbox-primary">
                            <input name="ad_id[]" value="{{ $ad->id }}" {{ in_array($ad->id, $data['slider']) ? 'checked' : '' }} type="checkbox" class="new-control-input">
                            <span class="new-control-indicator"></span><span class="new-chk-content"><img src="https://res.cloudinary.com/{{ cloudinary_app_name() }}/image/upload/w_100,q_100/v1601416550/{{ $ad->image }}" /></span>
                        </label>
                    </div>     
                </div>
                @endforeach
                @endif
            </div>

            <input type="submit" value="{{ __('messages.update') }}" class="btn btn-primary">
        </form>
    </div>
@endsection
