<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Booking;
use App\Models\Homepage;
use App\Models\Setting;
use App\Helpers\MailHelper;
use App\Models\EmailTemplate;
use App\Mail\userBooking;
use Auth;

class MyBookingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function index()
    {


        $user = Auth::guard('web')->user();
        $setting = Setting::first();
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
        $booking = Booking::with('property')->where('agent_id', $user->id)->paginate(10);

        return view('user.booking')->with(['booking' => $booking,'mobile_app' => $mobile_app]);
    }

    public function view(Request $request, $id)
    {
        $Booking = Booking::find($id);
        $Booking->status = $request->status;
        $Booking->save();

        // MailHelper::setMailConfig();

        // $template=EmailTemplate::where('id',13)->first();
        // $subject=$template->subject;
        // $message= 'Your Booking Is Confirmed';
        // $message = str_replace('{{user_name}}',$Booking->name,$message);
        // Mail::to($Booking->email)->send(new userBooking($message,$subject));

        $notification = trans('user_validation.Updated successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function remove($id)
    {
        $Booking = Booking::find($id);
        $Booking->delete();

        $notification = trans('user_validation.Deleted successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

    public function myBooking()
    {
        $user = Auth::guard('web')->user();
        $setting = Setting::first();
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
        $booking = Booking::with('property')->where('user_id', $user->id)->paginate(10);

        return view('user.my_booking')->with(['booking' => $booking,'mobile_app' => $mobile_app]);
    }

    public function myBookingRemove($id)
    {
        $Booking = Booking::find($id);
        $Booking->delete();

        $notification = trans('user_validation.Deleted successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }
}
