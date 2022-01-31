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
                                                <th>{{ __("Center ID#") }}</th>
                                                <td>{{ __("kahioja_fulfilment_center_0").$data->id}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Center") }}</th>
                                                <td>{{$data->center}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Phone") }}</th>
                                                <td>{{$data->phone}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Address") }}</th>
                                                <td>{{$data->address}}</td>
                                            </tr>
                                            <tr>
                                                <th>{{ __("Opened") }}</th>
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