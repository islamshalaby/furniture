@extends('admin.app')

@section('title' , __('messages.show_slider'))


@section('content')

    <div id="tableSimple" class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>{{ __('messages.show_slider') }}</h4>
                    </div>
                </div>
                @if(Auth::user()->add_data)
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <a class="btn btn-primary" href="{{ route('ads.sliders.create') }}">{{ __('messages.add') }}</a>
                    </div>
                @endif
        </div>
        <div class="widget-content widget-content-area">
            <div class="table-responsive">
                <table id="html5-extension" class="table table-hover non-hover" style="width:100%">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th class="text-center">{{ __('messages.image') }}</th>
                            <th class="text-center">{{ __('messages.details') }}</th>
                            @if(Auth::user()->update_data)
                                <th class="text-center">{{ __('messages.edit') }}</th>
                            @endif
                            @if(Auth::user()->delete_data)
                                <th class="text-center" >{{ __('messages.delete') }}</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        <?php $i = 1; ?>
                        @foreach ($data['sliders'] as $ad)
                            <tr>
                                <td><?=$i;?></td>
                                <td class="text-center"><img src="https://res.cloudinary.com/{{ cloudinary_app_name() }}/image/upload/w_100,q_100/v1581928924/{{ $ad->image }}"  /></td>

                                <td class="text-center blue-color"><a href="{{ route('ads.sliders.details', $ad->id) }}" ><i class="far fa-eye"></i></a></td>
                                @if(Auth::user()->update_data)
                                    <td class="text-center blue-color" ><a href="{{ route('ads.sliders.edit', $ad->id) }}" ><i class="far fa-edit"></i></a></td>
                                @endif
                                @if(Auth::user()->delete_data)
                                    <td class="text-center blue-color" ><a onclick="return confirm('Are you sure you want to delete this item?');" href="{{ route('ads.sliders.delete', $ad->id) }}" ><i class="far fa-trash-alt"></i></a></td>
                                @endif
                                <?php $i++; ?>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        
        </div>

    </div>

@endsection
