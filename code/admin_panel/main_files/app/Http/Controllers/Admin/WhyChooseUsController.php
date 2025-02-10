<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\WhyChooseUs;
use Image;
use File;
use Str;


class WhyChooseUsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $items = WhyChooseUs::all();

        return view('admin.why_choose_use',compact('items'));
    }

    public function edit($id)
    {
        $item = WhyChooseUs::find($id);

        return view('admin.why_choose_us_edit',compact('item'));
    }

    public function update(Request $request, $id)
    {
        $item = WhyChooseUs::find($id);
        $rules = [
            'title' => 'required',
            'description' => 'required',
        ];
        $customMessages = [
            'title.required' => trans('admin_validation.Title is required'),
            'description.required' => trans('admin_validation.Description is required')
        ];
        $this->validate($request, $rules,$customMessages);

        if($request->icon){
            $existing_image = $item->icon;
            $extention = $request->icon->getClientOriginalExtension();
            $image_name = Str::slug($request->title).date('Ymdhis').'.'.$extention;
            $image_name = 'uploads/website-images/'.$image_name;
            $request->icon->move(public_path('uploads/website-images/'),$image_name);

            $item->icon = $image_name;
            $item->save();
            if($existing_image){
                if(File::exists(public_path().'/'.$existing_image))unlink(public_path().'/'.$existing_image);
            }
        }

        $item->title = $request->title;
        $item->description = $request->description;
        $item->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.why-choose-us.index')->with($notification);
    }

}
