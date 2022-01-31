<?php

namespace App\Http\Controllers\Admin;

use Datatables;
use App\Classes\GeniusMailer;
use App\Models\Admin;
use App\Models\Withdraw;
use App\Models\Logistic;
use App\Models\Currency;
use App\Models\Generalsetting;
use App\Models\User;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator;
use DB;


class LogisticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    //*** JSON Request
    public function datatables()
    {
        $datas = Logistic::orderBy('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('action', function(Logistic $data) {
                                $delete ='<a href="javascript:;" data-href="' . route('admin-logistics-delete',$data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
                                return '<div class="action-list"><a data-href="' . route('admin-logistics-show',$data->id) . '" class="view details-width" data-toggle="modal" data-target="#modal1"> <i class="fas fa-eye"></i>Details</a><a data-href="' . route('admin-logistics-edit',$data->id) . '" class="edit" data-toggle="modal" data-target="#modal1"> <i class="fas fa-edit"></i>Edit</a>'.$delete.'</div>';
                            }) 
                            ->rawColumns(['action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
  	public function index()
    {
        return view('admin.logistics.index');
    }

    //*** GET Request
    public function create()
    {
        return view('admin.logistics.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
               'photo'      => 'required|mimes:jpeg,jpg,png,svg',
                ];

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new Logistic();
        $input = $request->all();
        if ($file = $request->file('photo')) 
         {      
            $name = time().str_replace(' ', '', $file->getClientOriginalName());
            $file->move('assets/images/logistics',$name);           
            $input['photo'] = $name;
        } 
        $input['password'] = bcrypt($request['password']);
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section        
        $msg = 'Company Added Successfully.';
        return response()->json($msg);      
        //--- Redirect Section Ends    
    }


    public function edit($id)
    {
        $data = Logistic::findOrFail($id);  
        return view('admin.logistics.edit',compact('data'));
    }

    public function update(Request $request,$id)
    {
        //--- Validation Section
        $rules =
            [
                'photo' => 'mimes:jpeg,jpg,png,svg',
                'email' => 'unique:admins,email,'.$id
            ];

            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends
            $input = $request->all();  
            $data = Logistic::findOrFail($id);        
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
            if($request->password == ''){
                $input['password'] = $data->password;
            }
            else{
                $input['password'] = bcrypt($request['password']);
            }
            $data->update($input);
            $msg = 'Logistic Company Updated Successfully.';
            return response()->json($msg);
 
    }

    //*** GET Request
    public function show($id)
    {
        $data = Logistic::findOrFail($id);
        return view('admin.logistics.show',compact('data'));
    }

    //*** GET Request Delete
    public function destroy($id)
    {
    // 	if($id == 1)
    // 	{
    //     return "You don't have access to remove this admin";
    // 	}
        $data = Logistic::findOrFail($id);
        //If Photo Doesn't Exist
        if($data->photo == null){
            $data->delete();
            //--- Redirect Section     
            $msg = 'Logistic Company Deleted Successfully.';
            return response()->json($msg);      
            //--- Redirect Section Ends     
        }
        //If Photo Exist
        if (file_exists(public_path().'/assets/images/logistics/'.$data->photo)) {
            unlink(public_path().'/assets/images/logistics/'.$data->photo);
        }
        $data->delete();
        //--- Redirect Section     
        $msg = 'Logistic Company Deleted Successfully.';
        return response()->json($msg);      
        //--- Redirect Section Ends    
    }


    //*** JSON Request
    
    public function withdrawdatatables()
    {
        
            $datas = Withdraw::where('type','=','logistics')->orderBy('id','desc')->get();
            //--- Integrating This Collection Into Datatables
            return Datatables::of($datas)
                            // ->addColumn('name', function(Withdraw $data) {
                            //     $name = $data->user->name;
                            //     return '<a href="' . route('admin-vendor-show', $data->user->id) . '" target="_blank">'. $name .'</a>';
                            // }) 
                            ->addColumn('company', function(Withdraw $data) {
                                $logistics = Logistic::where('id','=',$data->user_id)->first();
                                $company = $logistics->company;
                                return $company;
                            }) 
                            ->addColumn('phone', function(Withdraw $data) {
                                $logistics = Logistic::where('id','=',$data->user_id)->first();
                                $phone = $logistics->phone;
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
                                $action = '<div class="action-list"><a data-href="' . route('admin-logistics-withdraw-show',$data->id) . '" class="view details-width" data-toggle="modal" data-target="#modal1"> <i class="fas fa-eye"></i> Details</a>';
                                if($data->status == "pending") {
                                $action .= '<a data-href="' . route('admin-logistics-withdraw-accept',$data->id) . '" data-toggle="modal" data-target="#confirm-delete"> <i class="fas fa-check"></i> Accept</a><a data-href="' . route('admin-logistics-withdraw-reject',$data->id) . '" data-toggle="modal" data-target="#confirm-delete1"> <i class="fas fa-trash-alt"></i> Reject</a>';
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
        return view('admin.logistics.withdraws');
    }

    //*** GET Request       
    public function withdrawdetails($id)
    {
        $withdraw = DB::table('withdraws')
            ->join('logistics','withdraws.user_id','=','logistics.id')
            ->where('withdraws.id','=',$id)
            ->orderby('withdraws.id','desc')
            ->first();
        $sign = Currency::where('is_default','=',1)->first();
        return view('admin.logistics.withdraw-details',compact('withdraw','sign'));
    }

    //*** GET Request   
    public function accept($id)
    {
        $withdraw = Withdraw::findOrFail($id);
        $data['status'] = "completed";
        $withdraw->update($data);

        $withdraw = DB::table('withdraws')
            ->join('logistics','withdraws.user_id','=','logistics.id')
            ->where('withdraws.id','=',$id)
            ->orderby('withdraws.id','desc')
            ->first();

        $account = Logistic::findOrFail($withdraw->user_id);

        //Sending email to logistics
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
        // $withdraw = Withdraw::findOrFail($id);
        
        $withdraw = Withdraw::findOrFail($id);
        $data['status'] = "rejected";
        $withdraw->update($data);

        $withdraw = DB::table('withdraws')
            ->join('logistics','withdraws.user_id','=','logistics.id')
            ->where('withdraws.id','=',$id)
            ->orderby('withdraws.id','desc')
            ->first();

        $account = Logistic::findOrFail($withdraw->user_id);
        
        $account->current_balance = $account->current_balance + $withdraw->amount; 
        
        $account->update();
        
        //Sending email to logistics
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
