<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\NearestLocation;
use App\Models\PropertyNearestLocation;

class NearestLocationController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $locations = NearestLocation::orderBy('id', 'desc')->get();

        return view('admin.nearest_location',compact('locations'));
    }

    public function create(){
        return view('admin.nearest_location_create');
    }

    public function store(Request $request)
    {
        $rules = [
            'location'=>'required'
        ];
        $customMessages = [
            'location.required' => trans('admin_validation.Location is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $location = new NearestLocation();

        $location->location = $request->location;
        $location->status = $request->status;
        $location->save();

        $notification = trans('admin_validation.Created Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function edit($id){

        $location = NearestLocation::find($id);

        return view('admin.nearest_location_edit', compact('location'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'location'=>'required'
        ];
        $customMessages = [
            'location.required' => trans('admin_validation.Location is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $location = NearestLocation::find($id);

        $location->location = $request->location;
        $location->status = $request->status;
        $location->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.nearest-location.index')->with($notification);
    }

    public function destroy($id)
    {
        $count = PropertyNearestLocation::where('nearest_location_id', $id)->count();
        if($count == 0){
            $location = NearestLocation::find($id);
            $location->delete();

            $notification = trans('admin_validation.Delete Successfully');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->back()->with($notification);
        }else{
            $notification = trans('admin_validation.In this item multiple property exist, so you can not delete this item');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

    }

    public function changeStatus($id){
        $category = NearestLocation::find($id);
        if($category->status==1){
            $category->status=0;
            $category->save();
            $message = trans('admin_validation.Inactive Successfully');
        }else{
            $category->status=1;
            $category->save();
            $message= trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

}
