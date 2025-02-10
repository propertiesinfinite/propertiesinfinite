<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Admin;
use App\Models\Setting;
use Hash;
use Auth;
use Image;
use Str;
use File;
class AdminProfileController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        $admin=Auth::guard('admin')->user();
        $setting = Setting::first();
        $default_avatar = array(
            'image' => $setting->default_avatar
        );
        $default_avatar = (object) $default_avatar;
        return view('admin.admin_profile',compact('admin','default_avatar'));
    }

    public function update(Request $request){
        $admin=Auth::guard('admin')->user();
        $rules = [
            'name'=>'required',
            'email'=>'required|unique:admins,email,'.$admin->id,
            'password'=>'confirmed',
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'email.required' => trans('admin_validation.Email is required'),
            'email.unique' => trans('admin_validation.Email already exist'),
            'password.confirmed' => trans('admin_validation.Confirm password does not match'),
        ];
        $this->validate($request, $rules,$customMessages);

        $this->validate($request, $rules);
        $admin=Auth::guard('admin')->user();

        // inset user profile image
        if($request->file('image')){
            $old_image=$admin->image;
            $user_image=$request->image;
            $extention=$user_image->getClientOriginalExtension();
            $image_name= Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name='uploads/website-images/'.$image_name;
            Image::make($user_image)
                ->save(public_path().'/'.$image_name);

            $admin->image=$image_name;
            $admin->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->password){
            $admin->password=Hash::make($request->password);
        }
        $admin->name=$request->name;
        $admin->email=$request->email;
        $admin->save();

        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.profile')->with($notification);

    }

    public function agent_profile(){
        $admin = Auth::guard('admin')->user();

        return view('admin.agent_profile',compact('admin'));
    }

    public function agent_profile_update(Request $request){
        $rules = [
            'name'=>'required',
            'email'=>'required',
            'phone'=>'required',
            'designation'=>'required',
            'address'=>'required',
            'about_me'=>'required',
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'email.required' => trans('admin_validation.Email is required'),
            'email.unique' => trans('admin_validation.Email already exist'),
            'phone.required' => trans('admin_validation.Phone is required'),
            'designation.required' => trans('admin_validation.Desgination is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'about_me.required' => trans('admin_validation.About is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $admin = Auth::guard('admin')->user();

        $admin->agent_name = $request->name;
        $admin->agent_phone = $request->phone;
        $admin->agent_email = $request->email;
        $admin->designation = $request->designation;
        $admin->agent_address = $request->address;
        $admin->about_me = $request->about_me;
        $admin->facebook = $request->facebook;
        $admin->twitter = $request->twitter;
        $admin->linkedin = $request->linkedin;
        $admin->instagram = $request->instagram;
        $admin->save();

        if($request->file('image')){
            $old_image = $admin->agent_image;
            $user_image = $request->image;
            $extention = $user_image->getClientOriginalExtension();
            $image_name = Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name = 'uploads/website-images/'.$image_name;
            Image::make($user_image)
                ->save(public_path().'/'.$image_name);

            $admin->agent_image = $image_name;
            $admin->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        $notification=trans('admin_validation.Updated Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
}
