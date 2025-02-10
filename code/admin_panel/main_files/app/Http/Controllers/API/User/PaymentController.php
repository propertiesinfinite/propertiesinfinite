<?php

namespace App\Http\Controllers\API\User;

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
        $this->middleware('auth:api')->except('razorpay_webview_payment','webview_success_payment','webview_faild_payment','flutterwave_webview_payment','mollie_webview_payment','paystack_webview_payment','instamojo_webview_payment');
    }

    public function free_enroll($slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $user = Auth::guard('api')->user();

        $free_order = Order::where(['agent_id' => $user->id, 'plan_type' => 'free'])->count();

        if($free_order == 0){
            $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
            $order = $this->createOrder($user, $pricing_plan, 'Free', 'success', 'free_enroll');

            $this->sendMailToClient($user, $order);

            $notification = trans('user_validation.You have successfully enrolled this package');
            return response()->json(['message' => $notification]);

        }else{
            $notification = trans('user_validation.You have already enrolled trail version');
            return response()->json(['message' => $notification],403);
        }

    }


    public function payment($slug){

        $setting = Setting::first();

        $user = Auth::guard('api')->user();

        $user = User::select('id','name','email','image','phone','address','status','about_me','facebook','twitter','linkedin','instagram','designation')->where('id', $user->id)->first();

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

        return response()->json([
            'user' => $user,
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
            return response()->json(['message' => $notification],403);
        }

        $rules = [
            'tnx_info'=>'required',
        ];
        $customMessages = [
            'tnx_info.required' => trans('user_validation.Transaction is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
        $user = Auth::guard('api')->user();
        $order = $this->createOrder($user, $pricing_plan, 'Bank payment', 'pending', $request->tnx_info);
        $this->sendMailToClient($user, $order);

        $notification = trans('user_validation.Your order has been placed. please wait for admin payment approval');
        return response()->json(['message' => $notification]);
    }

    public function payWithStripe(Request $request, $slug){

        $rules = [
            'card_number'=>'required',
            'year'=>'required',
            'month'=>'required',
            'cvc'=>'required',
        ];
        $customMessages = [
            'card_number.required' => trans('user_validation.Card number is required'),
            'year.required' => trans('user_validation.Year is required'),
            'month.required' => trans('user_validation.Month is required'),
            'cvc.required' => trans('user_validation.Cvv is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

        $user = Auth::guard('api')->user();

        $stripe = StripePayment::first();
        $payableAmount = round($pricing_plan->plan_price * $stripe->currency_rate,2);
        Stripe\Stripe::setApiKey($stripe->stripe_secret);

        try{
            $token = Stripe\Token::create([
                'card' => [
                    'number' => $request->card_number,
                    'exp_month' => $request->month,
                    'exp_year' => $request->year,
                    'cvc' => $request->cvc,
                ],
            ]);

            if (!isset($token['id'])) {
                return response()->json(['error' => trans('user_validation.Payment Faild')],403);
            }

            $result = Stripe\Charge::create ([
                'card' => $token['id'],
                "amount" => $payableAmount * 100,
                "currency" => $stripe->currency_code,
                "description" => env('APP_NAME')
            ]);

        }catch (Exception $e) {
            return response()->json(['message' => trans('user_validation.Please provide valid card information')],403);
        }



        $order = $this->createOrder($user, $pricing_plan, 'Stripe', 'success', $result->balance_transaction);

        $this->sendMailToClient($user, $order);

        $notification = trans('user_validation.You have successfully enrolled this package');
        return response()->json(['message' => $notification]);

    }

    public function razorpay_webview(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $razorpay = RazorpayPayment::first();

        $user = Auth::guard('api')->user();
        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

        Session::put('auth_user', $user);

        return view('razorpay_webview', compact('razorpay','user','pricing_plan'));
    }

    public function razorpay_webview_payment(Request $request, $slug){
        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $user = Session::get('auth_user');

        $razorpay = RazorpayPayment::first();
        $input = $request->all();
        $api = new Api($razorpay->key,$razorpay->secret_key);
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        if(count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $response = $api->payment->fetch($input['razorpay_payment_id'])->capture(array('amount'=>$payment['amount']));
                $payId = $response->id;

                $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

                $order = $this->createOrder($user, $pricing_plan, 'Razorpay', 'success', $payId);

                $this->sendMailToClient($user, $order);

                return redirect()->route('webview-success-payment');

            }catch (Exception $e) {
                return redirect()->route('webview-faild-payment');
            }
        }else{
            return redirect()->route('webview-faild-payment');
        }
    }

    public function webview_success_payment(){
        $notification = trans('user_validation.You have successfully enrolled this package');
        return response()->json(['message' => $notification]);
    }

    public function webview_faild_payment(){
        $notification = trans('user_validation.Payment Faild');
        return response()->json(['message' => $notification],403);
    }


    public function flutterwave_webview(Request $request, $slug){
        $flutterwave = Flutterwave::first();

        $user = Auth::guard('api')->user();
        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

        Session::put('auth_user', $user);

        return view('flutterwave_webview', compact('flutterwave','user','pricing_plan'));
    }

    public function flutterwave_webview_payment(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $user = Session::get('auth_user');

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
            $order = $this->createOrder($user, $pricing_plan, 'Flutterwave', 'success', $tnx_id);
            $this->sendMailToClient($user, $order);

            $notification = trans('user_validation.You have successfully enrolled this package');
            return response()->json(['status' => 'success' , 'message' => $notification]);
        }else{
            $notification = trans('user_validation.Payment Faild');
            return response()->json(['status' => 'faild' , 'message' => $notification]);
        }
    }

    public function mollie_webview(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
        $user = Auth::guard('api')->user();

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
            'redirectUrl' => route('mollie-webview-payment'),
        ]);

        $payment = Mollie::api()->payments()->get($payment->id);
        session()->put('payment_id',$payment->id);
        session()->put('pricing_plan',$pricing_plan);
        session()->put('auth_user',$user);

        return redirect($payment->getCheckoutUrl(), 303);
    }

    public function mollie_webview_payment(Request $request){
        $pricing_plan = Session::get('pricing_plan');
        $user = Session::get('auth_user');
        $mollie = PaystackAndMollie::first();
        $mollie_api_key = $mollie->mollie_key;
        Mollie::api()->setApiKey($mollie_api_key);
        $payment = Mollie::api()->payments->get(session()->get('payment_id'));
        if ($payment->isPaid()){
            $order = $this->createOrder($user, $pricing_plan, 'Mollie', 'success', session()->get('payment_id'));
            $this->sendMailToClient($user, $order);

            return redirect()->route('webview-success-payment');
        }else{
            return redirect()->route('webview-faild-payment');
        }
    }

    public function paystack_webview(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $mollie = PaystackAndMollie::first();
        $paystack = $mollie;

        $user = Auth::guard('api')->user();
        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

        Session::put('auth_user', $user);

        return view('paystack_webview', compact('paystack','user','pricing_plan'));
    }

    public function paystack_webview_payment(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $user = Session::get('auth_user');
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
            $order = $this->createOrder($user, $pricing_plan, 'Paystack', 'success', $transaction);
            $this->sendMailToClient($user, $order);

            $notification = trans('user_validation.You have successfully enrolled this package');
            return response()->json(['status' => 'success' , 'message' => $notification]);
        }else{
            $notification = trans('user_validation.Payment Faild');
            return response()->json(['status' => 'faild' , 'message' => $notification]);
        }
    }

    public function instamojo_webview(Request $request, $slug){

        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            return response()->json(['message' => $notification],403);
        }

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();
        $user = Auth::guard('api')->user();

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
            'redirect_url' => route('instamojo-webview-payment'),
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
        Session::put('auth_user', $user);
        return redirect($response->payment_request->longurl);
    }

    public function instamojo_webview_payment(Request $request){

        $pricing_plan = Session::get('pricing_plan');
        $user = Session::get('auth_user');

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
            return redirect()->route('webview-faild-payment');
        } else {
            $data = json_decode($response);
        }

        if($data->success == true) {
            if($data->payment->status == 'Credit') {

                $order = $this->createOrder($user, $pricing_plan, 'Instamojo', 'success', $request->get('payment_id'));

                $this->sendMailToClient($user, $order);

                return redirect()->route('webview-success-payment');
            }
        }else{
            return redirect()->route('webview-faild-payment');
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
