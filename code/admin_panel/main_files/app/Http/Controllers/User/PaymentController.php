<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\Setting;
use App\Models\StripePayment;
use App\Models\PaypalPayment;
use App\Mail\OrderSuccessfully;
use App\Helpers\MailHelper;
use App\Models\EmailTemplate;
use App\Models\RazorpayPayment;
use App\Models\Flutterwave;
use App\Models\PaystackAndMollie;
use App\Models\InstamojoPayment;
use App\Models\PaymongoPayment;
use App\Models\BankPayment;
use App\Models\User;
use App\Models\Homepage;
use App\Models\BreadcrumbImage;
use App\Models\Order;
use App\Models\PricingPlan;
use App\Models\Property;

use Mail;
Use Stripe;
use Cart;
use Session;
use Str;
use Razorpay\Api\Api;
use Exception;
use Redirect;
use Auth;

use Mollie\Laravel\Facades\Mollie;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:web');
    }

    public function free_enroll($slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $user = Auth::guard('web')->user();

        $free_order = Order::where(['agent_id' => $user->id, 'plan_type' => 'free'])->count();

        if($free_order == 0){
            $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
            $order = $this->createOrder($user, $pricing_plan, 'Free', 'success', 'free_enroll');

            $this->sendMailToClient($user, $order);

            $notification = trans('user_validation.You have successfully enrolled this package');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->route('user.dashboard')->with($notification);

        }else{
            $notification = trans('user_validation.You have already enrolled trail version');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

    }


    public function payment($slug){

        $setting = Setting::first();

        $user = Auth::guard('web')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation','is_agency', 'owner_id')->where('id', $user->id)->first();

        if($user->owner_id != 0){
            $notification = trans('user_validation.You are not eligible to buy subscription');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

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

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

        if($pricing_plan->expired_time == 'monthly'){
            $plan_expired_date = date('Y-m-d', strtotime('30 days'));
        }elseif($pricing_plan->expired_time == 'yearly'){
            $plan_expired_date = date('Y-m-d', strtotime('365 days'));
        }elseif($pricing_plan->expired_time == 'lifetime'){
            $plan_expired_date = 'lifetime';
        }

        $bankPayment = BankPayment::select('id','status','account_info','image')->first();
        $stripe = StripePayment::first();
        $paypal = PaypalPayment::first();
        $razorpay = RazorpayPayment::first();
        $flutterwave = Flutterwave::first();
        $mollie = PaystackAndMollie::first();
        $paystack = $mollie;
        $instamojoPayment = InstamojoPayment::first();

        return view('payment')->with([
            'user' => $user,
            'mobile_app' => $mobile_app,
            'pricing_plan' => $pricing_plan,
            'plan_expired_date' => $plan_expired_date,
            'bankPayment' => $bankPayment,
            'stripe' => $stripe,
            'paypal' => $paypal,
            'razorpay' => $razorpay,
            'flutterwave' => $flutterwave,
            'mollie' => $mollie,
            'instamojoPayment' => $instamojoPayment,
            'paystack' => $paystack,
        ]);

    }

    public function bankPayment(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $rules = [
            'tnx_info'=>'required',
        ];
        $customMessages = [
            'tnx_info.required' => trans('user_validation.Transaction is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
        $user = Auth::guard('web')->user();
        $order = $this->createOrder($user, $pricing_plan, 'Bank payment', 'pending', $request->tnx_info);
        $this->sendMailToClient($user, $order);

        $notification = trans('user_validation.Your order has been placed. please wait for admin payment approval');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('user.dashboard')->with($notification);
    }

    public function payWithStripe(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

        $user = Auth::guard('web')->user();

        $stripe = StripePayment::first();
        $payableAmount = round($pricing_plan->plan_price * $stripe->currency_rate,2);
        Stripe\Stripe::setApiKey($stripe->stripe_secret);

        $result = Stripe\Charge::create ([
                "amount" => $payableAmount * 100,
                "currency" => $stripe->currency_code,
                "source" => $request->stripeToken,
                "description" => env('APP_NAME')
            ]);

        $order = $this->createOrder($user, $pricing_plan, 'Stripe', 'success', $result->balance_transaction);

        $this->sendMailToClient($user, $order);

        $notification = trans('user_validation.You have successfully enrolled this package');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('user.dashboard')->with($notification);

    }

    public function payWithRazorpay(Request $request, $slug){
        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $razorpay = RazorpayPayment::first();
        $input = $request->all();
        $api = new Api($razorpay->key,$razorpay->secret_key);
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount']));
                $payId = $response->id;

                $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

                $user = Auth::guard('web')->user();
                $order = $this->createOrder($user, $pricing_plan, 'Razorpay', 'success', $payId);

                $this->sendMailToClient($user, $order);

                $notification = trans('user_validation.You have successfully enrolled this package');
                $notification = array('messege'=>$notification,'alert-type'=>'success');
                return redirect()->route('user.dashboard')->with($notification);

            }catch (Exception $e) {
                $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
                $notification = trans('user_validation.Payment Faild');
                $notification = array('messege'=>$notification,'alert-type'=>'error');
                return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
            }
        }else{
            $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
            $notification = trans('user_validation.Payment Faild');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
        }
    }

    public function payWithFlutterwave(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $flutterwave = Flutterwave::first();
        $curl = curl_init();
        $tnx_id = $request->tnx_id;
        $url = "https://api.flutterwave.com/v3/transactions/$tnx_id/verify";
        $token = $flutterwave->secret_key;
        curl_setopt_array($curl, array(
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => "",
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array(
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        $response = json_decode($response);
        if($response->status == 'success'){

            $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
            $user = Auth::guard('web')->user();
            $order = $this->createOrder($user, $pricing_plan, 'Flutterwave', 'success', $tnx_id);
            $this->sendMailToClient($user, $order);

            $notification = trans('user_validation.You have successfully enrolled this package');
            return response()->json(['status' => 'success' , 'message' => $notification]);
        }else{
            $notification = trans('user_validation.Payment Faild');
            return response()->json(['status' => 'faild' , 'message' => $notification]);
        }
    }

    public function payWithMollie(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
        $user = Auth::guard('web')->user();

        $mollie = PaystackAndMollie::first();
        $price = $pricing_plan->plan_price * $mollie->mollie_currency_rate;
        $price = round($price,2);
        $price = sprintf('%0.2f', $price);

        $mollie_api_key = $mollie->mollie_key;
        $currency = strtoupper($mollie->mollie_currency_code);
        Mollie::api()->setApiKey($mollie_api_key);
        $payment = Mollie::api()->payments()->create([
            'amount' => [
                'currency' => $currency,
                'value' => ''.$price.'',
            ],
            'description' => env('APP_NAME'),
            'redirectUrl' => route('mollie-payment-success'),
        ]);

        $payment = Mollie::api()->payments()->get($payment->id);
        session()->put('payment_id',$payment->id);
        session()->put('pricing_plan',$pricing_plan);
        return redirect($payment->getCheckoutUrl(), 303);
    }

    public function molliePaymentSuccess(Request $request){
        $pricing_plan = Session::get('pricing_plan');
        $mollie = PaystackAndMollie::first();
        $mollie_api_key = $mollie->mollie_key;
        Mollie::api()->setApiKey($mollie_api_key);
        $payment = Mollie::api()->payments->get(session()->get('payment_id'));
        if ($payment->isPaid()){
            $user = Auth::guard('web')->user();
            $order = $this->createOrder($user, $pricing_plan, 'Mollie', 'success', session()->get('payment_id'));
            $this->sendMailToClient($user, $order);

            $notification = trans('user_validation.You have successfully enrolled this package');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->route('user.dashboard')->with($notification);
        }else{
            $notification = trans('user_validation.Payment Faild');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
        }
    }

    public function payWithPayStack(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $paystack = PaystackAndMollie::first();

        $reference = $request->reference;
        $transaction = $request->tnx_id;
        $secret_key = $paystack->paystack_secret_key;
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.paystack.co/transaction/verify/$reference",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_SSL_VERIFYHOST =>0,
            CURLOPT_SSL_VERIFYPEER =>0,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "GET",
            CURLOPT_HTTPHEADER => array(
                "Authorization: Bearer $secret_key",
                "Cache-Control: no-cache",
            ),
        ));
        $response = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        $final_data = json_decode($response);
        if($final_data->status == true) {
            $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
            $user = Auth::guard('web')->user();
            $order = $this->createOrder($user, $pricing_plan, 'Paystack', 'success', $transaction);
            $this->sendMailToClient($user, $order);

            $notification = trans('user_validation.You have successfully enrolled this package');
            return response()->json(['status' => 'success' , 'message' => $notification]);
        }else{
            $notification = trans('user_validation.Payment Faild');
            return response()->json(['status' => 'faild' , 'message' => $notification]);
        }
    }

    public function payWithInstamojo(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
        $user = Auth::guard('web')->user();

        $instamojoPayment = InstamojoPayment::first();
        $price = $pricing_plan->plan_price * $instamojoPayment->currency_rate;
        $price = round($price,2);

        $environment = $instamojoPayment->account_mode;
        $api_key = $instamojoPayment->api_key;
        $auth_token = $instamojoPayment->auth_token;

        if($environment == 'Sandbox') {
            $url = 'https://test.instamojo.com/api/1.1/';
        } else {
            $url = 'https://www.instamojo.com/api/1.1/';
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url.'payment-requests/');
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array("X-Api-Key:$api_key",
                "X-Auth-Token:$auth_token"));
        $payload = Array(
            'purpose' => env("APP_NAME"),
            'amount' => $price,
            'phone' => '918160651749',
            'buyer_name' => Auth::user()->name,
            'redirect_url' => route('response-instamojo'),
            'send_email' => true,
            'webhook' => 'http://www.example.com/webhook/',
            'send_sms' => true,
            'email' => Auth::user()->email,
            'allow_repeated_payments' => false
        );
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
        $response = curl_exec($ch);
        curl_close($ch);
        $response = json_decode($response);
        Session::put('pricing_plan', $pricing_plan);
        return redirect($response->payment_request->longurl);
    }

    public function instamojoResponse(Request $request){

        $pricing_plan = Session::get('pricing_plan');

        $input = $request->all();
        $instamojoPayment = InstamojoPayment::first();
        $environment = $instamojoPayment->account_mode;
        $api_key = $instamojoPayment->api_key;
        $auth_token = $instamojoPayment->auth_token;

        if($environment == 'Sandbox') {
            $url = 'https://test.instamojo.com/api/1.1/';
        } else {
            $url = 'https://www.instamojo.com/api/1.1/';
        }

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url.'payments/'.$request->get('payment_id'));
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_HTTPHEADER,
            array("X-Api-Key:$api_key",
                "X-Auth-Token:$auth_token"));
        $response = curl_exec($ch);
        $err = curl_error($ch);
        curl_close($ch);

        if ($err) {
            $notification = trans('user_validation.Payment Faild');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
        } else {
            $data = json_decode($response);
        }

        if($data->success == true) {
            if($data->payment->status == 'Credit') {

                $user = Auth::guard('web')->user();
                $order = $this->createOrder($user, $pricing_plan, 'Instamojo', 'success', $request->get('payment_id'));

                $this->sendMailToClient($user, $order);

                $notification = trans('user_validation.You have successfully enrolled this package');
                $notification = array('messege'=>$notification,'alert-type'=>'success');
                return redirect()->route('user.dashboard')->with($notification);

            }
        }else{
            $notification = trans('user_validation.Payment Faild');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
        }
    }



    public function createOrder($user, $pricing_plan, $payment_method, $payment_status, $tnx_info){

        if($pricing_plan->expired_time == 'monthly'){
            $expiration_date = date('Y-m-d', strtotime('30 days'));
        }elseif($pricing_plan->expired_time == 'yearly'){
            $expiration_date = date('Y-m-d', strtotime('365 days'));
        }elseif($pricing_plan->expired_time == 'lifetime'){
            $expiration_date = 'lifetime';
        }

        if($payment_status == 'success'){
            Order::where('agent_id', $user->id)->update(['order_status' => 'expired']);
        }

        $order = new Order();
        $order->order_id = substr(rand(0,time()),0,10);
        $order->agent_id = $user->id;
        $order->pricing_plan_id = $pricing_plan->id;
        $order->plan_type = $pricing_plan->plan_type;
        $order->plan_price = $pricing_plan->plan_price;
        $order->plan_name = $pricing_plan->plan_name;
        $order->expired_time = $pricing_plan->expired_time;
        $order->number_of_property = $pricing_plan->number_of_property;
        $order->featured_property = $pricing_plan->featured_property;
        $order->featured_property_qty = $pricing_plan->featured_property_qty;
        $order->top_property = $pricing_plan->top_property;
        $order->top_property_qty = $pricing_plan->top_property_qty;
        $order->urgent_property = $pricing_plan->urgent_property;
        $order->urgent_property_qty = $pricing_plan->urgent_property_qty;
        $order->max_agent_add = $pricing_plan->max_agent_add;
        if($payment_status == 'success'){
            $order->order_status = 'active';
        }else{
            $order->order_status = 'pending';
        }
        $order->payment_status = $payment_status;
        $order->transaction_id = $tnx_info;
        $order->payment_method = $payment_method;
        $order->expiration_date = $expiration_date;
        $order->save();

        $user_properties = Property::where('agent_id', $user->id)->orderBy('id','desc')->get();

        if($payment_status == 'success'){

            if($expiration_date == 'lifetime'){
                Property::where('agent_id', $user->id)->update(['expired_date' => null]);
            }else{
                Property::where('agent_id', $user->id)->update(['expired_date' => $expiration_date]);
            }

            if($user_properties->count() > 0){
                if($order->number_of_property != -1){
                    $i = 0;
                    foreach($user_properties as $index => $user_property){
                        if($i <= $order->number_of_property){
                            $user_property->status = 'enable';
                            $user_property->save();
                        }else{
                            $user_property->status = 'disable';
                            $user_property->save();
                        }
                        $i++;
                    }
                }

                if($order->featured_property == 'enable'){
                    if($order->featured_property_qty != -1){
                        $i = 0;
                        foreach($user_properties as $index => $user_property){
                            if($i <= $order->number_of_property){
                                $user_property->is_featured = 'enable';
                                $user_property->save();
                            }else{
                                $user_property->is_featured = 'disable';
                                $user_property->save();
                            }
                            $i++;
                        }
                    }
                }else{
                    foreach($user_properties as $index => $user_property){
                        $user_property->is_featured = 'disable';
                        $user_property->save();
                    }
                }

                if($order->top_property == 'enable'){
                    if($order->top_property_qty != -1){
                        $i = 0;
                        foreach($user_properties as $index => $user_property){
                            if($i <= $order->number_of_property){
                                $user_property->is_top = 'enable';
                                $user_property->save();
                            }else{
                                $user_property->is_top = 'disable';
                                $user_property->save();
                            }
                            $i++;
                        }
                    }
                }else{
                    foreach($user_properties as $index => $user_property){
                        $user_property->is_top = 'disable';
                        $user_property->save();
                    }
                }

                if($order->urgent_property == 'enable'){
                    if($order->urgent_property_qty != -1){
                        $i = 0;
                        foreach($user_properties as $index => $user_property){
                            if($i <= $order->number_of_property){
                                $user_property->is_urgent = 'enable';
                                $user_property->save();
                            }else{
                                $user_property->is_urgent = 'disable';
                                $user_property->save();
                            }
                            $i++;
                        }
                    }
                }else{
                    foreach($user_properties as $index => $user_property){
                        $user_property->is_urgent = 'disable';
                        $user_property->save();
                    }
                }
            }
        }

        return $order;
    }


    public function sendMailToClient($user, $order){
        MailHelper::setMailConfig();

        $setting = Setting::first();

        $template=EmailTemplate::where('id',6)->first();
        $subject=$template->subject;
        $message=$template->description;
        $message = str_replace('{{user_name}}',$user->name,$message);
        $message = str_replace('{{total_amount}}',$setting->currency_icon.$order->plan_price,$message);
        $message = str_replace('{{payment_method}}',$order->payment_method,$message);
        $message = str_replace('{{payment_status}}',$order->payment_status,$message);
        Mail::to($user->email)->send(new OrderSuccessfully($message,$subject));
    }

}
