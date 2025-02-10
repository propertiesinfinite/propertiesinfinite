<?php

namespace App\Http\Controllers\API\User;

use Str;
use Auth;
use File;
use Hash;
use Slug;
use Image;
use Session;
use Exception;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Rules\Captcha;
use App\Models\Compare;
use App\Models\Setting;
use App\Models\Homepage;
use App\Models\Property;
use App\Models\Wishlist;
use App\Models\PropertyPlan;
use Illuminate\Http\Request;
use App\Models\PropertySlider;
use App\Models\GoogleRecaptcha;
use App\Models\PropertyAminity;
use App\Http\Controllers\Controller;
use App\Models\AdditionalInformation;
use App\Models\PropertyNearestLocation;
use Modules\SupportTicket\Entities\SupportTicket;
use Modules\SupportTicket\Entities\TicketMessage;

class UserProfileController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:api');
    }
    public function dashboard(){

        $user = Auth::guard('api')->user();

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

        $properties = Property::where('agent_id', $user->id)->orderBy('id','desc')->paginate(10);


        return response()->json([
            'publish_property' => $publish_property,
            'awaiting_property' => $awaiting_property,
            'reject_property' => $reject_property,
            'total_purchase' => $total_purchase,
            'total_wishlist' => $total_wishlist,
            'total_review' => $total_review,
            'user' => $user,
            'properties' => $properties
        ]);



    }

    public function my_profile(){
        $setting = Setting::first();

        $user = Auth::guard('api')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation', 'kyc_status')->where('id', $user->id)->first();


        return response()->json([
            'user' => $user
        ]);
    }


    public function update_profile(Request $request){
        $user = Auth::guard('api')->user();
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
        return response()->json(['message' => $notification]);

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

        $user = Auth::guard('api')->user();
        if(Hash::check($request->current_password, $user->password)){
            $user->password = Hash::make($request->password);
            $user->save();

            $notification = 'Password change successfully';
            return response()->json(['message' => $notification]);

        }else{
            $notification = trans('user_validation.Current password does not match');
            return response()->json(['message' => $notification],403);
        }
    }


    public function wishlist(){
        $setting = Setting::first();

        $user = Auth::guard('api')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        $wishlists = Wishlist::where(['user_id' => $user->id])->get();
        $wishlist_arr = array();
        foreach($wishlists as $wishlist){
            $wishlist_arr [] = $wishlist->property_id;
        }

        $properties = Property::where('status', 'enable')->whereIn('id', $wishlist_arr)->paginate(10);

        return response()->json([
            'user' => $user,
            'properties' => $properties,
        ]);
    }

    public function my_reviews(){
        $setting = Setting::first();

        $user = Auth::guard('api')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation', 'kyc_status')->where('id', $user->id)->first();

        $reviews = Review::with('property')->where(['user_id' => $user->id])->paginate(10);

        return response()->json([
            'user' => $user,
            'reviews' => $reviews,
        ]);
    }

    public function orders(){
        $user = Auth::guard('api')->user();
        $orders = Order::orderBy('id','desc')->where('agent_id', $user->id)->paginate(10);

        $setting = Setting::first();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

        return response()->json([
            'user' => $user,
            'orders' => $orders,
        ]);
    }

    public function order_show($id){
        $order = Order::where('order_id',$id)->first();

        return response()->json([
            'order' => $order
        ]);
    }

    public function add_to_wishlist($id){
        $user = Auth::guard('api')->user();
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

    public function remove_wishlist($id){
        $user = Auth::guard('api')->user();
        Wishlist::where(['user_id' => $user->id, 'property_id' => $id])->delete();
        $notification = trans('user_validation.Removed successfully');
        return response()->json(['message' => $notification]);
    }


    public function delete_account(Request $request){

        $request->validate([
            'password' => 'required'
        ]);

        $user = Auth::guard('api')->user();

        if(Hash::check($request->password, $user->password)){
            try{

                $properties = Property::where('agent_id', $user->id)->get();

                foreach($properties as $property){

                    $id = $property->id;

                    $property = Property::find($id);

                    PropertyAminity::where('property_id', $id)->delete();
                    PropertyNearestLocation::where('property_id', $id)->delete();
                    AdditionalInformation::where('property_id', $id)->delete();
                    Wishlist::where('property_id', $id)->delete();
                    Compare::where('property_id', $id)->delete();
                    Review::where('property_id', $id)->delete();

                    $existing_plans = PropertyPlan::where('property_id', $id)->get();

                    foreach($existing_plans as $existing_plan){
                        $old_image = $existing_plan->image;
                        if($old_image){
                            if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
                        }
                        $existing_plan->delete();
                    }

                    $existing_sliders = PropertySlider::where('property_id', $id)->get();

                    foreach($existing_sliders as $existing_slider){
                        $old_slider = $existing_slider->image;
                        if($old_slider){
                            if(File::exists(public_path().'/'.$old_slider))unlink(public_path().'/'.$old_slider);
                        }
                        $existing_slider->delete();
                    }


                    $old_thumbnail_image = $property->thumbnail_image;
                    if($old_thumbnail_image){
                        if(File::exists(public_path().'/'.$old_thumbnail_image))unlink(public_path().'/'.$old_thumbnail_image);
                    }

                    $old_video_thumbnail = $property->video_thumbnail;
                    if($old_video_thumbnail){
                        if(File::exists(public_path().'/'.$old_video_thumbnail))unlink(public_path().'/'.$old_video_thumbnail);
                    }

                    $property->delete();

                }

                $id = $user->id;

                SupportTicket::where('user_id', $id)->delete();
                TicketMessage::where('user_id', $id)->delete();
                Wishlist::where('user_id', $id)->delete();
                Review::where('user_id', $id)->delete();
                Review::where('agent_id', $id)->delete();
                Order::where('agent_id', $id)->delete();

                $user = User::find($id);
                $user_image = $user->image;

                if($user_image){
                    if(File::exists(public_path().'/'.$user_image))unlink(public_path().'/'.$user_image);
                }

                $user->delete();

            }catch(Exception $ex){
                return response()->json([
                    'message' => $ex->getMessage()
                ], 500);
            }
        }else{
            $notification = trans('Please provide valid password');
            return response()->json([
                'message' => $notification
            ], 403);
        }
    }


    public function compare(){

        $user = Auth::guard('api')->user();

        $compares = Compare::where(['user_id' => $user->id])->take(4)->get();
        $compares_arr = array();
        foreach($compares as $compare){
            $compares_arr [] = $compare->property_id;
        }

        $properties = Property::where('status', 'enable')->whereIn('id', $compares_arr)->take(4)->get();

        return response()->json([
            'properties' => $properties,
        ]);
    }

    public function add_to_compare($id){

        $user = Auth::guard('api')->user();
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

    public function remove_compare($id){

        $user = Auth::guard('api')->user();

        $isExist = Compare::where(['user_id' => $user->id, 'property_id' => $id])->count();

        if(!$isExist){
            $message = trans('Item not found');
            return response()->json(['message' => $message],403);
        }

        $compare = Compare::where('property_id',$id)->where('user_id', $user->id)->delete();

        $message = trans('Item removed to compare list');
        return response()->json(['message' => $message]);
    }
}
