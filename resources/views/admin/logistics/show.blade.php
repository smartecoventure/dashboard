@extends('layouts.load')
@section('content')

						<div class="content-area no-padding">
							<div class="add-product-content1">
								<div class="row">
									<div class="col-lg-12">
										<div class="product-description">
											<div class="body-area">

                                    <div class="table-responsive show-table">
                                        <table class="table">
                                            <tr>
                                                <th>{{ __("Company ID#") }}</th>
                                                <td>{{ __("kahioja_logistics_0").$data->id}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Company Photo") }}</th>
                                                <td>
                                              <img src="{{ $data->photo ? asset('assets/images/logistics/'.$data->photo):asset('assets/images/noimage.png')}}" alt="{{ __("No Image") }}">

                                                </td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Company Name") }}</th>
                                                <td>{{$data->company}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Company Email") }}</th>
                                                <td>{{$data->email}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Company Phone") }}</th>
                                                <td>{{$data->phone}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Company Address") }}</th>
                                                <td>{{$data->address}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Joined") }}</th>
                                                <td>{{$data->created_at->diffForHumans()}}</td>
                                            </tr>
                                        </table>
                                    </div>


											</div>
										</div>
									</div>
								</div>
							</div>
						</div>

@endsection