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


class PackagingorderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    //*** GET Request
  	public function index()
  	{
  	    $datas = DB::table('vendor_orders')
        ->join('users','vendor_orders.user_id','=','users.id')
        ->join('orders','vendor_orders.order_number','=','orders.order_number')
        ->where('orders.status','=','packaging')
        ->orderby('orders.id','desc')
        ->get();
        
        return view('admin.packaging-order.index', compact('datas'));
    }
    
    public function confirm(Request $request, $id)
    {
        $data = Order::findOrFail($id);
        
        // We have change the logic, the vendor will get his money when the customer has recieved his products! 
        // foreach($data->vendororders as $vorder)
        // {
        //     $uprice = User::findOrFail($vorder->user_id);
        //     $uprice->current_balance = $uprice->current_balance + $vorder->price;
        //     $uprice->update();
        // }
        
        $order_number = $request->order_number;
        
        $updateOrderStatus = Order::where('order_number','=',$order_number)->update(['status' => 'ready for delivery']);
        
        return redirect()->route('admin-packaging-order-index');
        
    }

}
