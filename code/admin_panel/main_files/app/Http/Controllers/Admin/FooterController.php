<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Footer;
use Image;
use File;
class FooterController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        $footer = Footer::first();
        return view('admin.website_footer', compact('footer'));
    }

    public function update(Request $request, $id){
        $rules = [
            'email' =>'required',
            'phone' =>'required',
            'address' =>'required',
            'copyright' =>'required',
            'about_us' =>'required',
        ];
        $customMessages = [
            'email.required' => trans('admin_validation.Email is required'),
            'phone.required' => trans('admin_validation.Phone is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'copyright.required' => trans('admin_validation.Copyright is required'),
            'about_us.required' => trans('admin_validation.About Us is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $footer = Footer::first();
        $footer->email = $request->email;
        $footer->phone = $request->phone;
        $footer->address = $request->address;
        $footer->copyright = $request->copyright;
        $footer->about_us = $request->about_us;
        $footer->save();


        if($request->file('background_image')){

            $old_background = $footer->background_image;

            $extention = $request->background_image->getClientOriginalExtension();
            $image_name = 'footer-bg'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_path = 'uploads/website-images/'.$image_name;

            $request->background_image->move(public_path('uploads/website-images/'),$image_name);
            $footer->background_image = $image_path;
            $footer->save();

            if($old_background){
                if(File::exists(public_path().'/'.$old_background))unlink(public_path().'/'.$old_background);
            }

        }


        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }
}
