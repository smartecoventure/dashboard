@extends('layouts.admin') 

@section('content')  
                    <div class="content-area">
                        <div class="mr-breadcrumb">
                            <div class="row">
                                <div class="col-lg-12">
                                        <h4 class="heading">{{ __('Packaging') }}</h4>
                                        <ul class="links">
                                            <li>
                                                <a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }} </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('admin-packaging-order-index') }}">{{ __('Packaging') }}</a>
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
                                            <strong>No order ready packaging!</strong><a href="{{ route('admin-ready-for-packaging-index') }}"> Click here to check orders ready for packaging...</a>
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
                                                            <th>{{ __('Delivery Status') }}</th>
                                                        </tr>
                                                    </thead>


                                                <tbody>
                                                    @foreach($datas as $data)
                                                
                                                        <tr>
                                                            <td>{{$data->order_number}}</a></td>
                                                            <td>{{$data->customer_name}}</td>
                                                            <td>{{$data->customer_phone}}</td>
                                                            <td>{{$data->customer_address}}</td>
                                                            <td>
                                                                <form action="{{ route('admin-packaging-order-confirm',$data->id) }}" method="POST" enctype="multipart/form-data">
                                                                {{csrf_field()}}
                                                                    <input name="order_number" type="text" value="{{$data->order_number}}" style="display:none;">
                                                                    <input type="submit" class="text-white btn btn-success" value="Confirm Ready for Delivery">
                                                                </form>
                                                            </td>    

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