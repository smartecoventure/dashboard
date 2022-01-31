<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Classes\GeniusMailer;
use App\Models\Generalsetting;
use App\Models\Subscription;
use App\Models\User;
use App\Models\UserSubscription;
use Auth;
use Carbon\Carbon;
use Config;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Input;
use Validator;


class PaystackController extends Controller
{
    public function check(Request $request){

        //--- Validation Section
        $rules = [
               'shop_name'   => 'unique:users',
                ];
        $customs = [
               'shop_name.unique' => 'This shop name has already been taken.'
                   ];
        $validator = Validator::make($request->all(), $rules, $customs);
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends
        return response()->json('success');
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'shop_name'   => 'unique:users',
           ],[ 
               'shop_name.unique' => 'This shop name has already been taken.'
            ]);
        $user = Auth::user();
        $package = $user->subscribes()->where('status',1)->orderBy('id','desc')->first();
        $subs = Subscription::findOrFail($request->subs_id);
        $settings = Generalsetting::findOrFail(1);
        $success_url = action('User\UserController@index');
        $item_name = $subs->title." Plan";
        $item_number = str_random(4).time();
        $item_amount = $subs->price;
        $item_currency = $subs->currency_code;


        $today = Carbon::now()->format('Y-m-d');
        $date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
        $input = $request->all();  
        $user->is_vendor = 2;
        if(!empty($package)){
            if($package->subscription_id == $request->subs_id)
            {
                $newday = strtotime($today);
                $lastday = strtotime($user->date);
                $secs = $lastday-$newday;
                $days = $secs / 86400;
                $total = $days+$subs->days;
                $user->date = date('Y-m-d', strtotime($today.' + '.$total.' days'));
            }else{
                $user->date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
            }
        }else{
            $user->date = date('Y-m-d', strtotime($today.' + '.$subs->days.' days'));
        }

        $user->mail_sent = 1;     
        $user->update($input);
        $sub = new UserSubscription;
        $sub->user_id = $user->id;
        $sub->subscription_id = $subs->id;
        $sub->title = $subs->title;
        $sub->currency = $subs->currency;
        $sub->currency_code = $subs->currency_code;
        $sub->price = $subs->price;
        $sub->days = $subs->days;
        $sub->allowed_products = $subs->allowed_products;
        $sub->details = $subs->details;
        $sub->method = 'Paystack';
        $sub->txnid = $request->ref_id;

        $sub->status = 1;
        $sub->save();
                    
        $gs = Generalsetting::findOrFail(1);

        $to = $user->email;
        $subject = $sub->title.' Subscription Activated Successful';
        $msg_template = 
                        "
                        <div style='width: 50%; margin:0px auto; border:2px solid #eee; text-align:left; padding: 2% 4%; line-height: 1.6;'>
                            <div style='padding-bottom:1%;'>
                                <center><img style='width:20%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
                            </div>
                            <div style='border-top:2px solid #df7f1b; padding-bottom:1%;'>
                                <center><img style='width:35%;' src='https://kahioja.com/assets/images/thank-you.jpg' alt='Kahioja Image'></center>
                            </div>
                            <div>
                                Hey ".$user->name.",
                            </div>
                            <div>
                                <p>
                                    Your have successfully activated <b>".$sub->title."</b> on your Vendor Account!<br>
                                    Please <a style='color:#df7f1b; text-decoration:none;' href='https://kahioja.com/user/login'>Login</a> to your account and build your own shop
                                </p>
                            </div>
                            <div style='text-align:center;'>
                                Subcription Receipt
                            </div>
                            <table style='border-radius:10px; width:70%; margin:2% auto; padding: 2% 2%; font-size: 0.9rem; background-color: #eee;'>
                                <tr style='text-align:left;'>
                                    <th>Subcription Type</th>
                                    <td style='padding: 15px; text-align: right;'>".$sub->title."</td>
                                <tr>
                                <tr style='text-align:left;'>
                                    <th>Amount Paid</th>
                                    <td style='padding: 15px; text-align: right;'>".$sub->price."</td>
                                <tr>
                                <tr style='text-align:left;'>
                                    <th>Payment Method</th>
                                    <td style='padding: 15px; text-align: right;'>Paystack</td>
                                <tr>
                                <tr style='text-align:left;'>
                                    <th>Transaction Reference</th>
                                    <td style=' padding: 15px; text-align: right;'>".$sub->txnid."</td>
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

        return redirect()->route('user-dashboard')->with('success','Vendor Account Activated Successfully');
    }       

}
