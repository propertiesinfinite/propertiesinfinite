<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Aminity;
use App\Models\PropertyAminity;

class AminityContoller extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $aminities = Aminity::orderBy('id', 'desc')->get();

        return view('admin.aminity',compact('aminities'));
    }

    public function create(){
        return view('admin.aminity_create');
    }

    public function store(Request $request)
    {
        $rules = [
            'aminity'=>'required'
        ];
        $customMessages = [
            'aminity.required' => trans('admin_validation.Aminity is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $aminity = new Aminity();
        $aminity->aminity = $request->aminity;
        $aminity->save();

        $notification = trans('admin_validation.Created Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function edit($id){

        $aminity = Aminity::find($id);

        return view('admin.aminity_edit', compact('aminity'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'aminity'=>'required'
        ];
        $customMessages = [
            'aminity.required' => trans('admin_validation.Aminity is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $aminity = Aminity::find($id);
        $aminity->aminity = $request->aminity;
        $aminity->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.aminity.index')->with($notification);
    }

    public function destroy($id)
    {
        $count = PropertyAminity::where('aminity_id', $id)->count();
        if($count == 0){
            $aminity = Aminity::find($id);
            $aminity->delete();

            $notification = trans('admin_validation.Delete Successfully');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->back()->with($notification);
        }else{
            $notification = trans('admin_validation.In this item multiple property exist, so you can not delete this item');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

    }
}
