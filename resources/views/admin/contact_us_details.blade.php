@extends('admin.app')

@section('title' , __('messages.contact_us_details'))

@section('content')

    <div id="tableSimple" class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
            <div class="row">
                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
					
                    <h4>{{ __('messages.contact_us_details') }}</h4>
                </div>
            </div>
        </div>
        <div class="widget-content widget-content-area">
            <div class="table-responsive"> 
                <table class="table table-bordered mb-4">
                    <tbody>
                        <tr>
                            <td class="label-table" > {{ __('messages.user') }}</td>
                            <td>
                                <a target="_blank" href="{{ route('users.details', $data['contact_us']->user_id) }}">
                                    {{ $data['contact_us']->user->name }}
                                </a>
                            </td>
                        </tr>
                        <tr>
                            <td class="label-table" > {{ __('messages.phone') }}</td>
                            <td>{{ $data['contact_us']['phone'] }}</td>
                        </tr>
                        <tr>
                            <td class="label-table" > {{ __('messages.message') }}</td>
                            <td>{{ $data['contact_us']['message'] }}</td>
                        </tr> 
                        <tr>
                            <td class="label-table" > {{ __('messages.date') }}</td>
                            <td>{{ $data['contact_us']['created_at'] }}</td>
                        </tr> 
                    </tbody>
                </table>
            </div>
        </div>
        <div class="row">
            @if (count($data['contact_us']->images) > 0)
            @foreach ($data['contact_us']->images as $image)
            <div class="col-2 product_image">
                <img style="width: 100%" src="https://res.cloudinary.com/{{ cloudinary_app_name() }}/image/upload/w_100,q_100/v1581928924/{{ $image->image }}"  />
            </div>
            @endforeach
            @endif
        </div>
    </div>  
    
    
    

@endsection



