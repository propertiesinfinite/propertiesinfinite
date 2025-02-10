<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentExecution;

use App\Mail\OrderSuccessfully;
use App\Helpers\MailHelper;
use App\Models\EmailTemplate;
use App\Models\PaypalPayment;
use App\Models\BreadcrumbImage;
use App\Models\Order;
use App\Models\Setting;
use App\Models\PricingPlan;
use App\Models\User;

use Str;
use Cart;
use Mail;
use Session;
use Auth;

class PaypalController extends Controller
{
    private $apiContext;
    public function __construct()
    {
        $account = PaypalPayment::first();
        $paypal_conf = \Config::get('paypal');
        $this->apiContext = new ApiContext(new OAuthTokenCredential(
            $account->client_id,
            $account->secret_id,
            )
        );

        $setting=array(
            'mode' => $account->account_mode,
            'http.ConnectionTimeOut' => 30,
            'log.LogEnabled' => true,
            'log.FileName' => storage_path() . '/logs/paypal.log',
            'log.LogLevel' => 'ERROR'
        );
        $this->apiContext->setConfig($setting);
    }


    public function payWithPaypal($slug){
        if(env('APP_MODE') == 'DEMO'){
            $notification = trans('user_validation.This Is Demo Version. You Can Not Change Anything');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $pricing_plan = PricingPlan::where(['plan_slug' => $slug])->first();

        $user = Auth::guard('web')->user();

        $paypalSetting = PaypalPayment::first();
        $payableAmount = round($pricing_plan->plan_price * $paypalSetting->currency_rate,2);

        $name = env('APP_NAME');

        // set payer
        $payer = new Payer();
        $payer->setPaymentMethod("paypal");

        // set amount total
        $amount = new Amount();
        $amount->setCurrency($paypalSetting->currency_code)
            ->setTotal($payableAmount);

        // transaction
        $transaction = new Transaction();
        $transaction->setAmount($amount)
            ->setDescription(env('APP_NAME'));

        // redirect url
        $redirectUrls = new RedirectUrls();

        $root_url=url('/');
        $redirectUrls->setReturnUrl(route('paypal-payment-success'))
            ->setCancelUrl(route('paypal-payment-cancled'));

        // payment
        $payment = new Payment();
        $payment->setIntent("sale")
            ->setPayer($payer)
            ->setRedirectUrls($redirectUrls)
            ->setTransactions(array($transaction));
        try {
            $payment->create($this->apiContext);
        } catch (\PayPal\Exception\PPConnectionException $ex) {

            $notification = trans('user_validation.Payment Faild');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
        }

        // get paymentlink
        $approvalUrl = $payment->getApprovalLink();

        Session::put('pricing_plan', $pricing_plan);

        return redirect($approvalUrl);
    }

    public function paypalPaymentSuccess(Request $request){

        $pricing_plan = Session::get('pricing_plan');

        if (empty($request->get('PayerID')) || empty($request->get('token'))) {
            $notification = trans('user_validation.Payment Faild');
            $notification = array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
        }

        $payment_id=$request->get('paymentId');
        $payment = Payment::get($payment_id, $this->apiContext);
        $execution = new PaymentExecution();
        $execution->setPayerId($request->get('PayerID'));
        /**Execute the payment **/
        $result = $payment->execute($execution, $this->apiContext);

        if ($result->getState() == 'approved') {

            $user = Auth::guard('web')->user();

            $order = $this->createOrder($user, $pricing_plan, 'Paypal', 'success', $payment_id);

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

    public function paypalPaymentCancled(){

        $pricing_plan = Session::get('pricing_plan');

        $notification = trans('user_validation.Payment Faild');
        $notification = array('messege'=>$notification,'alert-type'=>'error');
        return redirect()->route('payment', $pricing_plan->plan_slug)->with($notification);
    }


    public function createOrder($user, $pricing_plan, $payment_method, $payment_status, $tnx_info){

        if($pricing_plan->expired_time == 'monthly'){
            $expiration_date = date('Y-m-d', strtotime('30 days'));
        }elseif($pricing_plan->expired_time == 'yearly'){
            $expiration_date = date('Y-m-d', strtotime('365 days'));
        }elseif($pricing_plan->expired_time == 'lifetime'){
            $expiration_date = 'lifetime';
        }

        Order::where('agent_id', $user->id)->update(['order_status' => 'expired']);

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
        $order->order_status = 'active';
        $order->payment_status = $payment_status;
        $order->transaction_id = $tnx_info;
        $order->payment_method = $payment_method;
        $order->expiration_date = $expiration_date;
        $order->save();

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
