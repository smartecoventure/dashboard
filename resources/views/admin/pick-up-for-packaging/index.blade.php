@extends('layouts.admin') 

@section('content')  
                    <div class="content-area">
                        <div class="mr-breadcrumb">
                            <div class="row">
                                <div class="col-lg-12">
                                        <h4 class="heading">{{ __('Pick Up for Packaging') }}</h4>
                                        <ul class="links">
                                            <li>
                                                <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('admin-pick-up-for-packaging-index') }}">{{ __('Pick Up for Packaging') }}</a>
                                            </li>
                                        </ul>
                                </div>
                            </div>
                        </div>
                        <div class="product-area">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mr-table allproduct">
                                        @include('includes.form-success') 
                                        @if(count($datas) == 0)
                                            <strong>No pick up for packaging!</strong><a href="{{ route('admin-ready-for-packaging-index') }}"> Click here to check orders ready for packaging...</a>
                                        @else
                                        <div class="table-responsive">
                                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                                                <table id="geniustable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Company') }}</th>
                                                            <th>{{ __('Order Number') }}</th>
                                                            <th>{{ __('Shop Name') }}</th>
                                                            <th>{{ __('Shop Address') }}</th>
                                                            <th>{{ __('Time Picked') }}</th>
                                                            <th>{{ __('Delivery Status') }}</th>
                                                        </tr>
                                                    </thead>


                                                <tbody>
                                                    @foreach($datas as $data)
                                                
                                                        <tr>
                                                            <td>{{$data->company}}</a></td>
                                                            <td>{{$data->order_number}}</a></td>
                                                            <td>{{$data->shop_name}}</td>
                                                            <td>{{$data->shop_address}}</td>
                                                            <td>{{\Carbon\Carbon::parse($data->time_pickup)->diffForHumans()}}</td>
                                                            <td>
                                                                @if($data->pickup_status == 1)
                                                                    <button type="submit" class="text-white btn btn-warning">
                                                                        On the way to the Vendor Shop
                                                                    </button>
                                                                @elseif($data->pickup_status == 2)
                                                                    <button type="submit" class="text-white btn btn-primary">
                                                                        On the way to the Fulfilment Center
                                                                    </button>
                                                                @elseif($data->pickup_status == 3)
                                                                    <button type="submit" class="text-white btn btn-success">
                                                                        Delivered to Fulfilment Center
                                                                    </button>
                                                                @endif
                                                            </td>
                                                            @if($data->pickup_status == 2)
                                                            <td>
                                                                <form action="{{ route('admin-accept-packaging-order-confirm') }}" method="POST" enctype="multipart/form-data">
                                                                {{csrf_field()}}
                                                                    <label for="center">Select Fulfilment Center</label>
                                                                    <select name="center" required="">
                                                                        <option></option>
                                                                        @foreach($packagingcenter as $center)
                                                                            <option value="{{$center->id}}">
                                                                                {{$center->center}}
                                                                            </option>
                                                                        @endforeach
                                                                    </select>
                                                                    <input name="order_number" type="text" value="{{$data->order_number}}" style="display:none;">
                                                                    <input type="submit" class="text-white btn btn-success" value="Confirm">
                                                                </form>
                                                            </td>
                                                            @endif

                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                    
                                                </table>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


@endsection    

@section('scripts')
@endsection   