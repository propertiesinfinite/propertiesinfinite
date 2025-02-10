<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\GoogleRecaptcha;
use App\Models\Setting;
use App\Models\Order;
use App\Models\Review;
use App\Models\Wishlist;
use App\Models\Compare;
use App\Models\Property;
use App\Models\Homepage;
use App\Rules\Captcha;
use Image;
use File;
use Str;
use Hash;
use Slug;
use Auth;
use Session;

class UserProfileController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:web');
    }
    public function dashboard(){

        $user = Auth::guard('web')->user();

        $publish_property = Property::where('agent_id', $user->id)
                                    ->where('status', 'enable')
                                    ->where('approve_by_admin', 'approved')
                                    ->where(function ($query) {
                                        $query->where('expired_date', null)
                                            ->orWhere('expired_date', '>=', date('Y-m-d'));
                                    })
                                    ->count();

        $awaiting_property = Property::where('agent_id', $user->id)
                                    ->where('approve_by_admin', 'pending')
                                    ->count();

        $reject_property = Property::where('agent_id', $user->id)
                                    ->where('approve_by_admin', 'reject')
                                    ->count();

        $total_purchase = Order::where('agent_id', $user->id)->count();
        $total_wishlist = Wishlist::where('user_id', $user->id)->count();
        $total_review = Review::where('user_id', $user->id)->count();

        $setting = Setting::first();

        $user = User::select('id','name','email','image','phone','address','status')->where('id', $user->id)->first();

        // mobile app
        $app_visibility = false;
        $homepage = Homepage::first();
        if($homepage->show_mobile_app == 'enable') $app_visibility = true;
        $mobile_app = (object) array(
            'visibility' => $app_visibility,
            'app_bg' => $setting->app_bg,
            'full_title' => $setting->app_full_title,
            'description' => $setting->app_description,
            'play_store' => $setting->google_playstore_link,
            'app_store' => $setting->app_store_link,
            'image' => $setting->app_image,
            'apple_btn_text1' => $setting->apple_btn_text1,
            'apple_btn_text2' => $setting->apple_btn_text2,
            'google_btn_text1' => $setting->google_btn_text1,
            'google_btn_text2' => $setting->google_btn_text2,
        );
        // mobile app

        return view('user.dashboard')->with([
            'publish_property' => $publish_property,
            'awaiting_property' => $awaiting_property,
            'reject_property' => $reject_property,
            'total_purchase' => $total_purchase,
            'total_wishlist' => $total_wishlist,
            'total_review' => $total_review,
            'mobile_app' => $mobile_app,
        ]);


    }

    public function my_profile(){
        $setting = Setting::first();

        $user = Auth::guard('web')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        // mobile app
        $app_visibility = false;
        $homepage = Homepage::first();
        if($homepage->show_mobile_app == 'enable') $app_visibility = true;
        $mobile_app = (object) array(
            'visibility' => $app_visibility,
            'app_bg' => $setting->app_bg,
            'full_title' => $setting->app_full_title,
            'description' => $setting->app_description,
            'play_store' => $setting->google_playstore_link,
            'app_store' => $setting->app_store_link,
            'image' => $setting->app_image,
            'apple_btn_text1' => $setting->apple_btn_text1,
            'apple_btn_text2' => $setting->apple_btn_text2,
            'google_btn_text1' => $setting->google_btn_text1,
            'google_btn_text2' => $setting->google_btn_text2,
        );
        // mobile app

        return view('user.my_profile')->with([
            'user' => $user,
            'mobile_app' => $mobile_app,
        ]);
    }


    public function update_profile(Request $request){
        $user = Auth::guard('web')->user();
        $rules = [
            'name'=>'required',
            'phone'=>'required',
            'address'=>'required',
            'designation'=>'required',
            'about_me'=>'required',
        ];
        $customMessages = [
            'name.required' => trans('user_validation.Name is required'),
            'email.required' => trans('user_validation.Email is required'),
            'email.unique' => trans('user_validation.Email already exist'),
            'phone.required' => trans('user_validation.Phone is required'),
            'address.required' => trans('user_validation.Address is required'),
            'designation.required' => trans('user_validation.Designation is required'),
            'about_me.required' => trans('user_validation.About me is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user->name = $request->name;
        $user->phone = $request->phone;
        $user->address = $request->address;
        $user->about_me = $request->about_me;
        $user->designation = $request->designation;
        $user->facebook = $request->facebook;
        $user->twitter = $request->twitter;
        $user->linkedin = $request->linkedin;
        $user->instagram = $request->instagram;
        $user->save();

        if($request->file('image')){
            $old_image=$user->image;
            $user_image=$request->image;
            $extention=$user_image->getClientOriginalExtension();
            $image_name= Str::slug($request->name).date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name='uploads/custom-images/'.$image_name;

            Image::make($user_image)
                ->save(public_path().'/'.$image_name);

            $user->image=$image_name;
            $user->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        $notification = trans('user_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

    public function change_password(){
        $setting = Setting::first();

        $user = Auth::guard('web')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        // mobile app
        $app_visibility = false;
        $homepage = Homepage::first();
        if($homepage->show_mobile_app == 'enable') $app_visibility = true;
        $mobile_app = (object) array(
            'visibility' => $app_visibility,
            'app_bg' => $setting->app_bg,
            'full_title' => $setting->app_full_title,
            'description' => $setting->app_description,
            'play_store' => $setting->google_playstore_link,
            'app_store' => $setting->app_store_link,
            'image' => $setting->app_image,
            'apple_btn_text1' => $setting->apple_btn_text1,
            'apple_btn_text2' => $setting->apple_btn_text2,
            'google_btn_text1' => $setting->google_btn_text1,
            'google_btn_text2' => $setting->google_btn_text2,
        );
        // mobile app

        return view('user.change_password')->with([
            'user' => $user,
            'mobile_app' => $mobile_app,
        ]);
    }

    public function updatePassword(Request $request){
        $rules = [
            'current_password'=>'required',
            'password'=>'required|min:4|confirmed',
        ];
        $customMessages = [
            'current_password.required' => trans('user_validation.Current password is required'),
            'password.required' => trans('user_validation.Password is required'),
            'password.min' => trans('user_validation.Password minimum 4 character'),
            'password.confirmed' => trans('user_validation.Confirm password does not match'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user = Auth::guard('web')->user();
        if(Hash::check($request->current_password, $user->password)){
            $user->password = Hash::make($request->password);
            $user->save();

            $notification = 'Password change successfully';
            $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

        }else{
            $notification = trans('user_validation.Current password does not match');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }
    }


    public function wishlist(){
        $setting = Setting::first();

        $user = Auth::guard('web')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        // mobile app
        $app_visibility = false;
        $homepage = Homepage::first();
        if($homepage->show_mobile_app == 'enable') $app_visibility = true;
        $mobile_app = (object) array(
            'visibility' => $app_visibility,
            'app_bg' => $setting->app_bg,
            'full_title' => $setting->app_full_title,
            'description' => $setting->app_description,
            'play_store' => $setting->google_playstore_link,
            'app_store' => $setting->app_store_link,
            'image' => $setting->app_image,
            'apple_btn_text1' => $setting->apple_btn_text1,
            'apple_btn_text2' => $setting->apple_btn_text2,
            'google_btn_text1' => $setting->google_btn_text1,
            'google_btn_text2' => $setting->google_btn_text2,
        );
        // mobile app

        $wishlists = Wishlist::where(['user_id' => $user->id])->get();
        $wishlist_arr = array();
        foreach($wishlists as $wishlist){
            $wishlist_arr [] = $wishlist->property_id;
        }

        $properties = Property::where('status', 'enable')->whereIn('id', $wishlist_arr)->paginate(10);

        return view('user.wishlist')->with([
            'user' => $user,
            'mobile_app' => $mobile_app,
            'properties' => $properties,
        ]);

    }

    public function compare(){

        $setting = Setting::first();
        $user = Auth::guard('web')->user();
        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        // mobile app
        $app_visibility = false;
        $homepage = Homepage::first();
        if($homepage->show_mobile_app == 'enable') $app_visibility = true;
        $mobile_app = (object) array(
            'visibility' => $app_visibility,
            'app_bg' => $setting->app_bg,
            'full_title' => $setting->app_full_title,
            'description' => $setting->app_description,
            'play_store' => $setting->google_playstore_link,
            'app_store' => $setting->app_store_link,
            'image' => $setting->app_image,
            'apple_btn_text1' => $setting->apple_btn_text1,
            'apple_btn_text2' => $setting->apple_btn_text2,
            'google_btn_text1' => $setting->google_btn_text1,
            'google_btn_text2' => $setting->google_btn_text2,
        );
        // mobile app

        $compares = Compare::where(['user_id' => $user->id])->take(4)->get();
        $compares_arr = array();
        foreach($compares as $compare){
            $compares_arr [] = $compare->property_id;
        }

        $properties = Property::where('status', 'enable')->whereIn('id', $compares_arr)->take(4)->get();

        return view('user.compare')->with([
            'user' => $user,
            'mobile_app' => $mobile_app,
            'properties' => $properties,
        ]);
    }

    public function my_reviews(){
        $setting = Setting::first();

        $user = Auth::guard('web')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        // mobile app
        $app_visibility = false;
        $homepage = Homepage::first();
        if($homepage->show_mobile_app == 'enable') $app_visibility = true;
        $mobile_app = (object) array(
            'visibility' => $app_visibility,
            'app_bg' => $setting->app_bg,
            'full_title' => $setting->app_full_title,
            'description' => $setting->app_description,
            'play_store' => $setting->google_playstore_link,
            'app_store' => $setting->app_store_link,
            'image' => $setting->app_image,
            'apple_btn_text1' => $setting->apple_btn_text1,
            'apple_btn_text2' => $setting->apple_btn_text2,
            'google_btn_text1' => $setting->google_btn_text1,
            'google_btn_text2' => $setting->google_btn_text2,
        );
        // mobile app

        $reviews = Review::with('property')->where(['user_id' => $user->id])->paginate(10);

        return view('user.my_reviews')->with([
            'user' => $user,
            'mobile_app' => $mobile_app,
            'reviews' => $reviews,
        ]);
    }

    public function orders(){
        $user = Auth::guard('web')->user();
        $orders = Order::orderBy('id','desc')->where('agent_id', $user->id)->paginate(10);

        $setting = Setting::first();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        // mobile app
        $app_visibility = false;
        $homepage = Homepage::first();
        if($homepage->show_mobile_app == 'enable') $app_visibility = true;
        $mobile_app = (object) array(
            'visibility' => $app_visibility,
            'app_bg' => $setting->app_bg,
            'full_title' => $setting->app_full_title,
            'description' => $setting->app_description,
            'play_store' => $setting->google_playstore_link,
            'app_store' => $setting->app_store_link,
            'image' => $setting->app_image,
            'apple_btn_text1' => $setting->apple_btn_text1,
            'apple_btn_text2' => $setting->apple_btn_text2,
            'google_btn_text1' => $setting->google_btn_text1,
            'google_btn_text2' => $setting->google_btn_text2,
        );
        // mobile app

        return view('user.order')->with([
            'mobile_app' => $mobile_app,
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function add_to_wishlist($id){
        $user = Auth::guard('web')->user();
        $isExist = Wishlist::where(['user_id' => $user->id, 'property_id' => $id])->count();
        if($isExist == 0){
            $wishlist = new Wishlist();
            $wishlist->property_id = $id;
            $wishlist->user_id = $user->id;
            $wishlist->save();
            $message = trans('user_validation.Wishlist added successfully');
            return response()->json(['message' => $message]);
        }else{
            $message = trans('user_validation.Already added to wishlist');
            return response()->json(['message' => $message],403);
        }
    }

    public function add_to_compare($id){

        $user = Auth::guard('web')->user();
        $isExist = Compare::where(['user_id' => $user->id, 'property_id' => $id])->count();
        $compareCount = Compare::where(['user_id' => $user->id])->count();
        if($compareCount >= 4)
        {
            $message = trans('You can not add more than 4 properties');
            return response()->json(['message' => $message],403);

        }else{

            if($isExist == 0){
                $compare = new Compare();
                $compare->property_id = $id;
                $compare->user_id = $user->id;
                $compare->save();
                $message = trans('user_validation.Compare added successfully');
                return response()->json(['message' => $message]);
            }else{
                $message = trans('user_validation.Already added to Compare');
                return response()->json(['message' => $message],403);
            }
        }

    }

    public function remove_wishlist($id){
        $user = Auth::guard('web')->user();
        $wishlist = Wishlist::where('property_id',$id)->where('user_id', $user->id)->delete();

        $notification = trans('user_validation.Removed successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
    public function remove_compare($id){

        $compare = Compare::where('property_id',$id)->delete();
        $notification = trans('user_validation.Removed successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

}
