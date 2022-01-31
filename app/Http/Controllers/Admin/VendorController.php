<?php

namespace App\Http\Controllers\Admin;

use Datatables;
use App\Classes\GeniusMailer;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Generalsetting;
use App\Models\Withdraw;
use App\Models\Currency;
use App\Models\Verification;
use App\Models\UserSubscription;
use Illuminate\Support\Facades\Input;
use Validator;
use Auth;
use DB;


class VendorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

	    //*** JSON Request
	    public function datatables()
	    {
	        $datas = User::where('is_vendor','=',2)->orWhere('is_vendor','=',1)->orderBy('id','desc')->get();
	         //--- Integrating This Collection Into Datatables
	         return Datatables::of($datas)
                                ->addColumn('status', function(User $data) {
                                    $class = $data->is_vendor == 2 ? 'drop-success' : 'drop-danger';
                                    $s = $data->is_vendor == 2 ? 'selected' : '';
                                    $ns = $data->is_vendor == 1 ? 'selected' : '';
                                    return '<div class="action-list"><select class="process select vendor-droplinks '.$class.'">'.
                '<option value="'. route('admin-vendor-st',['id1' => $data->id, 'id2' => 2]).'" '.$s.'>Activated</option>'.
                '<option value="'. route('admin-vendor-st',['id1' => $data->id, 'id2' => 1]).'" '.$ns.'>Deactivated</option></select></div>';
                                }) 
	                            ->addColumn('action', function(User $data) {
	                                return '<div class="godropdown"><button class="go-dropdown-toggle"> Actions<i class="fas fa-chevron-down"></i></button><div class="action-list"><a href="' . route('admin-vendor-secret',$data->id) . '" > <i class="fas fa-user"></i> Secret Login</a><a href="javascript:;" data-href="' . route('admin-vendor-verify',$data->id) . '" class="verify" data-toggle="modal" data-target="#verify-modal"> <i class="fas fa-question"></i> Ask For Verification</a><a href="' . route('admin-vendor-show',$data->id) . '" > <i class="fas fa-eye"></i> Details</a><a href="' . route('admin-vendor-edit',$data->id) . '"> <i class="fas fa-edit"></i> Edit</a><a href="javascript:;" class="send" data-email="'. $data->email .'" data-toggle="modal" data-target="#vendorform"><i class="fas fa-envelope"></i> Send Email</a><a href="javascript:;" data-href="' . route('admin-vendor-delete',$data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i> Delete</a></div></div>';
	                            }) 
	                            ->rawColumns(['status','action'])
	                            ->toJson(); //--- Returning Json Data To Client Side
	    }

	//*** GET Request
    public function index()
    {
        return view('admin.vendor.index');
    }

    //*** GET Request
    public function color()
    {
        return view('admin.generalsetting.vendor_color');
    }


    //*** GET Request
    public function subsdatatables()
    {
         $datas = UserSubscription::where('status','=',1)->orderBy('id','desc')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('name', function(UserSubscription $data) {
                                $name = isset($data->user->owner_name) ? $data->user->owner_name : 'Removed';
                                return  $name;
                            })

                            ->editColumn('txnid', function(UserSubscription $data) {
                                $txnid = $data->txnid == null ? 'Free' : $data->txnid;
                                return $txnid;
                            }) 
                            ->editColumn('created_at', function(UserSubscription $data) {
                                $date = $data->created_at->diffForHumans();
                                return $date;
                            }) 
                            ->addColumn('action', function(UserSubscription $data) {
                                return '<div class="action-list"><a data-href="' . route('admin-vendor-sub',$data->id) . '" class="view details-width" data-toggle="modal" data-target="#modal1"> <i class="fas fa-eye"></i>Details</a></div>';
                            }) 
                            ->rawColumns(['action'])
                            ->toJson(); //--- Returning Json Data To Client Side


    }


	//*** GET Request
    public function subs()
    {

        return view('admin.vendor.subscriptions');
    }

	//*** GET Request
    public function sub($id)
    {
        $subs = UserSubscription::findOrFail($id);
        return view('admin.vendor.subscription-details',compact('subs'));
    }

	//*** GET Request
  	public function status($id1,$id2)
    {
        $user = User::findOrFail($id1);
        $user->is_vendor = $id2;
        $user->update();
        //--- Redirect Section        
        $msg[0] = 'Status Updated Successfully.';
        return response()->json($msg);      
        //--- Redirect Section Ends    

    }

	//*** GET Request
    public function edit($id)
    {
        $data = User::findOrFail($id);
        return view('admin.vendor.edit',compact('data'));
    }



	//*** GET Request
    public function verify($id)
    {
        $data = User::findOrFail($id);
        return view('admin.vendor.verification',compact('data'));
    }

	//*** POST Request
    public function verifySubmit(Request $request, $id)
    {
        // $verification_id = Verification::where('user_id', '=', $id)->get();
        
        $gs = Generalsetting::findOrFail(1);
        $user = User::findOrFail($id);
        $user->verifies()->create(['admin_warning' => 1, 'warning_reason' => $request->details]);
        
        $to = $user->email;
        $subject = 'Request for verification';
        // $details = $request->details;
        
        $msg_template = '<div style="width: 50%; margin:0px auto; border:2px solid #eee; text-align:center; padding: 2% 3%; font-size: 1.5rem; line-height: 1.3;">
                    	    <div>
                                <img style="width:50%;" src="https://kahioja.com/assets/images/kahioja_full.jpg" alt="Kahioja Image">
                    	    </div>
                    	    <hr>
                    	    <p>
                    	        Please verify your Account. <br>
                    	        Kindly login to your dashboard to verify your Account<br>
                    	        Thank you!
                    	    </p>
                    	</div>';
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
        

        $success_msg = 'Verification Request Sent Successfully.';
        return response()->json($success_msg);   
    }


	//*** POST Request
    public function update(Request $request, $id)
    {
	    //--- Validation Section
	        $rules = [
                'shop_name'   => 'unique:users,shop_name,'.$id,
                 ];
            $customs = [
                'shop_name.unique' => 'Shop Name "'.$request->shop_name.'" has already been taken. Please choose another name.'
            ];

         $validator = Validator::make($request->all(), $rules,$customs);
         
         if ($validator->fails()) {
           return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
         }
         //--- Validation Section Ends

        $user = User::findOrFail($id);
        $data = $request->all();
        $user->update($data);
        $msg = 'Vendor Information Updated Successfully.'.'<a href="'.route("admin-vendor-index").'">View Vendor Lists</a>';
        return response()->json($msg);   
    }

	//*** GET Request
    public function show($id)
    {
        $data = User::findOrFail($id);
        return view('admin.vendor.show',compact('data'));
    }
    
    public function sendmail(Request $request){
        
        $gs = Generalsetting::findOrFail(1);
        
        $to = $request->to;
        $subject = $request->subject;
        $message = $request->message;
        $msg_template = '<div style="width: 50%; margin:0px auto; border:2px solid #eee; text-align:center; padding: 2% 3%; font-size: 1.5rem; line-height: 1.3;">
                    	    <div>
                                <img style="width:50%;" src="https://kahioja.com/assets/images/kahioja_full.jpg" alt="Kahioja Image">
                    	    </div>
                    	    <hr>
                    	    <p>
                    	       '.$message.'
                    	    </p>
                    	</div>';
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
            return redirect()->back()->with('success', 'Message sent');
	        
        }else{
            $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
            mail($to,$subject,$msg,$headers);
            return redirect()->back()->with('success', 'Message sent');
        }
    }
    

    //*** GET Request
    public function secret($id)
    {
        Auth::guard('web')->logout();
        $data = User::findOrFail($id);
        Auth::guard('web')->login($data); 
        return redirect()->route('vendor-dashboard');
    }
    

	//*** GET Request
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->is_vendor = 0;
            $user->is_vendor = 0;
            $user->shop_name = null;
            $user->shop_details= null;
            $user->owner_name = null;
            $user->shop_number = null;
            $user->shop_address = null;
            $user->reg_number = null;
            $user->shop_message = null;
        $user->update();
        if($user->notivications->count() > 0)
        {
            foreach ($user->notivications as $gal) {
                $gal->delete();
            }
        }
            //--- Redirect Section     
            $msg = 'Vendor Deleted Successfully.';
            return response()->json($msg);      
            //--- Redirect Section Ends    
    }

        //*** JSON Request
        public function withdrawdatatables()
        {
             $datas = Withdraw::where('type','=','vendor')->orderBy('id','desc')->get();
             //--- Integrating This Collection Into Datatables
             return Datatables::of($datas)
                                // ->addColumn('name', function(Withdraw $data) {
                                //     $name = $data->user->name;
                                //     return '<a href="' . route('admin-vendor-show', $data->user->id) . '" target="_blank">'. $name .'</a>';
                                // }) 
                                ->addColumn('email', function(Withdraw $data) {
                                    $email = $data->user->email;
                                    return $email;
                                }) 
                                ->addColumn('phone', function(Withdraw $data) {
                                    $phone = $data->user->phone;
                                    return $phone;
                                }) 
                                ->editColumn('status', function(Withdraw $data) {
                                    $status = ucfirst($data->status);
                                    return $status;
                                }) 
                                ->editColumn('amount', function(Withdraw $data) {
                                    $sign = Currency::where('is_default','=',1)->first();
                                    //$amount = $sign->sign.round($data->amount * $sign->value , 2);
                                    $amount = $sign->sign.''.($data->amount - $data->fee);
                                    return $amount;
                                })
                                ->editColumn('charge', function(Withdraw $data) {
                                    $sign = Currency::where('is_default','=',1)->first();
                                    //$amount = $sign->sign.round($data->amount * $sign->value , 2);
                                    $charge = $sign->sign.''.$data->fee;
                                    return $charge;
                                })
                                ->editColumn('bank_details', function(Withdraw $data) {
                                    $acc_name = $data->acc_name;
                                    $bank_name = $data->bank_name;
                                    $acc_no = $data->iban;
                                    $bank_details = $acc_name.' '.$bank_name.' '.$acc_no;
                                    return $bank_details;
                                })
                                ->editColumn('time_requested', function(Withdraw $data) {
                                    $date = date('H:m:s d-M-Y',strtotime($data->created_at));
                                    return $date;
                                })
                                ->addColumn('action', function(Withdraw $data) {
                                    $action = '<div class="action-list"><a data-href="' . route('admin-vendor-withdraw-show',$data->id) . '" class="view details-width" data-toggle="modal" data-target="#modal1"> <i class="fas fa-eye"></i> Details</a>';
                                    if($data->status == "pending") {
                                    $action .= '<a data-href="' . route('admin-vendor-withdraw-accept',$data->id) . '" data-toggle="modal" data-target="#confirm-delete"> <i class="fas fa-check"></i> Accept</a><a data-href="' . route('admin-vendor-withdraw-reject',$data->id) . '" data-toggle="modal" data-target="#confirm-delete1"> <i class="fas fa-trash-alt"></i> Reject</a>';
                                    }
                                    $action .= '</div>';
                                    return $action;
                                }) 
                                ->rawColumns(['name','action'])
                                ->toJson(); //--- Returning Json Data To Client Side
        }

        //*** GET Request
        public function withdraws()
        {
            return view('admin.vendor.withdraws');
        }

        //*** GET Request       
        public function withdrawdetails($id)
        {
            $sign = Currency::where('is_default','=',1)->first();
            $withdraw = Withdraw::findOrFail($id);
            return view('admin.vendor.withdraw-details',compact('withdraw','sign'));
        }

        //*** GET Request   
        public function accept($id)
        {
            $withdraw = Withdraw::findOrFail($id);
            $data['status'] = "completed";
            $withdraw->update($data);

            $account = User::findOrFail($withdraw->user->id);

            //Sending email to Vendor
            $gs = Generalsetting::findOrFail(1);
            $sign = Currency::where('is_default','=',1)->first();
            $date = date("D M j Y G:i:s",  strtotime($withdraw->updated_at)  + 1 * 3600);
            
            $to = $account->email;
            $subject = 'Withdraw Request Approved from Kahioja';
            $msg_template = 
                            "
                            <div style='width: 50%; margin:0px auto; border:2px solid #eee; text-align:left; padding: 2% 4%; line-height: 1.6;'>
                                <div style='padding-bottom:1%;'>
                                    <center><img style='width:20%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
                                </div>
                                <div style='font-weight:bold; font-size:1.2rem;'>
                                    Withdraw Request Approved from KAHIOJA
                                </div>
                                <div>
                                    Congratulation! ".$account->name." Your withdraw request of ".$sign->sign."".$withdraw->amount." has been Approved
                                </div>
                                <hr>
                                <div style='text-align:center; font-weight:bold; font-size:1.2rem;'>
                                    ACCOUNT BALANCE
                                </div>
                                <hr>
                                <table style='border-radius:10px; width:70%; margin:2% auto; padding: 2% 2%; font-size: 0.9rem; background-color: #eee;'>
                                    <tr style='text-align:left;'>
                                        <th>Amount Requested</th>
                                        <td style='padding: 15px; text-align: right;'>".$sign->sign."".$withdraw->amount."</td>
                                    <tr>
                                    <tr style='text-align:left;'>
                                        <th>Applicable Fees</th>
                                        <td style=' padding: 15px; text-align: right;'>".$sign->sign."".$withdraw->fee."</td>
                                    <tr>
                                    <tr style='text-align:left;'>
                                        <th>Current Balance</th>
                                        <td style=' padding: 15px; text-align: right;'>".$sign->sign."".$account->current_balance."</td>
                                    <tr>
                                </table>
                                <div style='text-transform: uppercase; text-align:center;'>
                                    ".$date."
                                </div>
                                <div>
                                    Happy Shopping,<br>
                                    Your KAHIOJA Team
                                </div>
                                <div style='border-top:2px solid #000; margin-top:1%; padding:2%;'>
                                    <center><img style='width:15%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
                                </div>
                                <div style='text-align:center; font-size:0.8rem; line-height:1.3;'>
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
            
            //--- Redirect Section     
            $msg = 'Withdraw Accepted Successfully.';
            return response()->json($msg);      
            //--- Redirect Section Ends   
        }

        //*** GET Request   
        public function reject($id)
        {
            $withdraw = Withdraw::findOrFail($id);
            $account = User::findOrFail($withdraw->user->id);
            $account->affilate_income = $account->affilate_income + $withdraw->amount + $withdraw->fee;
            
            $account->current_balance = $account->current_balance + $withdraw->amount; 
            
            $account->update();
            $data['status'] = "rejected";
            $withdraw->update($data);
            
            //Sending email to Vendor
            $gs = Generalsetting::findOrFail(1);
            $sign = Currency::where('is_default','=',1)->first();
            $date = date("D M j Y G:i:s",  strtotime($withdraw->updated_at)  + 1 * 3600);
            
            $to = $account->email;
            $subject = 'Withdraw Request Rejected from Kahioja';
            $msg_template = 
                            "
                            <div style='width: 50%; margin:0px auto; border:2px solid #eee; text-align:left; padding: 2% 4%; line-height: 1.6;'>
                                <div style='padding-bottom:1%;'>
                                    <center><img style='width:20%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
                                </div>
                                <div style='font-weight:bold; font-size:1.2rem;'>
                                    Withdraw Request Rejected from KAHIOJA
                                </div>
                                <div>
                                    Sorry! ".$account->name." Your withdraw request of ".$sign->sign." ".$withdraw->amount." has been rejected for either of the following reasons:
                                </div>
                                <div>
                                    - Incomplete Account Details <br>
                                    - Unverified Account <br>
                                </div>
                                <hr>
                                <div style='text-align:center; font-weight:bold; font-size:1.2rem;'>
                                    ACCOUNT BALANCE
                                </div>
                                <hr>
                                <table style='border-radius:10px; width:70%; margin:2% auto; padding: 2% 2%; font-size: 0.9rem; background-color: #eee;'>
                                    <tr style='text-align:left;'>
                                        <th>Amount Requested</th>
                                        <td style='padding: 15px; text-align: right;'>".$sign->sign."".$withdraw->amount."</td>
                                    <tr>
                                    <tr style='text-align:left;'>
                                        <th>Applicable Fees</th>
                                        <td style=' padding: 15px; text-align: right;'>".$sign->sign."".$withdraw->fee."</td>
                                    <tr>
                                    <tr style='text-align:left;'>
                                        <th>Current Balance</th>
                                        <td style=' padding: 15px; text-align: right;'>".$sign->sign."".$account->current_balance."</td>
                                    <tr>
                                </table>
                                <div style='text-transform: uppercase; text-align:center;'>
                                    ".$date."
                                </div>
                                <div>
                                    Happy Shopping,<br>
                                    Your KAHIOJA Team
                                </div>
                                <div style='border-top:2px solid #000; margin-top:1%; padding:2%;'>
                                    <center><img style='width:15%;' src='https://kahioja.com/assets/images/1597338993KAHIOJA.png' alt='Kahioja Image'></center>
                                </div>
                                <div style='text-align:center; font-size:0.8rem; line-height:1.3;'>
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
            
            //--- Redirect Section     
            $msg = 'Withdraw Rejected Successfully.';
            return response()->json($msg);     
            //--- Redirect Section Ends   
        }

}
