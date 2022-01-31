<?php

namespace App\Http\Controllers\User;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Generalsetting;
use App\Models\User;
use App\Classes\GeniusMailer;
use App\Models\Notification;
use Auth;
use Illuminate\Support\Facades\Input;
use Validator;

class RegisterController extends Controller
{

    public function register(Request $request)
    {

    	$gs = Generalsetting::findOrFail(1);

    	if($gs->is_capcha == 1)
    	{
	        $value = session('captcha_string');
	        if ($request->codes != $value){
	            return response()->json(array('errors' => [ 0 => 'Please enter Correct Capcha Code.' ]));    
	        }    		
    	}


        //--- Validation Section

        $rules = [
		        'email'   => 'required|email|unique:users',
		        'password' => [
                        'required',
                        'confirmed',
                        'min:8',             // must be at least 8 characters in length
                        'max:16',             // must be at least 16 characters in length
                        // 'regex:/[a-z]/',       must contain at least one lowercase letter
                        // 'regex:/[A-Z]/',       must contain at least one uppercase letter
                        // 'regex:/[0-9]/',       must contain at least one digit
                    ],
                ];
        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

	        $user = new User;
	        $input = $request->all();        
	        $input['password'] = bcrypt($request['password']);
	        $token = md5(time().$request->name.$request->email);
	        $input['verification_link'] = $token;
	        $input['affilate_code'] = md5($request->name.$request->email);

	          if(!empty($request->vendor))
	          {
					//--- Validation Section
					$rules = [
						'shop_name' => 'unique:users',
				// 		'shop_number'  => 'max:10'
							];
					$customs = [
						'shop_name.unique' => 'This Shop Name has already been taken.',
				// 		'shop_number.max'  => 'Shop Number Must Be Less Then 10 Digit.'
					];

					$validator = Validator::make($request->all(), $rules, $customs);
					if ($validator->fails()) {
					return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
					}
					$input['is_vendor'] = 1;

			  }
			  
			$user->fill($input)->save();
	        if($gs->is_verification_email == 1){
    	        $to = $request->email;
    	        $subject = 'Verify your email address.';
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
							Thanks for signing up, ".$request->name.",
						</div>
						<div>
							<p>
								Please verify your email to get access to thousands of products to \"Buy and Sell.\" <br>
								<center>
									<button style='margin-top: 2%; background-color:orange; border:none; padding: 2% 5%; border-radius:25px;'>
										<a style='text-decoration:none; color:#fff; font-size: 1.2rem;' href=".url('user/register/verify/'.$token).">
											Verify Email now
										</a>
									</button>
							</center>
							</p>
							<p>
								Or <br> <a target='_blank' style='font-size: 1.5rem;' href=".url('user/register/verify/'.$token).">Click on this link to verify your email address</a>
							</p>
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
          	return response()->json('We need to verify your email address. We have sent an email to '.$to.' to verify your email address. Please click link in that email to continue.');
	        }else{
                $user->email_verified = 'Yes';
                $user->update();
    	        $notification = new Notification;
    	        $notification->user_id = $user->id;
    	        $notification->save();
                Auth::guard('web')->login($user); 
              	return response()->json(1);
	        }

    }

    public function token($token)
    {
        $gs = Generalsetting::findOrFail(1);

        if($gs->is_verification_email == 1)
	        {    	
        $user = User::where('verification_link','=',$token)->first();
        if(isset($user))
        {
            $user->email_verified = 'Yes';
            $user->update();
	        $notification = new Notification;
	        $notification->user_id = $user->id;
	        $notification->save();
            Auth::guard('web')->login($user); 
            return redirect()->route('user-dashboard')->with('success','Email Verified Successfully');
        }
    		}
    		else {
    		return redirect()->back();	
    		}
    }
}