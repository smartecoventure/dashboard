<?php

namespace App\Http\Controllers\Logistics;

use Datatables;
use App\Models\Logistic;
use App\Models\Order;
use App\Models\LogisticsDelivery;
use App\Models\User;
use App\Models\VendorOrder;
use DB;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator;


class ReadyfordeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:logistics');
    }

    //*** GET Request
  	public function index()
  	{
        $datas = DB::select("SELECT DISTINCT order_number FROM orders WHERE status='ready for delivery' ORDER BY id");
        
        return view('logistics.ready-for-delivery.index',compact('datas'));
    }
    
    public function show($slug)
    {
        $order = Order::where('order_number','=',$slug)->first();
        $cart = unserialize(bzdecompress(utf8_decode($order->cart)));
        
        $datas = DB::select("SELECT DISTINCT vendor_orders.order_number, vendor_orders.user_id, users.owner_name, users.shop_name, users.shop_address, users.shop_number FROM vendor_orders, users WHERE vendor_orders.order_number='$slug' AND vendor_orders.status='completed' AND vendor_orders.user_id = users.id ORDER BY users.id");

        return view('logistics.order.delivery',compact('order','cart','datas'));
    }

    public function showVendor($id)
    {
        $data = User::findOrFail($id);
        return view('logistics.vendor.show',compact('data'));
    }
    
    public function status(Request $request, $id)
    {
        $vendor_id = $request->vendor_id;
        $logistics_id = Auth::guard('logistics')->user()->id;
        $order_number = $id;
        
        $logisticsDelivery = new LogisticsDelivery();
        
        $logisticsDelivery->logistics_id = $logistics_id;
        $logisticsDelivery->order_number = $order_number;
        $logisticsDelivery->vendor_id = $vendor_id;
        $logisticsDelivery->delivery_status = 1;
        $logisticsDelivery->time_pickup_delivery = now();
        
        $logisticsDelivery->save();
        
        $updateVendorOrderStatus = VendorOrder::where('order_number','=',$order_number)->where('user_id','=',$vendor_id)->update(['status' => 'accept delivery']);
        
        $checkVendorOrderCount = VendorOrder::where('order_number','=',$order_number)->where('status','=','completed')->get();
        
        if(count($checkVendorOrderCount) == 0){
            $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'accept delivery']);
        }

        return redirect()->route('logistics-pick-up-for-delivery-index');
        
    }
    
    public function pickupfordelivery()
  	{
        $logistics_id = Auth::guard('logistics')->user()->id;
  	    
  	    $datas = DB::table('logistics_deliveries')
        ->join('users','logistics_deliveries.vendor_id','=','users.id')
        ->join('orders','logistics_deliveries.order_number','=','orders.order_number')
        ->where('logistics_deliveries.logistics_id','=',$logistics_id)
        ->where('logistics_deliveries.delivery_status','=',1)
        ->orderby('logistics_deliveries.id','desc')
        ->get();

        // $datas = DB::select("SELECT * FROM logistics_deliveries WHERE logistics_deliveries.logistics_id = '$logistics_id' AND logistics_deliveries.delivery_status = 1 ORDER BY logistics_deliveries.id");

        return view('logistics.pick-up-for-delivery.index',compact('datas'));
    }
    
    public function cancel(Request $request, $id)
    {
        
        $logistics_id = Auth::guard('logistics')->user()->id;
        $order_number = $id;
        $vendor_id = $request->vendor_id;
        
        $logisticsDelivery = new LogisticsDelivery();
        
        $deleteOrderStatus = LogisticsDelivery::where('order_number','=',$order_number)->where('logistics_id','=',$logistics_id)->where('vendor_id','=',$vendor_id)->delete();

        $updateVendorOrderStatus = VendorOrder::where('order_number','=',$order_number)->where('user_id','=',$vendor_id)->update(['status' => 'completed']);
        
        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'ready for delivery']);
        
        return redirect()->route('logistics-pick-up-for-delivery-index');
        
    }
    

    public function ondelivery()
  	{
        $logistics_id = Auth::guard('logistics')->user()->id;
  	    
  	    $datas = DB::table('logistics_deliveries')
        ->join('users','logistics_deliveries.vendor_id','=','users.id')
        ->join('orders','logistics_deliveries.order_number','=','orders.order_number')
        ->where('logistics_deliveries.logistics_id','=',$logistics_id)
        ->where('logistics_deliveries.delivery_status','=',2)
        ->orderby('logistics_deliveries.id','desc')
        ->get();
          
        return view('logistics.on-delivery.index',compact('datas'));
    }

    public function confirmdelivery(Request $request, $id)
    {
        
        $logistics_id = Auth::guard('logistics')->user()->id;
        $order_number = $id;
        
        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'delivered']);
        $updateDeleiveryStatus = LogisticsDelivery::where('order_number','=',$order_number)->update(['delivery_status' => 3]);
        
        return redirect()->route('logistics-pick-up-for-delivery-index');
        
    }
    
    //*** JSON Request
    public function datatablescompleteddelivery()
    {
        $logistics_id = Auth::guard('logistics')->user()->id;
        
        $datas = DB::select("SELECT DISTINCT vendor_orders.order_number, orders.customer_name, orders.customer_phone, orders.customer_address, logistics_deliveries.logistics_id FROM vendor_orders, orders, logistics_deliveries WHERE vendor_orders.order_number=orders.order_number AND vendor_orders.status='delivered' AND logistics_deliveries.logistics_id='$logistics_id' ORDER BY orders.id DESC");
        
        //--- Integrating This Collection Into Datatables
        return Datatables::of($datas)->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
  	public function completeddelivery()
  	{
  	    $logistics_id = Auth::guard('logistics')->user()->id;
        
        $datas = DB::select("SELECT DISTINCT vendor_orders.order_number, orders.customer_name, orders.customer_phone, orders.customer_address, logistics_deliveries.logistics_id FROM vendor_orders, orders, logistics_deliveries WHERE vendor_orders.order_number=orders.order_number AND vendor_orders.status='delivered' AND logistics_deliveries.logistics_id='$logistics_id' ORDER BY orders.id DESC");

        return view('logistics.completed-delivery.index');
    }
    

}
