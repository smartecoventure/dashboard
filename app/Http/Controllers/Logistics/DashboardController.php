<?php

namespace App\Http\Controllers\Logistics;

use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use InvalidArgumentException;
use Validator;
use Illuminate\Support\Facades\Hash;
use App\Models\LogisticsPickup;
use App\Models\LogisticsDelivery;

use DB;

class DashboardController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:logistics');
    }

    public function index()
    {
        $logistics_id = Auth::guard('logistics')->user()->id;
        
        $delivery = LogisticsDelivery::where('delivery_status','=',3)->where('logistics_id','=',$logistics_id)->get();
        $data = Auth::guard('logistics')->user();

        return view('logistics.dashboard',compact('delivery','data','logistics_id'));
    }

    public function profile()
    {
        $data = Auth::guard('logistics')->user();
        return view('logistics.profile',compact('data'));
    }

    public function profileupdate(Request $request)
    {
        //--- Validation Section

        $rules =
        [
            'photo' => 'mimes:jpeg,jpg,png,svg',
            'email' => 'unique:admins,email,'.Auth::guard('logistics')->user()->id
        ];


        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends
        $input = $request->all();
        $data = Auth::guard('logistics')->user();
            if ($file = $request->file('photo'))
            {
                $name = time().str_replace(' ', '', $file->getClientOriginalName());
                $file->move('assets/images/logistics/',$name);
                if($data->photo != null)
                {
                    if (file_exists(public_path().'/assets/images/logistics/'.$data->photo)) {
                        unlink(public_path().'/assets/images/logistics/'.$data->photo);
                    }
                }
            $input['photo'] = $name;
            }
        $data->update($input);
        $msg = 'Successfully updated your profile';
        return response()->json($msg);
    }

    public function passwordreset()
    {
        $data = Auth::guard('logistics')->user();
        return view('logistics.password',compact('data'));
    }

    public function changepass(Request $request)
    {
        $logistics = Auth::guard('logistics')->user();
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
        $msg = 'Successfully change your passwprd';
        return response()->json($msg);
    }

}
