<?php

namespace App\Http\Controllers\Vendor;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use App\Models\Order;
use DB;
use App\Models\LogisticsDelivery;
use App\Models\VendorOrder;

class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        $user = Auth::user();
        $orders = VendorOrder::where('user_id','=',$user->id)->orderBy('id','desc')->get()->groupBy('order_number');
        return view('vendor.order.index',compact('user','orders'));
    }

    public function show($slug)
    {
        $user = Auth::user();
        $order = Order::where('order_number','=',$slug)->first();
        $cart = unserialize(bzdecompress(utf8_decode($order->cart)));
        return view('vendor.order.details',compact('user','order','cart'));
    }

    public function license(Request $request, $slug)
    {
        $order = Order::where('order_number','=',$slug)->first();
        $cart = unserialize(bzdecompress(utf8_decode($order->cart)));
        $cart->items[$request->license_key]['license'] = $request->license;
        $order->cart = utf8_encode(bzcompress(serialize($cart), 9));
        $order->update();         
        $msg = 'Successfully Changed The License Key.';
        return response()->json($msg);
    }



    public function invoice($slug)
    {
        $user = Auth::user();
        $order = Order::where('order_number','=',$slug)->first();
        $cart = unserialize(bzdecompress(utf8_decode($order->cart)));
        return view('vendor.order.invoice',compact('user','order','cart'));
    }

    public function printpage($slug)
    {
        $user = Auth::user();
        $order = Order::where('order_number','=',$slug)->first();
        $cart = unserialize(bzdecompress(utf8_decode($order->cart)));
        return view('vendor.order.print',compact('user','order','cart'));
    }

    public function status($slug,$status)
    {
        $user = Auth::user();
        $mainorder = VendorOrder::where('order_number','=',$slug)->where('user_id','=',$user->id)->first();
        
        if ($mainorder->status == "completed" || $mainorder->status == "accepted delivery" || $mainorder->status == "picked up for delivery" || $mainorder->status == "on delivery" || $mainorder->status == "delivered"){
            return redirect()->back()->with('success','This Order is Already Completed');
        }else{
            $user = Auth::user();
            $order = VendorOrder::where('order_number','=',$slug)->where('user_id','=',$user->id)->update(['status' => $status]);
            
            if($status == "pending"){
                $updateOrderStatus = Order::where('order_number','=',$slug)->update(['status' => 'pending']);
            }else if($status == "processing"){
                $updateOrderStatus = Order::where('order_number','=',$slug)->update(['status' => 'processing']);
            }else if($status == "completed"){
                $updateOrderStatus = Order::where('order_number','=',$slug)->update(['status' => 'ready for delivery']);
            }
            return redirect()->route('vendor-order-index')->with('success','Order Status Updated Successfully');
        }
    }
    
    public function pickupforpackaging()
  	{
  	    $user = Auth::user();
     
        $datas = DB::select("SELECT DISTINCT vendor_orders.order_number, logistics_deliveries.logistics_id, logistics_deliveries.time_pickup_delivery, logistics_deliveries.vendor_id, logistics_deliveries.delivery_status, logistics.company FROM vendor_orders, logistics_deliveries, logistics WHERE logistics_deliveries.logistics_id = logistics.id AND vendor_orders.order_number=logistics_deliveries.order_number AND vendor_orders.user_id = '$user->id' AND vendor_orders.status='accept delivery' AND  logistics_deliveries.vendor_id='$user->id'");

        return view('vendor.pick-up-for-packaging.index',compact('datas'));
    }
    
    public function confirm(Request $request, $id)
    {
        
        $order_number = $id;

        $user = Auth::user();
        
        $updatePickUpStatus = LogisticsDelivery::where('order_number','=',$order_number)->where('vendor_id','=',$user->id)->update(['delivery_status' => 2]);

        $updateVendorOrderStatus = VendorOrder::where('order_number','=',$order_number)->where('user_id','=',$user->id)->update(['status' => 'picked up for delivery']);

        $checkVendorOrderCount = VendorOrder::where('order_number','=',$order_number)->where('status','=','completed')->orwhere('status','=','accept delivery')->get();
        
        if(count($checkVendorOrderCount) == 0){
            $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'on delivery']);
        }
        
        return redirect()->route('pick-up-for-packaging-index');
        
    }

    public function ondelivery()
  	{
        $user = Auth::user();
     
        $datas = DB::select("SELECT DISTINCT vendor_orders.order_number, logistics_deliveries.time_pickup_delivery, logistics_deliveries.delivery_status, logistics.company, orders.customer_name, orders.customer_phone, orders.customer_address FROM vendor_orders, logistics_deliveries, logistics, orders WHERE logistics_deliveries.logistics_id = logistics.id AND vendor_orders.order_number=logistics_deliveries.order_number AND vendor_orders.user_id = '$user->id' AND vendor_orders.status='picked up for delivery' AND  logistics_deliveries.vendor_id='$user->id' AND vendor_orders.order_number=orders.order_number");

        return view('vendor.on-delivery.index',compact('datas'));
    }

    public function alldelivery()
  	{
        $user = Auth::user();
     
        $datas = DB::select("SELECT DISTINCT vendor_orders.order_number, orders.customer_name, orders.customer_phone, orders.customer_address, logistics_deliveries.vendor_id FROM vendor_orders, orders, logistics_deliveries WHERE vendor_orders.order_number=orders.order_number AND vendor_orders.status='delivered' AND logistics_deliveries.vendor_id='$user->id' ORDER BY orders.id DESC");
        
        return view('vendor.completed-delivery.index',compact('datas'));
    }

}
