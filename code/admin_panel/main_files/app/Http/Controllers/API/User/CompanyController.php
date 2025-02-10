<?php

namespace App\Http\Controllers\API\User;

use Auth;
use Image;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\Booking;
use App\Models\Setting;
use App\Models\Homepage;
use App\Models\Property;
use App\Models\Wishlist;
use App\Mail\userBooking;
use App\Helpers\MailHelper;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Models\EmailTemplate;
use App\Models\CompanyProfile;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;

class CompanyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function company_profile()
    {
        $user = Auth::guard('api')->user();

        if ($user->profile) {
            return response()->json(['company_profile' => $user->profile]);
        }else {
            return response()->json(['company_profile' => null]);
        }

    }

    public function my_team()
    {
        $user = Auth::guard('api')->user();

        $agents = User::where('owner_id', $user->id)->paginate(10);

        return response()->json(['agents' => $agents]);
    }

    public function agency_agent_edit($id){

        $agent = User::where('id', $id)->first();

        if(!$agent) return response()->json(['message' => trans('Agent not found')],403);

        return response()->json(['agency_agent' => $agent]);
    }

    public function apply_company(Request $request){

        $rules = [
            'name'=>'required',
            'email'=>'required|unique:company_profiles',
            'phone'=>'required|unique:company_profiles',
            'tag_line'=>'required',
            'address'=>'required',
            'about_us'=>'required',
            'kyc_id'=>'required',
            'file'=>'required',
        ];
        $customMessages = [
            'name.required' => trans('admin_validation.Name is required'),
            'email.required' => trans('admin_validation.Email is required'),
            'email.unique' => trans('admin_validation.Email already exist'),
            'phone.required' => trans('admin_validation.Phone is required'),
            'tag_line.required' => trans('admin_validation.Desgination is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'about_us.required' => trans('admin_validation.About is required'),
            'kyc_id.required' => trans('admin_validation.Type of is required'),
            'file' => trans('admin_validation.File is required'),
        ];

        $this->validate($request, $rules,$customMessages);

        $user = Auth::guard('api')->user();

        if($user->is_agency == 2){
            $notification=trans('admin_validation.you already applied for agency');
           return response()->json(['message' => $notification], 403);
        }

        if($user->is_agency == 1){
            $notification=trans('admin_validation.Your agency application has already been approved.');
           return response()->json(['message' => $notification], 403);
        }

        $companyProfile = $user?->profile;


        if ($companyProfile) {

            $old_image=$companyProfile->image;

            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }

            $companyProfile->delete();
        }

        $companyProfile = new CompanyProfile();

        if($request->image){
            $extention = $request->image->getClientOriginalExtension();
            $image_name = 'company-logo'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $companyProfile->image = $image_name;
        }

        if($request->file){
            $extention = $request->file->getClientOriginalExtension();
            $img_name = 'document'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $img_name = 'uploads/custom-images/'.$img_name;
            $request->file->move(public_path('uploads/custom-images/'),$img_name);
            $companyProfile->file = $img_name;
        }

        $companyProfile->user_id = $user->id;
        $companyProfile->company_name = $request->name;
        $companyProfile->tag_line = $request->tag_line;
        $companyProfile->about_us = $request->about_us;
        $companyProfile->email = $request->email;
        $companyProfile->phone = $request->phone;
        $companyProfile->facebook = $request->facebook;
        $companyProfile->twitter = $request->twitter;
        $companyProfile->linkedin = $request->linkedin;
        $companyProfile->instagram = $request->instagram;
        $companyProfile->address = $request->address;
        $companyProfile->is_approved = 2;
        $companyProfile->kyc_id = $request->kyc_id;
        $companyProfile->message = $request->message;
        $companyProfile->save();

        $user->is_agency = 2;
        $user->save();

        $notification = trans('admin_validation.Agency application success. Admin will verify your application.');

        return response()->json(['message' => $notification]);

    }

    public function update_agency_information(Request $request, $id){

        $profile = CompanyProfile::find($id);

        if(!$profile){
            $notification = trans('Profile Not Found');
            return response()->json(['message' => $notification], 403);
        }

        $rules = [
           'name'=>'required',
           'email'=>'required|unique:company_profiles,email,'.$id,
           'phone'=>'required|unique:company_profiles,phone,'.$id,
           'tag_line'=>'required',
           'address'=>'required',
           'about_us'=>'required',
       ];
       $customMessages = [
           'name.required' => trans('admin_validation.Name is required'),
           'email.required' => trans('admin_validation.Email is required'),
           'email.unique' => trans('admin_validation.Email already exist'),
           'phone.required' => trans('admin_validation.Phone is required'),
           'tag_line.required' => trans('admin_validation.Desgination is required'),
           'address.required' => trans('admin_validation.Address is required'),
           'about_us.required' => trans('admin_validation.About is required'),
       ];
       $this->validate($request, $rules,$customMessages);

       $user = Auth::guard('api')->user();

       if($user->is_agency == 2){
            $notification=trans('admin_validation.you already applied for agency');
            return response()->json(['message' => $notification], 403);
        }

       $companyProfile = $user->profile;


       if ($companyProfile) {

           if($request->image){

               $old_image=$companyProfile->image;

               $extention = $request->image->getClientOriginalExtension();
               $image_name = 'company-logo'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
               $image_name = 'uploads/custom-images/'.$image_name;

               Image::make($request->image)
                   ->encode('webp', 80)
                   ->save(public_path().'/'.$image_name);
               $companyProfile->image = $image_name;

               if($old_image){
                   if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
               }

           }

           if ($request->hasFile('file')) {

                $old_document=$companyProfile->file;

                $extention = $request->file->getClientOriginalExtension();
                $img_name = 'document'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
                $img_name = 'uploads/custom-images/'.$img_name;
                $request->file->move(public_path('uploads/custom-images/'),$img_name);
                $companyProfile->file = $img_name;

                if($old_document){
                    if(File::exists(public_path().'/'.$old_document))unlink(public_path().'/'.$old_document);
                }
            }

           $companyProfile->user_id = $user->id;
           $companyProfile->company_name = $request->name;
           $companyProfile->tag_line = $request->tag_line;
           $companyProfile->about_us = $request->about_us;
           $companyProfile->email = $request->email;
           $companyProfile->phone = $request->phone;
           $companyProfile->facebook = $request->facebook;
           $companyProfile->twitter = $request->twitter;
           $companyProfile->linkedin = $request->linkedin;
           $companyProfile->instagram = $request->instagram;
           $companyProfile->address = $request->address;
           $companyProfile->kyc_id = $request->has('kyc_id') ? $request->kyc_id : $companyProfile->kyc_id;
           $companyProfile->message = $request->has('message') ? $request->message : $companyProfile->message;
           $companyProfile->save();


           $notification = trans('admin_validation.Updated successfully');
           return response()->json(['message' => $notification]);
       }else{
        $notification = trans('Not Found');
           return response()->json(['message' => $notification], 403);
       }

   }

    public function store_agent(Request $request){

        $rules = [
            'name'=>'required',
            'email'=>'required|unique:users',
            'phone'=>'required|unique:users',
            'designation'=>'required',
            'address'=>'required',
            'about_me'=>'required',
            'password'=>'required|confirmed',
        ];

        $customMessages = [
            'password.required' => trans('admin_validation.Password is required'),
            'name.required' => trans('admin_validation.Name is required'),
            'email.required' => trans('admin_validation.Email is required'),
            'email.unique' => trans('admin_validation.Email already exist'),
            'phone.required' => trans('admin_validation.Phone is required'),
            'designation.required' => trans('admin_validation.Desgination is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'about_me.required' => trans('admin_validation.About is required'),
        ];

        $this->validate($request, $rules,$customMessages);

        $agent = new User();

        $user = Auth::guard('api')->user();

        if($user->is_agency != 1){
            $notification=trans('user.To create agent first you need to be an agency');
            return response()->json(['message' => $notification], 403);
        }

        if($request->image){
            $extention = $request->image->getClientOriginalExtension();
            $image_name = 'agent'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $agent->image = $image_name;
        }

        $agent->password = Hash::make($request->password);
        $agent->user_name = Str::slug($request->name).'-'.date('Ymdhis');
        $agent->name = $request->name;
        $agent->phone = $request->phone;
        $agent->email = $request->email;
        $agent->designation = $request->designation;
        $agent->address = $request->address;
        $agent->about_me = $request->about_me;
        $agent->facebook = $request->facebook;
        $agent->twitter = $request->twitter;
        $agent->linkedin = $request->linkedin;
        $agent->instagram = $request->instagram;
        $agent->status = $request->has('status') ? 1 : 0;
        $agent->owner_id = $user->id;
        $agent->save();

        $notification=trans('admin_validation.Created Successfully');

        return response()->json(['message' => $notification]);
    }

    public function agency_agent_update(Request $request, $id){

        $rules = [
            'name'=>'required',
            'email'=>'required|unique:users,email,'.$id,
            'phone'=>'required|unique:users,phone,'.$id,
            'designation'=>'required',
            'address'=>'required',
            'about_me'=>'required',
        ];

        $customMessages = [
            'password.required' => trans('admin_validation.Password is required'),
            'name.required' => trans('admin_validation.Name is required'),
            'email.required' => trans('admin_validation.Email is required'),
            'email.unique' => trans('admin_validation.Email already exist'),
            'phone.required' => trans('admin_validation.Phone is required'),
            'designation.required' => trans('admin_validation.Desgination is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'about_me.required' => trans('admin_validation.About is required'),
        ];

        $this->validate($request, $rules,$customMessages);

        $agent = User::where('id', $id)->first();

        if(!$agent){
            $notification = trans('Agent Not Found');
            return response()->json(['message' => $notification], 403);
        }

        $user = Auth::guard('api')->user();

        if($user->is_agency != 1){
            $notification=trans('user.To create agent first you need to be an agency');
            return response()->json(['message' => $notification], 403);
        }

        if($request->image){
            $extention = $request->image->getClientOriginalExtension();
            $image_name = 'agent'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $agent->image = $image_name;
        }

        $agent->user_name = Str::slug($request->name).'-'.date('Ymdhis');
        $agent->name = $request->name;
        $agent->phone = $request->phone;
        $agent->email = $request->email;
        $agent->designation = $request->designation;
        $agent->address = $request->address;
        $agent->about_me = $request->about_me;
        $agent->facebook = $request->facebook;
        $agent->twitter = $request->twitter;
        $agent->linkedin = $request->linkedin;
        $agent->instagram = $request->instagram;
        $agent->status = $request->has('status') ? 1 : 0;
        $agent->owner_id = $user->id;
        $agent->save();

        $notification=trans('admin_validation.Updated Successfully');

        return response()->json(['message' => $notification]);
    }

    public function destroy($id){

        $property_count = Property::where('agent_id', $id)->count();

        $user = User::find($id);

        if(!$user){
            $notification = trans('Agent Not Found');
            return response()->json(['message' => $notification], 403);
        }


        if($property_count == 0){
            Wishlist::where('user_id', $id)->delete();
            Review::where('user_id', $id)->delete();
            Review::where('agent_id', $id)->delete();
            Order::where('agent_id', $id)->delete();

            $user_image = $user?->image;
            $user->delete();
            if($user_image){
                if(File::exists(public_path().'/'.$user_image))unlink(public_path().'/'.$user_image);
            }

            $notification = trans('user_validation.Deleted successfully');
            return response()->json(['message' => $notification]);
        }else{
            $notification = trans('admin_validation.In this item multiple property exist, so you can not delete this item');
            return response()->json(['message' => $notification], 403);
        }
    }



}
