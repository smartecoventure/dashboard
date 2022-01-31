<!doctype html>
<html lang="en" dir="ltr">

<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta http-equiv="X-UA-Compatible" content="IE=edge">
    	<meta name="author" content="GeniusOcean">
    	<meta name="csrf-token" content="{{ csrf_token() }}">
		<!-- Title -->
		<title>{{$gs->title}}</title>
		<!-- favicon -->
		<link rel="icon"  type="image/x-icon" href="{{asset('assets/images/'.$gs->favicon)}}"/>
		<!-- Bootstrap -->
		<link href="{{asset('assets/admin/css/bootstrap.min.css')}}" rel="stylesheet" />
		<!-- Fontawesome -->
		<link rel="stylesheet" href="{{asset('assets/admin/css/fontawesome.css')}}">
		<!-- icofont -->
		<link rel="stylesheet" href="{{asset('assets/admin/css/icofont.min.css')}}">
		<!-- Sidemenu Css -->
		<link href="{{asset('assets/admin/plugins/fullside-menu/css/dark-side-style.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/admin/plugins/fullside-menu/waves.min.css')}}" rel="stylesheet" />

		<link href="{{asset('assets/admin/css/plugin.css')}}" rel="stylesheet" />

		<link href="{{asset('assets/admin/css/jquery.tagit.css')}}" rel="stylesheet" />
    	<link rel="stylesheet" href="{{ asset('assets/admin/css/bootstrap-coloroicker.css') }}">
		<!-- Main Css -->

		<!-- stylesheet -->
		@if(DB::table('admin_languages')->where('is_default','=',1)->first()->rtl == 1)

		<link href="{{asset('assets/admin/css/rtl/style.css')}}" rel="stylesheet"/>
		<link href="{{asset('assets/admin/css/rtl/custom.css')}}" rel="stylesheet"/>
		<link href="{{asset('assets/admin/css/rtl/responsive.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/admin/css/common.css')}}" rel="stylesheet" />

		@else

		<link href="{{asset('assets/admin/css/style.css')}}" rel="stylesheet"/>
		<link href="{{asset('assets/admin/css/custom.css')}}" rel="stylesheet"/>
		<link href="{{asset('assets/admin/css/responsive.css')}}" rel="stylesheet" />
		<link href="{{asset('assets/admin/css/common.css')}}" rel="stylesheet" />

		@endif

		@yield('styles')

	</head>
	<body>
		<div class="page">
			<div class="page-main">
				<!-- Header Menu Area Start -->
				<div class="header">
					<div class="container-fluid">
						<div class="d-flex justify-content-between">
							<a class="admin-logo" href="{{ route('front.index') }}" target="_blank">
								<img src="{{asset('assets/images/'.$gs->logo)}}" alt="">
							</a>
							<div class="menu-toggle-button">
								<a class="nav-link" href="javascript:;" id="sidebarCollapse">
									<div class="my-toggl-icon">
											<span class="bar1"></span>
											<span class="bar2"></span>
											<span class="bar3"></span>
									</div>
								</a>
							</div>

							<div class="right-eliment">
								<ul class="list">

									<input type="hidden" id="all_notf_count" value="{{ route('all-notf-count') }}">

									<li class="login-profile-area">
										<a class="dropdown-toggle-1" href="javascript:;">
											<div class="user-img">
												<img src="{{ Auth::guard('logistics')->user()->photo ? asset('assets/images/logistics/'.Auth::guard('logistics')->user()->photo ):asset('assets/images/noimage.png') }}" alt="">
											</div>
										</a>
										<div class="dropdown-menu">
											<div class="dropdownmenu-wrapper">
													<ul>
														<h5>{{ __('Welcome!') }}</h5>
															<li>
																<a href="{{ route('logistics.profile') }}"><i class="fas fa-user"></i> {{ __('Edit Profile') }}</a>
															</li>
															<li>
																<a href="{{ route('logistics.password') }}"><i class="fas fa-cog"></i> {{ __('Change Password') }}</a>
															</li>
															<li>
																<a href="{{ route('logistics.logout') }}"><i class="fas fa-power-off"></i> {{ __('Logout') }}</a>
															</li>
														</ul>
											</div>
										</div>
									</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
				<!-- Header Menu Area End -->
				<div class="wrapper">
					<!-- Side Menu Area Start -->
					<nav id="sidebar" class="nav-sidebar">
						<ul class="list-unstyled components" id="accordion">
							<li>
								<a href="{{ route('logistics.dashboard') }}" class="wave-effect"><i class="fa fa-home mr-2"></i>{{ __('Dashboard') }}</a>
							</li>
                            <!-- <li>
								<a href="{{-- route('logistics-ready-for-packaging-index') --}}"><i class="fas fa-box-open"></i> {{ __('Ready for Packaging') }}</a>
							</li>
							<li>
								<a href="{{-- route('logistics-pick-up-for-packaging-index') --}}"><i class="fas fa-box-open"></i> {{ __('Pick Up for Packaging') }}</a>
							</li>
                            <li>
								<a href="{{-- route('logistics-completed-packaging-index') --}}"><i class="fas fa-box-open"></i> {{ __('Total Packaging') }}</a>
							</li> -->
                            <li>
								<a href="{{ route('logistics-ready-for-delivery-index') }}"><i class="fas fa-truck"></i> {{ __('Ready for Delivery') }}</a>
							</li>
							<li>
								<a href="{{ route('logistics-pick-up-for-delivery-index') }}"><i class="fas fa-truck"></i> {{ __('Delivery Accepted') }}</a>
							</li>
							<li>
								<a href="{{ route('logistics-on-delivery') }}"><i class="fas fa-truck"></i> {{ __('On Delivery') }}</a>
							</li>
							<li>
								<a href="{{ route('logistics-completed-delivery-index') }}"><i class="fas fa-truck"></i> {{ __('Total Delivery') }}</a>
							</li>
							<li>
								<a href="{{route('logistics-wt-index')}}" class="wave-effect"><i class="fas fa-list"></i>{{ $langg->lang451 }}</a>
							</li>
                            <li>
								<a href="{{ route('logistics.profile') }}"><i class="fas fa-user"></i> {{ __('Edit Profile') }}</a>
							</li>
                            <li>
								<a href="{{ route('logistics.logout') }}"><i class="fas fa-power-off"></i> {{ __('Logout') }}</a>
							</li>
						</ul>
					</nav>
					<!-- Main Content Area Start -->
					@yield('content')
					<!-- Main Content Area End -->
					</div>
				</div>
			</div>

			<script type="text/javascript">
			  var mainurl = "{{url('/')}}";
			  var admin_loader = {{ $gs->is_admin_loader }};
			  var whole_sell = {{ $gs->wholesell }};
			  var getattrUrl = '{{ route('admin-prod-getattributes') }}';
			</script>

		<!-- Dashboard Core -->
		<script src="{{asset('assets/admin/js/vendors/jquery-1.12.4.min.js')}}"></script>
    <script src="{{asset('assets/admin/js/vendors/vue.js')}}"></script>
		<script src="{{asset('assets/admin/js/vendors/bootstrap.min.js')}}"></script>
		<script src="{{asset('assets/admin/js/jqueryui.min.js')}}"></script>
		<!-- Fullside-menu Js-->
		<script src="{{asset('assets/admin/plugins/fullside-menu/jquery.slimscroll.min.js')}}"></script>
		<script src="{{asset('assets/admin/plugins/fullside-menu/waves.min.js')}}"></script>

		<script src="{{asset('assets/admin/js/plugin.js')}}"></script>
		<script src="{{asset('assets/admin/js/Chart.min.js')}}"></script>
		<script src="{{asset('assets/admin/js/tag-it.js')}}"></script>
		<script src="{{asset('assets/admin/js/nicEdit.js')}}"></script>
        <script src="{{asset('assets/admin/js/bootstrap-colorpicker.min.js') }}"></script>
        <script src="{{asset('assets/admin/js/notify.js') }}"></script>

        <script src="{{asset('assets/admin/js/jquery.canvasjs.min.js')}}"></script>

		<script src="{{asset('assets/admin/js/load.js')}}"></script>
		<!-- Custom Js-->
		<script src="{{asset('assets/admin/js/custom.js')}}"></script>
		<!-- AJAX Js-->
		<script src="{{asset('assets/admin/js/myscript.js')}}"></script>



		@yield('scripts')

@if($gs->is_admin_loader == 0)
<style>
	div#geniustable_processing {
		display: none !important;
	}
</style>
@endif

	</body>

</html>
