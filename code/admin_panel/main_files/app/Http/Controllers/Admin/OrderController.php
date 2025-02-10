<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Property;

use App\Models\PricingPlan;

use Illuminate\Pagination\Paginator;


class OrderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function assign_pricing_plan(){
        $items = PricingPlan::where('status', 'enable')->orderBy('serial', 'asc')->get();
        $agents = User::where('status', 1)->where('owner_id', 0)->get();

        return view('admin.assign_pricing_plan', compact('items', 'agents'));
    }

    public function store_assign_pricing_plan(Request $request){

        $rules = [
            'plan_id'=>'required',
            'agent_id'=>'required',
        ];
        $customMessages = [
            'plan_id.required' => trans('admin_validation.Pricing plan is required'),
            'agent_id.required' => trans('admin_validation.Agent is required'),

        ];
        $this->validate($request, $rules,$customMessages);

        $item = PricingPlan::find($request->plan_id);
        $agent = User::find($request->agent_id);

        if($item->expired_time == 'monthly'){
            $expiration_date = date('Y-m-d', strtotime('30 days'));
        }elseif($item->expired_time == 'yearly'){
            $expiration_date = date('Y-m-d', strtotime('365 days'));
        }elseif($item->expired_time == 'lifetime'){
            $expiration_date = 'lifetime';
        }

        Order::where('agent_id', $agent->id)->update(['order_status' => 'expired']);

        $order = new Order();
        $order->order_id = substr(rand(0,time()),0,10);
        $order->agent_id = $request->agent_id;
        $order->pricing_plan_id = $request->plan_id;
        $order->plan_type = $item->plan_type;
        $order->plan_price = $item->plan_price;
        $order->plan_name = $item->plan_name;
        $order->expired_time = $item->expired_time;
        $order->number_of_property = $item->number_of_property;
        $order->featured_property = $item->featured_property;
        $order->featured_property_qty = $item->featured_property_qty;
        $order->top_property = $item->top_property;
        $order->top_property_qty = $item->top_property_qty;
        $order->urgent_property = $item->urgent_property;
        $order->urgent_property_qty = $item->urgent_property_qty;
        $order->max_agent_add = $item->max_agent_add;
        $order->order_status = 'active';
        $order->payment_status = 'success';
        $order->transaction_id = 'hand_cash';
        $order->payment_method = 'hand_cash';
        $order->expiration_date = $expiration_date;
        $order->save();

        $user_properties = Property::where('agent_id', $agent->id)->orderBy('id','desc')->get();

        if($expiration_date == 'lifetime'){
            Property::where('agent_id', $agent->id)->update(['expired_date' => null]);
        }else{
            Property::where('agent_id', $agent->id)->update(['expired_date' => $expiration_date]);
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

        $notification = trans('admin_validation.Assign successful');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }


    public function index(Request $request){

        if($request->agent_id){
            $orders = Order::with('agent')->orderBy('id','desc')->where('agent_id', $request->agent_id)->get();
        }else{
            $orders = Order::with('agent')->orderBy('id','desc')->get();
        }


        $title = trans('admin_validation.Purchase history');

        return view('admin.order', compact('orders', 'title'));
    }

    public function pending_payment(Request $request){
        $orders = Order::with('agent')->orderBy('id','desc')->where('payment_status', 'pending')->get();

        $title = trans('admin_validation.Pending Payment');

        return view('admin.order', compact('orders', 'title'));
    }




    public function show($id){
        $order = Order::with('agent')->where('order_id',$id)->first();


        return view('admin.show_order',compact('order'));
    }

    public function destroy($id){

        $order = Order::find($id);
        $order->delete();

        $notification = trans('admin_validation.Delete successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.purchase-history')->with($notification);
    }

    public function payment_approved($id){
        $order = Order::find($id);

        Order::where('agent_id', $order->agent_id)->update(['order_status' => 'expired']);

        $order->order_status = 'active';
        $order->payment_status = 'success';
        $order->save();

        $user_properties = Property::where('agent_id', $order->agent_id)->orderBy('id','desc')->get();

        if($order->expiration_date == 'lifetime'){
            Property::where('agent_id', $order->agent_id)->update(['expired_date' => null]);
        }else{
            Property::where('agent_id', $order->agent_id)->update(['expired_date' => $order->expiration_date]);
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

        $notification= trans('admin_validation.Approved Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

}
