<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Setting;
use App\Models\User;
use App\Models\Blog;
use App\Models\Subscriber;
use App\Models\Property;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function dashobard(){
        $todayOrders = Order::orderBy('id','desc')->whereDay('created_at', now()->day)->get();
        $today_total_order = $todayOrders->count();
        $today_total_earning = $todayOrders->where('payment_status','success')->sum('plan_price');
        $today_pending_earning = $todayOrders->where('payment_status','pending')->sum('plan_price');
        $today_users = User::whereDay('created_at', now()->day)->count();

        $monthlyOrders = Order::orderBy('id','desc')->whereMonth('created_at', now()->month)->get();
        $monthly_total_order = $monthlyOrders->count();
        $monthly_total_earning = $monthlyOrders->where('payment_status','success')->sum('plan_price');
        $monthly_pending_earning = $monthlyOrders->where('payment_status','pending')->sum('plan_price');
        $monthly_users = User::whereMonth('created_at', now()->month)->count();

        $yearlyOrders = Order::orderBy('id','desc')->whereYear('created_at', now()->year)->get();
        $yearly_total_order = $yearlyOrders->count();
        $yearly_total_earning = $yearlyOrders->where('payment_status','success')->sum('plan_price');
        $yearly_pending_earning = $yearlyOrders->where('payment_status','success')->sum('plan_price');
        $yearly_users = User::whereYear('created_at', now()->year)->count();

        $totalOrders = Order::orderBy('id','desc')->get();
        $total_total_order = $totalOrders->count();
        $total_earning = $totalOrders->where('payment_status','success')->sum('plan_price');
        $total_pending_earning = $totalOrders->where('payment_status','pending')->sum('plan_price');


        $total_users = User::count();
        $total_blog = Blog::count();
        $total_subscriber = Subscriber::where('is_verified',1)->count();


        $agent_order = Order::groupBy('agent_id')->select('agent_id')->get();
        $agent_arr = array();

        foreach($agent_order as $agent){
            $agent_arr[] = $agent->agent_id;
        }

        $total_agent = User::whereIn('id', $agent_arr)->where('status', 1)->orderBy('id','desc')->count();
        $total_user = User::orderBy('id','desc')->where('status',1)->count();

        $total_own_property = Property::where('agent_id', 0)->count();
        $total_property = Property::count();
        $total_publish_property = Property::where('status', 'enable')
                                    ->where('approve_by_admin', 'approved')
                                    ->where(function ($query) {
                                        $query->where('expired_date', null)
                                            ->orWhere('expired_date', '>=', date('Y-m-d'));
                                    })
                                    ->count();

        $awaiting_property = Property::where('approve_by_admin', 'pending')->count();
        $reject_property = Property::where('approve_by_admin', 'reject')->count();


        return view('admin.dashboard')->with([
            'today_total_order' => $today_total_order,
            'today_total_earning' => $today_total_earning,
            'today_pending_earning' => $today_pending_earning,
            'today_users' => $today_users,
            'monthly_total_order' => $monthly_total_order,
            'monthly_total_earning' => $monthly_total_earning,
            'monthly_pending_earning' => $monthly_pending_earning,
            'monthly_users' => $monthly_users,
            'yearly_total_order' => $yearly_total_order,
            'yearly_total_earning' => $yearly_total_earning,
            'yearly_pending_earning' => $yearly_pending_earning,
            'yearly_users' => $yearly_users,
            'total_total_order' => $total_total_order,
            'total_earning' => $total_earning,
            'total_pending_earning' => $total_pending_earning,
            'total_users' => $total_users,
            'total_own_property' => $total_own_property,
            'total_property' => $total_property,
            'total_publish_property' => $total_publish_property,
            'awaiting_property' => $awaiting_property,
            'reject_property' => $reject_property,
            'total_agent' => $total_agent,
            'total_user' => $total_user,
        ]);

    }

}
