<?php

namespace App\Http\Controllers\Front;

use App\Classes\GeniusMailer;
use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderTrack;
use App\Models\Pagesetting;
use App\Models\PaymentGateway;
use App\Models\Pickup;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
use App\Models\VendorOrder;
use Auth;
use Illuminate\Http\Request;
use Session;

use KingFlamez\Rave\Facades\Rave as Flutterwave;

class FlutterwaveController extends Controller
{
    public function initialize(Request $request)
    {
        if($request->pass_check){
            $users = User::where('email','=',$request->personal_email)->get();
            if(count($users) == 0) {
                if ($request->personal_pass == $request->personal_confirm){
                    $user = new User;
                    $user->name = $request->personal_name; 
                    $user->email = $request->personal_email;   
                    $user->password = bcrypt($request->personal_pass);
                    $token = md5(time().$request->personal_name.$request->personal_email);
                    $user->verification_link = $token;
                    $user->affilate_code = md5($request->name.$request->email);
                    $user->email_verified = 'Yes';
                    $user->save();
                    Auth::guard('web')->login($user);                     
                }else{
                    return redirect()->back()->with('unsuccess',"Confirm Password Doesn't Match.");     
                }
            }
            else {
                return redirect()->back()->with('unsuccess',"This Email Already Exist.");  
            }
        }
        
        if(!Session::has('cart')) {
            return redirect()->route('front.cart')->with('success',"You don't have any product to checkout.");
        }

        
        if(Session::has('currency')) {
            $curr = Currency::find(Session::get('currency'));
            $currency_name = $curr->name;
        }else{
            $curr = Currency::where('is_default','=',1)->first();
            $currency_name = $curr->name;
        }

        $gs = Generalsetting::findOrFail(1);
        $oldCart = Session::get('cart');
        $cart = new Cart($oldCart);

        try{
            //This generates a payment reference
            $reference = Flutterwave::generateReference();

            // Enter the details of the payment
            $data = [
                'payment_options' => 'card,banktransfer',
                'amount' => round($request->total / $curr->value, 2),
                'email' => request()->email,
                'tx_ref' => $reference,
                'currency' => $currency_name,
                'redirect_url' => route('callback'),
                'customer' => [
                    'email' => request()->email,
                    "phone_number" => request()->phone,
                    "name" => request()->name
                ],

                "customizations" => [
                    "title" => 'Checkout Payment',
                    "description" => "Payment for items purchased at Kahioja Stores"
                ]
            ];

            $payment = Flutterwave::initializePayment($data);


            if($payment['status'] !== 'success') {
                return redirect()->route('front.checkout')->with('unsuccess','Payment Failed. Please try again later');
            }

            $order = new Order;
    
            foreach($cart->items as $key => $prod){
                if(!empty($prod['item']['license']) && !empty($prod['item']['license_qty'])){
                    foreach($prod['item']['license_qty']as $ttl => $dtl){
                        if($dtl != 0){
                            $dtl--;
                            $produc = Product::findOrFail($prod['item']['id']);
                            $temp = $produc->license_qty;
                            $temp[$ttl] = $dtl;
                            $final = implode(',', $temp);
                            $produc->license_qty = $final;
                            $produc->update();
                            $temp =  $produc->license;
                            $license = $temp[$ttl];
                                $oldCart = Session::has('cart') ? Session::get('cart') : null;
                                $cart = new Cart($oldCart);
                                $cart->updateLicense($prod['item']['id'],$license);  
                                Session::put('cart',$cart);
                            break;
                        }                    
                    }
                }
            }

            $item_name = $gs->title." Order";
            $item_number = str_random(4).time();
            
            $order['user_id'] = $request->user_id;
            $order['cart'] = utf8_encode(bzcompress(serialize($cart), 9)); 
            $order['totalQty'] = $request->totalQty;
            $order['pay_amount'] = round($request->total / $curr->value, 2);
            $order['method'] = $request->method;
            $order['shipping'] = $request->shipping;
            $order['pickup_location'] = $request->pickup_location;
            $order['customer_email'] = $request->email;
            $order['customer_name'] = $request->name;
            $order['shipping_cost'] = $request->shipping_cost;
            $order['packing_cost'] = $request->packing_cost;
            $order['tax'] = $request->tax;
            $order['customer_phone'] = $request->phone;
            $order['order_number'] = str_random(4).time();
            $order['customer_address'] = $request->address;
            $order['customer_country'] = $request->customer_country;
            $order['customer_city'] = $request->city;
            $order['customer_zip'] = $request->zip;
            $order['shipping_email'] = $request->shipping_email;
            $order['shipping_name'] = $request->shipping_name;
            $order['shipping_phone'] = $request->shipping_phone;
            $order['shipping_address'] = $request->shipping_address;
            $order['shipping_country'] = $request->shipping_country;
            $order['shipping_city'] = $request->shipping_city;
            $order['shipping_zip'] = $request->shipping_zip;
            $order['order_note'] = $request->order_notes;
            $order['coupon_code'] = $request->coupon_code;
            $order['coupon_discount'] = $request->coupon_discount;
            $order['dp'] = $request->dp;
            $order['payment_status'] = "Pending";
            $order['currency_sign'] = $curr->sign;
            $order['currency_value'] = $curr->value;
            $order['payment_status'] = "Completed";
            $order['txnid'] = $request->ref_id;
            $order['dp'] = $request->dp;
            $order['vendor_shipping_id'] = $request->vendor_shipping_id;
            $order['vendor_packing_id'] = $request->vendor_packing_id;
        
            if($order['dp'] == 1){
                $order['status'] = 'completed';
            }

            if (Session::has('affilate')) {
                $val = $request->total / $curr->value;
                $val = $val / 100;
                $sub = $val * $gs->affilate_charge;
                $order['affilate_user'] = Session::get('affilate');
                $order['affilate_charge'] = $sub;
            }

            $order->save();

            if($order->dp == 1){
                $track = new OrderTrack;
                $track->title = 'Completed';
                $track->text = 'Your order has completed successfully.';
                $track->order_id = $order->id;
                $track->save();
            }else {
                $track = new OrderTrack;
                $track->title = 'Pending';
                $track->text = 'You have successfully placed your order.';
                $track->order_id = $order->id;
                $track->save();
            }
        
            $notification = new Notification;
            $notification->order_id = $order->id;
            $notification->save();

            if($request->coupon_id != ""){
                $coupon = Coupon::findOrFail($request->coupon_id);
                $coupon->used++;
                if($coupon->times != null){
                    $i = (int)$coupon->times;
                    $i--;
                    $coupon->times = (string)$i;
                }
                $coupon->update();
            }

            foreach($cart->items as $prod){
                $x = (string)$prod['size_qty'];
                if(!empty($x)){
                    $product = Product::findOrFail($prod['item']['id']);
                    $x = (int)$x;
                    $x = $x - $prod['qty'];
                    $temp = $product->size_qty;
                    $temp[$prod['size_key']] = $x;
                    $temp1 = implode(',', $temp);
                    $product->size_qty =  $temp1;
                    $product->update();               
                }
            }

            foreach($cart->items as $prod){
                $x = (string)$prod['stock'];
                if($x != null){
                    $product = Product::findOrFail($prod['item']['id']);
                    $product->stock =  $prod['stock'];
                    $product->update();  
                    
                    if($product->stock <= 5){
                        $notification = new Notification;
                        $notification->product_id = $product->id;
                        $notification->save();                    
                    }              
                
                }
            }

            $notf = null;

            foreach($cart->items as $prod){
                if($prod['item']['user_id'] != 0){
                    $vorder =  new VendorOrder;
                    $vorder->order_id = $order->id;
                    $vorder->user_id = $prod['item']['user_id'];
                    $notf[] = $prod['item']['user_id'];
                    $vorder->qty = $prod['qty'];
                    $vorder->price = $prod['price'];
                    $vorder->ship_fee = $prod['item']['ship_fee'];
                    $vorder->order_number = $order->order_number;             
                    $vorder->save();
                }

            }

            if(!empty($notf)){
                $users = array_unique($notf);
                foreach ($users as $user) {
                    $notification = new UserNotification;
                    $notification->user_id = $user;
                    $notification->order_number = $order->order_number;
                    $notification->save();    
                }
            }

            Session::put('temporder',$order);
            Session::put('tempcart',$cart);
            Session::put('tempcart',$cart);
            Session::put('orderNo',$order['order_number']);
            Session::forget('cart');
            Session::forget('already');
            Session::forget('coupon');
            Session::forget('coupon_total');
            Session::forget('coupon_total1');
            Session::forget('coupon_percentage');
            
            //Sending Email To Buyer
            // $to = $request->email;
            // $subject = 'Your Order Placed!!!';
            // $msg_template = 
            //     "
            //         <div style='width: 50%; margin:0px auto; border:2px solid #eee; text-align:left; padding: 2% 4%; line-height: 1.6;'>
            //             <div style='padding-bottom:1%;'>
            //                 <center><img style='width:20%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
            //             </div>
            //             <div style='border-top:2px solid #df7f1b; padding-bottom:1%;'>
            //                 <center><img style='width:35%;' src='https://kahioja.com/assets/images/thank-you.jpg' alt='Kahioja Image'></center>
            //             </div>
            //             <div>
            //                 Hey ".$request->name.",
            //             </div>
            //             <div>
            //                 <p>
            //                     You have placed a new order. Your order number is ".$order->order_number.". Please wait for your delivery;
            //                 </p>
            //             </div>
            //             <div>
            //                 Happy Shopping,<br>
            //                 Your KAHIOJA Team
            //             </div>
            //             <div style='border-top:2px solid #000; margin-top:1%; padding:2%;'>
            //                 <center><img style='width:15%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
            //             </div>
            //             <div style='text-align:center; font-size:0.8rem; line-height:1.3;'>
            //                 A1/A2 Block A Hamisu Abba Sumaila Plaza Tarauni Kano, Nigeria | <a style='color:#df7f1b;'>info@kahioja.com</a><br>
            //                 You've received this email because you have an account with KAHIOJA.
            //             </div>
            //         </div>
            //     "; 
            
            // $msg = $msg_template;

            // if($gs->is_smtp == 1){
            //     $data = [
            //         'to' => $to,
            //         'subject' => $subject,
            //         'body' => $msg,
            //     ];
            //     $mailer = new GeniusMailer();
            //     $mailer->sendCustomMail($data);
                
                // $data = [
                //     'to' => $request->email,
                //     'type' => "new_order",
                //     'cname' => $request->name,
                //     'oamount' => "",
                //     'aname' => "",
                //     'aemail' => "",
                //     'wtitle' => "",
                //     'onumber' => $order->order_number,
                // ];
                // $mailer->sendAutoOrderMail($data,$order->id);            
            // }else{
            //     $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
            //     mail($to,$subject,$msg,$headers);            
            // }
            
            //Sending Email To Admin
            // if($gs->is_smtp == 1){
            //     $data = [
            //         'to' => Pagesetting::find(1)->contact_email,
            //         'subject' => "New Order Recieved!!",
            //         'body' => "
            //                 <div style='width: 50%; margin:0px auto; border:2px solid #eee; text-align:left; padding: 2% 4%; line-height: 1.6;'>
            //                     <div style='padding-bottom:1%;'>
            //                         <center><img style='width:20%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
            //                     </div>
            //                     <div style='border-top:2px solid #df7f1b; padding-bottom:1%;'>
            //                         <center><img style='width:35%;' src='https://kahioja.com/assets/images/thank-you.jpg' alt='Kahioja Image'></center>
            //                     </div>
            //                     <div>
            //                         Hey Admin,
            //                     </div>
            //                     <div>
            //                         <p>
            //                             Your store has received a new order.<br>Order Number is ".$order->order_number.". Please login to your panel to check. <br>Thank you.
            //                         </p>
            //                     </div>
            //                     <div>
            //                         Happy Shopping,<br>
            //                         Your KAHIOJA Team
            //                     </div>
            //                     <div style='border-top:2px solid #000; margin-top:1%; padding:2%;'>
            //                         <center><img style='width:15%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
            //                     </div>
            //                     <div style='text-align:center; font-size:0.8rem; line-height:1.3;'>
            //                         A1/A2 Block A Hamisu Abba Sumaila Plaza Tarauni Kano, Nigeria | <a style='color:#df7f1b;'>info@kahioja.com</a><br>
            //                         You've received this email because you have an account with KAHIOJA.
            //                     </div>
            //                 </div>",
            //     ];
                
            //     $mailer = new GeniusMailer();
            //     $mailer->sendCustomMail($data);            
            // }else{
            //     $to = Pagesetting::find(1)->contact_email;
            //     $subject = "New Order Recieved!!";
            //     $msg = 
            //     "
            //         <div style='width: 50%; margin:0px auto; border:2px solid #eee; text-align:left; padding: 2% 4%; line-height: 1.6;'>
            //             <div style='padding-bottom:1%;'>
            //                 <center><img style='width:20%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
            //             </div>
            //             <div style='border-top:2px solid #df7f1b; padding-bottom:1%;'>
            //                 <center><img style='width:35%;' src='https://kahioja.com/assets/images/thank-you.jpg' alt='Kahioja Image'></center>
            //             </div>
            //             <div>
            //                 Hey Admin,
            //             </div>
            //             <div>
            //                 <p>
            //                     Your store has received a new order.<br>Order Number is ".$order->order_number.". Please login to your panel to check. <br>Thank you.
            //                 </p>
            //             </div>
            //             <div>
            //                 Happy Shopping,<br>
            //                 Your KAHIOJA Team
            //             </div>
            //             <div style='border-top:2px solid #000; margin-top:1%; padding:2%;'>
            //                 <center><img style='width:15%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
            //             </div>
            //             <div style='text-align:center; font-size:0.8rem; line-height:1.3;'>
            //                 A1/A2 Block A Hamisu Abba Sumaila Plaza Tarauni Kano, Nigeria | <a style='color:#df7f1b;'>info@kahioja.com</a><br>
            //                 You've received this email because you have an account with KAHIOJA.
            //             </div>
            //         </div>
            //     ";
            //     $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
            //     mail($to,$subject,$msg,$headers);
            // }

            return redirect($payment['data']['link']);
        
        }catch(Exception $e){
            print('Error: ' . $e->getMessage());
        }

    }    
    /**
     * Obtain Rave callback information
     * @return void
     */
    public function callback(Request $request)
    {
        $status = request()->status;

        //if payment is successful
        if ($status ==  'successful') {
        
            $transactionID = Flutterwave::getTransactionIDFromCallback();
            $data = Flutterwave::verifyTransaction($transactionID);
            
            $success_url = action('Front\PaymentController@payreturn');
            
            $transactID = $data['data']['id'];
            $tx_ref = $data['data']['tx_ref'];
            $amount = $data['data']['amount'];
            $charge_fee = $data['data']['app_fee'];
            $amount_paid = $amount + $charge_fee;
            $currency = $data['data']['currency'];
            $date = date("D M j Y G:i:s",  strtotime($data['data']['created_at'])  + 1 * 3600);
            
            $payment_type = $data['data']['payment_type'];
            
            if($payment_type == 'card'){
                $last_4digits = $data['data']['card']['last_4digits'];
                $card_type = $data['data']['card']['type'];
                $card_details = $payment_type.'-'.$card_type.'-'.$last_4digits;
            }else{
                $card_details = $payment_type;
            }
            
            $customer_email = $data['data']['customer']['email'];
            $customer_phone = $data['data']['customer']['phone_number'];
            
            
            Session::put('transactID', $transactID);
            Session::put('payment_type', $payment_type);
            Session::put('customer_email', $customer_email);
            Session::put('customer_phone', $customer_phone);
            
            $orderNo = Session::get('orderNo');

            $order = Order::where('order_number', $orderNo)->update([
                    'method'=> $card_details,
                    'txnid'=> $transactID]);
            
            $gs = Generalsetting::findOrFail(1);

            $to = $customer_email;
            $subject = 'Payment Receipt from Kahioja';
            $msg_template = 
                            "
                            <div style='width: 50%; margin:0px auto; border:2px solid #eee; text-align:left; padding: 2% 4%; line-height: 1.6;'>
                                <div style='padding-bottom:1%;'>
                                    <center><img style='width:20%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
                                </div>
                                <!-- <div style='border-top:2px solid #df7f1b; padding-bottom:1%;'>
                                    <center><img style='width:35%;' src='https://kahioja.com/assets/images/thank-you.jpg' alt='Kahioja Image'></center>
                                </div> -->
                                <div style='text-align:center; font-size:1.3rem; font-weight:bold;'>
                                    Receipt from KAHIOJA
                                </div>
                                <div style='text-align:center;'>
                                    Order Number #".$orderNo."<br>
                                    Receipt #".$tx_ref."<br>
                                </div>
                                <div style='text-align:center; font-size: 3rem; font-weight: 500;'>
                                    Total: ".$currency."".$amount."
                                </div>
                                <hr>
                                <div style='text-align:center;'>
                                    SUMMARY
                                </div>
                                <table style='border-radius:10px; width:70%; margin:2% auto; padding: 2% 2%; font-size: 0.9rem; background-color: #eee;'>
                                    <tr style='text-align:left;'>
                                        <th>Amount Paid</th>
                                        <td style='padding: 15px; text-align: right;'>".$currency."".$amount_paid."</td>
                                    <tr>
                                    <tr style='text-align:left;'>
                                        <th>Applicable Fees</th>
                                        <td style=' padding: 15px; text-align: right;'>".$currency."".$charge_fee."</td>
                                    <tr>
                                    <tr style='text-align:left;'>
                                        <th>Payment Method</th>
                                        <td style=' padding: 15px; text-align: right;'>".$payment_type."</td>
                                    <tr>
                                    <tr style='text-align:left;'>
                                        <th>Transaction Reference</th>
                                        <td style=' padding: 15px; text-align: right;'>".$transactID."</td>
                                    <tr>
                                </table>
                                <div style='text-transform: uppercase; text-align:center;'>
                                    ".$date."
                                </div>
                                <div>
                                    Your KAHIOJA Team
                                </div>
                                <div style='border-top:2px solid #000; margin-top:1%; padding:2%;'>
                                    <center><img style='width:15%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
                                </div>
                                <div style='text-align:center; font-size:11px; line-height:1.3;'>
                                    A1/A2 Block A Hamisu Abba Sumaila Plaza Tarauni Kano, Nigeria | <a style='color:#df7f1b;'>info@kahioja.com</a><br>
                                    You've received this email because you have an account with KAHIOJA.
                                </div>
                            </div>
                            ";
            
            $msg = $msg_template;
            //Sending Email To Customer
            if($gs->is_smtp == 1){
                $data = [
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $msg,
                ];

                $mailer = new GeniusMailer();
                $mailer->sendCustomMail($data);
            }else{
                $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
                mail($to,$subject,$msg,$headers);
            }

            return redirect($success_url);
        
        }elseif ($status ==  'cancelled'){
            return redirect()->route('front.checkout')->with('unsuccess','Payment Cancelled');
        }else{
            return redirect()->route('front.checkout')->with('unsuccess','Payment Failed. Please try again later');
        }

        // Get the transaction from your DB using the transaction reference (txref)
        // Check if you have previously given value for the transaction. If you have, redirect to your successpage else, continue
        // Confirm that the currency on your db transaction is equal to the returned currency
        // Confirm that the db transaction amount is equal to the returned amount
        // Update the db transaction record (including parameters that didn't exist before the transaction is completed. for audit purpose)
        // Give value for the transaction
        // Update the transaction to note that you have given value for the transaction
        // You can also redirect to your success page from here

    }
    
}
