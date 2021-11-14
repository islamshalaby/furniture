@extends('admin.app')

@section('title' , __('messages.main_ads_second'))


@section('content')

    <div id="tableSimple" class="col-lg-12 col-12 layout-spacing">
        <div class="statbox widget box box-shadow">
            <div class="widget-header">
                
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>{{ __('messages.main_ads_second') }}</h4>
                    </div>
                </div>
                @if(Auth::user()->add_data)
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <a class="btn btn-primary" href="/admin-panel/ads/add">{{ __('messages.add') }}</a>
                    </div>
                @endif
        </div>
        <div class="widget-content widget-content-area">

        <ul class="nav nav-tabs  mb-3 mt-3" id="iconTab" role="tablist">
            <li class="nav-item">
                <a class="nav-link active" id="icon-outlink-tab" data-toggle="tab" href="#icon-outlink" role="tab" aria-controls="icon-home" aria-selected="true">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-airplay"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path><polygon points="12 15 17 21 7 21 12 15"></polygon></svg>
                    {{ __('messages.outer_link_ads') }}
                 </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="icon-products-tab" data-toggle="tab" href="#icon-products" role="tab" aria-controls="icon-contact" aria-selected="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-airplay"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path><polygon points="12 15 17 21 7 21 12 15"></polygon></svg>
                    {{ __('messages.products_ads') }}
                 </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" id="icon-offers-tab" data-toggle="tab" href="#icon-offers" role="tab" aria-controls="icon-contact" aria-selected="false">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-airplay"><path d="M5 17H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2h-1"></path><polygon points="12 15 17 21 7 21 12 15"></polygon></svg>
                    {{ __('messages.offers_ads') }}
                 </a>
            </li>
        </ul>
        <div class="tab-content" id="iconTabContent-1">
            <div class="tab-pane fade show active" id="icon-outlink" role="tabpanel" aria-labelledby="icon-outlink-tab">
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
                            @foreach ($data['out_link'] as $ad)
                                <tr>
                                    <td><?=$i;?></td>
                                    <td class="text-center"><img src="https://res.cloudinary.com/{{ cloudinary_app_name() }}/image/upload/w_100,q_100/v1581928924/{{ $ad->image }}"  /></td>

                                    <td class="text-center blue-color"><a href="/admin-panel/ads/details/{{ $ad->id }}" ><i class="far fa-eye"></i></a></td>
                                    @if(Auth::user()->update_data)
                                        <td class="text-center blue-color" ><a href="/admin-panel/ads/edit/{{ $ad->id }}" ><i class="far fa-edit"></i></a></td>
                                    @endif
                                    @if(Auth::user()->delete_data)
                                        <td class="text-center blue-color" ><a onclick="return confirm('Are you sure you want to delete this item?');" href="/admin-panel/ads/delete/{{ $ad->id }}" ><i class="far fa-trash-alt"></i></a></td>
                                    @endif
                                    <?php $i++; ?>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="icon-products" role="tabpanel" aria-labelledby="icon-products-tab">
                <div class="table-responsive">
                    <table id="html5-extension2" class="table table-hover non-hover" style="width:100%">
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
                            @foreach ($data['products'] as $ad)
                                <tr>
                                    <td><?=$i;?></td>
                                    <td class="text-center"><img src="https://res.cloudinary.com/{{ cloudinary_app_name() }}/image/upload/w_100,q_100/v1581928924/{{ $ad->image }}"  /></td>

                                    <td class="text-center blue-color"><a href="/admin-panel/ads/details/{{ $ad->id }}" ><i class="far fa-eye"></i></a></td>
                                    @if(Auth::user()->update_data)
                                        <td class="text-center blue-color" ><a href="/admin-panel/ads/edit/{{ $ad->id }}" ><i class="far fa-edit"></i></a></td>
                                    @endif
                                    @if(Auth::user()->delete_data)
                                        <td class="text-center blue-color" ><a onclick="return confirm('Are you sure you want to delete this item?');" href="/admin-panel/ads/delete/{{ $ad->id }}" ><i class="far fa-trash-alt"></i></a></td>
                                    @endif
                                    <?php $i++; ?>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="tab-pane fade" id="icon-offers" role="tabpanel" aria-labelledby="icon-offers-tab">
                <div class="table-responsive">
                    <table id="html5-extension3" class="table table-hover non-hover" style="width:100%">
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
                            @foreach ($data['offers'] as $ad)
                                <tr>
                                    <td><?=$i;?></td>
                                    <td class="text-center"><img src="https://res.cloudinary.com/{{ cloudinary_app_name() }}/image/upload/w_100,q_100/v1581928924/{{ $ad->image }}"  /></td>

                                    <td class="text-center blue-color"><a href="/admin-panel/ads/details/{{ $ad->id }}" ><i class="far fa-eye"></i></a></td>
                                    @if(Auth::user()->update_data)
                                        <td class="text-center blue-color" ><a href="/admin-panel/ads/edit/{{ $ad->id }}" ><i class="far fa-edit"></i></a></td>
                                    @endif
                                    @if(Auth::user()->delete_data)
                                        <td class="text-center blue-color" ><a onclick="return confirm('Are you sure you want to delete this item?');" href="/admin-panel/ads/delete/{{ $ad->id }}" ><i class="far fa-trash-alt"></i></a></td>
                                    @endif
                                    <?php $i++; ?>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>



           
        </div>

    </div>

@endsection
