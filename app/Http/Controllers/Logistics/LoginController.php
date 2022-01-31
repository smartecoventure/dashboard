<?php

namespace App\Http\Controllers\Logistics;

use App\Classes\GeniusMailer;
use App\Models\Generalsetting;
use App\Models\Logistic;
use Illuminate\Support\Facades\Hash;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Auth;
use Illuminate\Support\Facades\Input;
use Validator;


class LoginController extends Controller
{
    public function __construct()
    {
      $this->middleware('guest:logistics', ['except' => ['logout']]);
    }

    public function showLoginForm()
    {
      return view('logistics.login');
    }

    public function login(Request $request)
    {
        //--- Validation Section
        $rules = [
                  'email'   => 'required|email',
                  'password' => 'required'
                ];

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

      // Attempt to log the user in
      if (Auth::guard('logistics')->attempt(['email' => $request->email, 'password' => $request->password], $request->remember)) {
        // if successful, then redirect to their intended location
        return response()->json(route('logistics.dashboard'));
      }

      // if unsuccessful, then redirect back to the login with the form data
          return response()->json(array('errors' => [ 0 => 'Credentials Doesn\'t Match !' ]));     
    }

    public function showForgotForm()
    {
      return view('logistics.forgot');
    }

    public function forgot(Request $request)
    {
      $gs = Generalsetting::findOrFail(1);
      $input =  $request->all();
      if (Logistic::where('email', '=', $request->email)->count() > 0) {
      // user found
      $logistics = Logistic::where('email', '=', $request->email)->firstOrFail();
      $token = md5(time().$logistics->name.$logistics->email);

      $file = fopen(public_path().'/project/storage/tokens/'.$token.'.data','w+');
      fwrite($file,$logistics->id);
      fclose($file);

      $subject = "Reset Password Request";
      $msg = "Please click this link : ".'<a href="'.route('logistics.change.token',$token).'">'.route('logistics.change.token',$token).'</a>'.' to change your password.';
      if($gs->is_smtp == 1)
      {
          $data = [
                  'to' => $request->email,
                  'subject' => $subject,
                  'body' => $msg,
          ];

          $mailer = new GeniusMailer();
          $mailer->sendCustomMail($data);                
      }
      else
      {
          $headers = "From: ".$gs->from_name."<".$gs->from_email.">";
          mail($request->email,$subject,$msg,$headers);            
      }
      return response()->json('Verification Link Sent Successfully!. Please Check your email.');
      }
      else{
      // user not found
      return response()->json(array('errors' => [ 0 => 'No Account Found With This Email.' ]));    
      }  
    }

    public function showChangePassForm($token)
    {
      if (file_exists(public_path().'/project/storage/tokens/'.$token.'.data')){
        $id = file_get_contents(public_path().'/project/storage/tokens/'.$token.'.data');
        return view('logistics.changepass',compact('id','token'));  
      }
    }

    public function changepass(Request $request)
    {
        $id = $request->logistics_id;
        $logistics =  Logistic::findOrFail($id);
        $token = $request->file_token;
        if ($request->cpass){
            if (Hash::check($request->cpass, $logistics->password)){
                if ($request->newpass == $request->renewpass){
                    $input['password'] = Hash::make($request->newpass);
                }else{
                    return response()->json(array('errors' => [ 0 => 'Confirm password does not match.' ]));
                }
            }else{
                return response()->json(array('errors' => [ 0 => 'Current password Does not match.' ]));
            }
        }
        $logistics->update($input);

        unlink(public_path().'/project/storage/tokens/'.$token.'.data');

        $msg = 'Successfully changed your password.<a href="'.route('logistics.login').'"> Login Now</a>';
        return response()->json($msg);
    }


    public function logout()
    {
        Auth::guard('logistics')->logout();
        return redirect('/');
    }
}
