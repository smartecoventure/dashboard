@extends('layouts.admin') 

@section('content') 

<input type="hidden" id="headerdata" value="{{ __('PICK FOR DELIVERY') }}">
 
                    <div class="content-area">
                        <div class="mr-breadcrumb">
                            <div class="row">
                                <div class="col-lg-12">
                                        <h4 class="heading">{{ __('Pick Up for Delivery') }}</h4>
                                        <ul class="links">
                                            <li>
                                                <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('admin-pick-up-for-delivery-index') }}">{{ __('Pick Up for Delivery') }}</a>
                                            </li>
                                        </ul>
                                </div>
                            </div>
                        </div>
                        <div class="product-area">
                            <div class="row">
                                <div class="col-lg-12">
                                    <div class="mr-table allproduct">
                                        @include('includes.admin.form-success') 
                                        @if(count($datas) == 0)
                                            <strong>No data found!</strong><a href="{{ route('admin-ready-for-delivery-index') }}"> Click to check pick up ready for Deliveries</a>
                                        @else
                                        <div class="table-responsive">
                                        <div class="gocover" style="background: url({{asset('assets/images/'.$gs->admin_loader)}}) no-repeat scroll center center rgba(45, 45, 45, 0.5);"></div>
                                                <table id="geniustable" class="table table-hover dt-responsive" cellspacing="0" width="100%">
                                                    <thead>
                                                        <tr>
                                                            <th>{{ __('Order Number') }}</th>
                                                            <th>{{ __('Customer Name') }}</th>
                                                            <th>{{ __('Customer Phone') }}</th>
                                                            <th>{{ __('Customer Address') }}</th>
                                                            <th>{{ __('Time Pick Up Order') }}</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                    
                                                    @foreach($datas as $data)
                                                
                                                        <tr>
                                                            <td>{{$data->order_number}}</a></td>
                                                            <td>{{$data->customer_name}}</td>
                                                            <td>{{$data->customer_phone}}</td>
                                                            <td>{{$data->customer_address}}</td>
                                                            <td>{{\Carbon\Carbon::parse($data->time_pickup_delivery)->diffForHumans()}}</td>
                                                            <td>
                                                                @if($data->delivery_status == 1)
                                                                    <button type="submit" class="text-white btn btn-primary">
                                                                        <b>{{$data->company}}</b> logistics company on the way to <b>{{$data->center}}</b> fulfilment center to pick delivery
                                                                    </button>
                                                                @elseif($data->delivery_status == 2)
                                                                    <button type="submit" class="text-white btn btn-primary">
                                                                        On Delivery to Customer
                                                                    </button>
                                                                @elseif($data->delivery_status == 3)
                                                                    <button type="submit" class="text-white btn btn-success">
                                                                        Delivered Successfully to Customer
                                                                    </button>
                                                                @endif
                                                            </td>
                                                            @if($data->delivery_status == 1)
                                                            <td>
                                                                <form action="{{ route('admin-accept-delivery-order-confirm') }}" method="POST" enctype="multipart/form-data">
                                                                {{csrf_field()}}
                                                                    <input type="text" value="{{$data->order_number}}" style="display:none;" name="order_number">
                                                                    <button type="submit" class="text-white btn btn-danger">
                                                                        Confirm
                                                                    </button>
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