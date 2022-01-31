<?php

namespace App\Http\Controllers\Admin;

use Datatables;
use App\Models\Admin;
use App\Models\PackagingCenter;
use Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Input;
use Validator;


class PackagingCenterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    //*** JSON Request
    public function datatables()
    {
        $datas = PackagingCenter::orderBy('id')->get();
         //--- Integrating This Collection Into Datatables
         return Datatables::of($datas)
                            ->addColumn('action', function(PackagingCenter $data) {
                                $delete ='<a href="javascript:;" data-href="' . route('admin-packaging-center-delete',$data->id) . '" data-toggle="modal" data-target="#confirm-delete" class="delete"><i class="fas fa-trash-alt"></i></a>';
                                return '<div class="action-list"><a data-href="' . route('admin-packaging-center-show',$data->id) . '" class="view details-width" data-toggle="modal" data-target="#modal1"> <i class="fas fa-eye"></i>Details</a><a data-href="' . route('admin-packaging-center-edit',$data->id) . '" class="edit" data-toggle="modal" data-target="#modal1"> <i class="fas fa-edit"></i>Edit</a>'.$delete.'</div>';
                            }) 
                            ->rawColumns(['action'])
                            ->toJson(); //--- Returning Json Data To Client Side
    }

    //*** GET Request
  	public function index()
    {
        return view('admin.packaging-center.index');
    }

    //*** GET Request
    public function create()
    {
        return view('admin.packaging-center.create');
    }

    //*** POST Request
    public function store(Request $request)
    {
        //--- Validation Section
        $rules = [
               'address'      => 'required',
                ];

        $validator = Validator::make($request->all(), $rules);
        
        if ($validator->fails()) {
          return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        //--- Validation Section Ends

        //--- Logic Section
        $data = new PackagingCenter();
        $input = $request->all();
        $data->fill($input)->save();
        //--- Logic Section Ends

        //--- Redirect Section        
        $msg = 'Center Added Successfully.';
        return response()->json($msg);      
        //--- Redirect Section Ends    
    }


    public function edit($id)
    {
        $data = PackagingCenter::findOrFail($id);  
        return view('admin.packaging-center.edit',compact('data'));
    }

    public function update(Request $request,$id)
    {
        //--- Validation Section
        $rules =
            [
                'center' => 'required',
            ];

            $validator = Validator::make($request->all(), $rules);
            
            if ($validator->fails()) {
            return response()->json(array('errors' => $validator->getMessageBag()->toArray()));
            }
            //--- Validation Section Ends
            $input = $request->all();  
            $data = PackagingCenter::findOrFail($id);        
                
            $data->update($input);
            $msg = 'Packaging Center Updated Successfully.';
            return response()->json($msg);
    }

    //*** GET Request
    public function show($id)
    {
        $data = PackagingCenter::findOrFail($id);
        return view('admin.packaging-center.show',compact('data'));
    }

    //*** GET Request Delete
    public function destroy($id)
    {
    	
        $data = PackagingCenter::findOrFail($id);
        
        $data->delete();
        //--- Redirect Section     
        $msg = 'Packaging Center Deleted Successfully.';
        return response()->json($msg);      
        //--- Redirect Section Ends    
    }
}