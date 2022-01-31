<?php

namespace App\Http\Controllers\Admin;

use App\Classes\GeniusMailer;
use Datatables;
use App\Models\Admin;
use App\Models\Order;
use App\Models\VendorOrder;
use App\Models\PackagingCenter;
use App\Models\LogisticsPickup;
use App\Models\LogisticsDelivery;
use App\Models\Generalsetting;
use App\Models\User;
use DB;
use App\Models\OrderTrack;
use App\Models\Product;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator;


class ReadyfordeliveryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    //*** JSON Request
    public function datatables()
    {
        $datas = DB::table('orders')
        ->join('logistics_pickups','orders.order_number','=','logistics_pickups.order_number')
        ->join('packaging_centers','logistics_pickups.fulfilment_center','=','packaging_centers.id')
        ->where('orders.status','=','ready for delivery')
        ->orderby('orders.id','desc')
        ->get();
        
        //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
  	public function index()
  	{
  	    $datas = DB::table('orders')
        ->join('logistics_pickups','orders.order_number','=','logistics_pickups.order_number')
        ->join('packaging_centers','logistics_pickups.fulfilment_center','=','packaging_centers.id')
        ->where('orders.status','=','ready for delivery')
        ->orderby('orders.id','desc')
        ->get();
        
        return view('admin.ready-for-delivery.index');
    }
    
    public function pickupfordelivery()
  	{
  	    $datas = DB::table('orders')
        ->join('logistics_deliveries','orders.order_number','=','logistics_deliveries.order_number')
        ->join('logistics','logistics_deliveries.logistics_id','=','logistics.id')
        ->join('logistics_pickups','orders.order_number','=','logistics_pickups.order_number')
        ->join('packaging_centers','logistics_pickups.fulfilment_center','=','packaging_centers.id')
        ->where('orders.status','=','pick up for delivery')
        ->orderby('logistics_deliveries.id','asc')
        ->get();
        
        return view('admin.pick-up-for-delivery.index',compact('datas'));
    }
    
    public function confirm(Request $request)
    {
        $order_number = $request->order_number;
        
        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'on delivery']);
        
        $updateDeleiveryStatus = LogisticsDelivery::where('order_number','=',$order_number)->update(['delivery_status' => 2]);
        
        return redirect()->route('admin-pick-up-for-delivery-index');
        
    }
    
    public function delivery()
  	{
  	    $datas = DB::table('orders')
        ->join('logistics_deliveries','orders.order_number','=','logistics_deliveries.order_number')
        ->join('logistics','logistics_deliveries.logistics_id','=','logistics.id')
        ->join('logistics_pickups','orders.order_number','=','logistics_pickups.order_number')
        ->join('packaging_centers','logistics_pickups.fulfilment_center','=','packaging_centers.id')
        ->where('orders.status','=','on delivery')
        ->orwhere('orders.status','=','delivered')
        ->orderby('orders.id','asc')
        ->get();
        
        return view('admin.delivery.index',compact('datas'));
    }
    
    public function confirmdelivery(Request $request)
    {
        $order_number = $request->order_number;
        
        $data = DB::table('orders')->where('order_number','=',$order_number)->first();
        
        $gs = Generalsetting::findOrFail(1);
            
            if($gs->is_smtp == 1)
            {
                $maildata = [
                    'to' => $data->customer_email,
                    'subject' => 'Your order '.$data->order_number.' is Completed!',
                    'body' => "Hello ".$data->customer_name.","."\n Thank you for shopping with us. We are looking forward to your next visit.",
                ];
    
                $mailer = new GeniusMailer();
                $mailer->sendCustomMail($maildata);                
            }
            else
            {
               $to = $data->customer_email;
               $subject = 'Your order '.$data->order_number.' is Completed!';
               $msg = "Hello ".$data->customer_name.","."\n Thank you for shopping with us. We are looking forward to your next visit.";
            $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
               mail($to,$subject,$msg,$headers);                
            }
        
        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'completed']);
        
        $updateDeleiveryStatus = LogisticsDelivery::where('order_number','=',$order_number)->update(['delivery_status' => 4]);
        
        return redirect()->route('admin-delivery-index');
        
    }
    
    //*** JSON Request
    public function datatablescompleteddelivery()
    {
        $datas = DB::table('orders')
        ->join('logistics_deliveries','orders.order_number','=','logistics_deliveries.order_number')
        ->join('logistics','logistics_deliveries.logistics_id','=','logistics.id')
        ->where('orders.status','=','completed')
        ->orderby('orders.id','desc')
        ->get();
        
        //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
  	public function completeddelivery()
  	{
  	    $datas = DB::table('orders')
        ->join('logistics_deliveries','orders.order_number','=','logistics_deliveries.order_number')
        ->join('logistics','logistics_deliveries.logistics_id','=','logistics.id')
        ->where('orders.status','=','completed')
        ->orderby('orders.id','desc')
        ->get();
        
        return view('admin.completed-delivery.index');
    }

}
