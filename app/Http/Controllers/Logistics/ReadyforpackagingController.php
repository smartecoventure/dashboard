<?php

namespace App\Http\Controllers\Logistics;

use Datatables;
use App\Models\Logistic;
use App\Models\Order;
use App\Models\LogisticsPickup;
use App\Models\PackagingCenter;
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
        $this->middleware('auth:logistics');
    }

    //*** GET Request
  	public function index()
  	{
  	    $datas = DB::table('vendor_orders')
        ->join('users','vendor_orders.user_id','=','users.id')
        ->join('orders','vendor_orders.order_number','=','orders.order_number')
        ->where('orders.status','=','processing')
        ->orwhere('orders.status','=','ready for pick up')
        ->orderby('orders.id','desc')
        ->get();
        
        return view('logistics.ready-for-packaging.index',compact('datas'));
    }
    
    public function show($slug)
    {
        $order = Order::where('order_number','=',$slug)->first();
        $cart = unserialize(bzdecompress(utf8_decode($order->cart)));
        
        return view('logistics.order.details',compact('order','cart'));
    }
    
    public function status(Request $request, $id)
    {
        
        $logistics_id = Auth::guard('logistics')->user()->id;
        $order_number = $id;
        
        $logisticsPickUp = new LogisticsPickup();
        
        $logisticsPickUp->logistics_id = $logistics_id;
        $logisticsPickUp->order_number = $order_number;
        $logisticsPickUp->time_pickup = now();
        
        $logisticsPickUp->save();
        
        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'pick up for packaging']);
        
        return redirect()->route('logistics-pick-up-for-packaging-index');
        
    }
    
    public function pickupforpackaging()
  	{
  	    $logistics_id = Auth::guard('logistics')->user()->id;
  	    
  	    $datas = DB::table('logistics_pickups')
        ->where('logistics_pickups.logistics_id','=',$logistics_id)
        ->join('vendor_orders','logistics_pickups.order_number','=','vendor_orders.order_number')
        ->join('users','vendor_orders.user_id','=','users.id')
        ->where('logistics_pickups.pickup_status','<',3)
        ->orderby('logistics_pickups.id','desc')
        ->get();
        
        return view('logistics.pick-up-for-packaging.index',compact('datas'));
    }
    
    public function cancel(Request $request, $id)
    {
        
        $logistics_id = Auth::guard('logistics')->user()->id;
        $order_number = $id;
        $logisticsPickUp = new LogisticsPickup();
        $deleteOrderStatus = LogisticsPickUp::where('order_number','=',$order_number)->where('logistics_id','=',$logistics_id)->delete();
        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'ready for pick up']);
        return redirect()->route('logistics-pick-up-for-packaging-index');
        
    }
    
    //*** JSON Request
    public function datatablescompletedpackaging()
    {
        $logistics_id = Auth::guard('logistics')->user()->id;
        
        $datas = DB::table('orders')
        ->join('logistics_pickups','orders.order_number','=','logistics_pickups.order_number')
        ->join('logistics','logistics_pickups.logistics_id','=','logistics.id')
        ->join('packaging_centers','logistics_pickups.fulfilment_center','=','packaging_centers.id')
        ->where('logistics_pickups.logistics_id','=',$logistics_id)
        ->where('logistics_pickups.pickup_status','=',3)
        ->orderby('orders.id','desc')
        ->get();
        
        //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
  	public function completedpackaging()
  	{
  	    return view('logistics.completed-packaging.index');
    }
    

}
