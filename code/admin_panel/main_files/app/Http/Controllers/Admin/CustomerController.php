<?php

namespace App\Http\Controllers\Admin;

use Str;
use File;
use Mail;
use Image;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\Property;
use App\Models\Wishlist;
use App\Helpers\MailHelper;
use Illuminate\Http\Request;
use App\Mail\SendSingleAgentMail;
use App\Http\Controllers\Controller;
use Modules\SupportTicket\Entities\SupportTicket;
use Modules\SupportTicket\Entities\TicketMessage;

class CustomerController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        $customers = User::orderBy('id','desc')->where('status',1)->get();

        return view('admin.customer', compact('customers'));
    }

    public function pending_customer_list(){
        $customers = User::orderBy('id','desc')->where('status',0)->get();
        return view('admin.customer', compact('customers'));
    }

    public function show($id){
        $customer = User::find($id);
        if($customer){
            return view('admin.show_customer',compact('customer'));
        }else{
            $notification='Something went wrong';
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('admin.customer-list')->with($notification);
        }

    }

    public function create(){
        return view('admin.create_user');
    }

    public function store(Request $request){
        $rules = [
            'name'=>'required',
            'email'=>'required|unique:users',
            'phone'=>'required',
            'designation'=>'required',
            'address'=>'required',
            'about_me'=>'required',
            'password'=>'required',
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
        $agent->status = 1;
        $agent->save();

        $notification=trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function destroy($id)
    {
        $property_count = Property::where('agent_id', $id)->count();


        $sub_agents = User::where('owner_id', $id)->count();

        if($sub_agents > 0){
            $notification = trans('admin_validation.In this item multiple agent exist, so you can not delete this item');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        if($property_count == 0){
            SupportTicket::where('user_id', $id)->delete();
            TicketMessage::where('user_id', $id)->delete();
            Wishlist::where('user_id', $id)->delete();
            Review::where('user_id', $id)->delete();
            Review::where('agent_id', $id)->delete();
            Order::where('agent_id', $id)->delete();


            $companyProfile = CompanyProfile::where('user_id', $id)->first();

            if($companyProfile){
                if($companyProfile->image){
                    if(File::exists(public_path().'/'.$companyProfile->image))unlink(public_path().'/'.$companyProfile->image);
                }

                if($companyProfile->file){
                    if(File::exists(public_path().'/'.$companyProfile->file))unlink(public_path().'/'.$companyProfile->file);
                }

                $companyProfile->delete();

            }


            $user = User::find($id);
            $user_image = $user->image;
            $user->delete();
            if($user_image){
                if(File::exists(public_path().'/'.$user_image))unlink(public_path().'/'.$user_image);
            }


            $notification = trans('admin_validation.Delete Successfully');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->back()->with($notification);

        }else{
            $notification = trans('admin_validation.In this item multiple property exist, so you can not delete this item');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

    }

    public function change_status($id){
        $customer = User::find($id);
        if($customer->status == 1){
            $customer->status = 0;
            $customer->save();
            $message = trans('admin_validation.Inactive Successfully');
        }else{
            $customer->status = 1;
            $customer->save();
            $message = trans('admin_validation.Active Successfully');
        }
        return response()->json($message);
    }

    public function send_emailto_all_user(){
        return view('admin.send_email_to_all_customer');
    }

    public function send_mailto_all_user(Request $request){
        $rules = [
            'subject'=>'required',
            'message'=>'required'
        ];
        $customMessages = [
            'subject.required' => trans('admin_validation.Subject is required'),
            'message.required' => trans('admin_validation.Message is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $users = User::where('status',1)->get();
        MailHelper::setMailConfig();
        foreach($users as $user){
            Mail::to($user->email)->send(new SendSingleSellerMail($request->subject,$request->message));
        }

        $notification = trans('admin_validation.Email Send Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function send_mailto_single_user(Request $request, $id){
        $rules = [
            'subject'=>'required',
            'message'=>'required'
        ];
        $customMessages = [
            'subject.required' => trans('admin_validation.Subject is required'),
            'message.required' => trans('admin_validation.Message is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user = User::find($id);
        MailHelper::setMailConfig();
        Mail::to($user->email)->send(new SendSingleAgentMail($request->subject,$request->message));

        $notification = trans('admin_validation.Email Send Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

}
