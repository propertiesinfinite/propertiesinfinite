<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Booking;
use Auth;

class BookingController extends Controller
{
    public function store(Request $request){
        $user = Auth::guard('web')->user();
        if($user){
            $rules = [
                'property_id'=>'required',
                'booking_time'=>'required',
                'booking_date'=>'required',
                'guests'=>'required',
                'name'=>'required',
                'city'=>'required',
                'country'=>'required',
                'zip_code'=>'required',
                'email'=>'required',
                'phone'=>'required',
            ];
            $customMessages = [
                'property_id.required' => trans('user_validation.Property id is required'),
                'booking_time.required' => trans('user_validation.Booking Time is required'),
                'booking_date.unique' => trans('user_validation.Bookig date already exist'),
                'guests.required' => trans('user_validation.Guests is required'),
                'name.required' => trans('user_validation.Name is required'),
                'city.required' => trans('user_validation.City is required'),
                'country.required' => trans('user_validation.Country is required'),
                'zip_code.required' => trans('user_validation.Zip Code is required'),
                'email.required' => trans('user_validation.Email is required'),
                'phone.required' => trans('user_validation.Phone is required'),
            ];
            $this->validate($request, $rules,$customMessages);
            $booking = new Booking();
            $booking->property_id = $request->property_id;
            $booking->agent_id = $request->agent_id;
            $booking->user_id = $user->id;
            $booking->booking_time = $request->booking_time;
            $booking->booking_date = $request->booking_date;
            $booking->guests = $request->guests;
            $booking->name = $request->name;
            $booking->country = $request->country;
            $booking->city = $request->city;
            $booking->zip_code = $request->zip_code;
            $booking->email = $request->email;
            $booking->phone = $request->phone;
            $booking->comment = $request->comment;
            $booking->save();
            $notification = trans('user_validation.Booking Created Successfully');
            $notification=array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->back()->with($notification);

        }else{
            $notification = trans('user_validation.First You Need To Login');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

    }
}
