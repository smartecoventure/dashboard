<?php

namespace App\Http\Controllers\Admin;

use Datatables;
use App\Models\Admin;
use App\Models\Order;
use App\Models\VendorOrder;
use App\Models\PackagingCenter;
use App\Models\LogisticsPickup;
use App\Models\LogisticsDelivery;
use App\Models\User;
use DB;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator;


class ReadyforpackagingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    //*** JSON Request
    public function datatables()
    {
        $datas = DB::table('vendor_orders')
        ->join('users','vendor_orders.user_id','=','users.id')
        ->join('orders','vendor_orders.order_number','=','orders.order_number')
        ->where('orders.status','=','processing')
        ->orwhere('orders.status','=','ready for pick up')
        ->orderby('orders.id','desc')
        ->get();
        
        //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
  	public function index()
  	{
        return view('admin.ready-for-packaging.index');
    }
    
    public function pickupforpackaging()
  	{
  	    $datas = DB::table('logistics_pickups')
        ->join('logistics','logistics_pickups.logistics_id','=','logistics.id')
        ->join('vendor_orders','logistics_pickups.order_number','=','vendor_orders.order_number')
        ->join('users','vendor_orders.user_id','=','users.id')
        ->orderby('logistics_pickups.id','desc')
        ->get();
        
        $packagingcenter = DB::table('packaging_centers')->orderby('center','asc')->get();
        
        return view('admin.pick-up-for-packaging.index',compact('datas','packagingcenter'));
    }
    
    public function confirm(Request $request)
    {
        
        $fulfilment_center = $request->center;
        $order_number = $request->order_number;
        
        $updatePickUpStatus = LogisticsPickUp::where('order_number','=',$order_number)->update(['pickup_status' => 3, 'fulfilment_center' => $fulfilment_center]);

        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'packaging']);
        
        return redirect()->route('admin-pick-up-for-packaging-index');
        
    }

}
