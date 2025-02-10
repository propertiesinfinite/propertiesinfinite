<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\PricingPlan;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class PricingPlanController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $items = PricingPlan::orderBy('serial', 'asc')->get();

        return view('admin.pricing_plan',compact('items'));
    }

    public function create(){
        return view('admin.pricing_plan_create');
    }

    public function store(Request $request)
    {
        $rules = [
            'plan_type'=>'required',
            'plan_price'=>'required|numeric',
            'plan_name'=>'required',
            'expired_time'=>'required',
            'number_of_property'=>'required|numeric',
            'featured_property'=>'required',
            'featured_property_qty'=> $request->featured_property == 'enable' ?  'required|numeric' : '',
            'top_property'=>'required',
            'top_property_qty'=> $request->top_property_qty == 'enable' ?  'required|numeric' : '',
            'urgent_property'=>'required',
            'urgent_property_qty'=> $request->urgent_property_qty == 'enable' ?  'required|numeric' : '',
            'serial'=>'required',
            'max_agent_add' => 'required',
            'status'=>'required',
        ];
        $customMessages = [
            'plan_type.required' => trans('admin_validation.Plan type is required'),
            'plan_price.required' => trans('admin_validation.Plan price is required'),
            'plan_price.numeric' => trans('admin_validation.Plan price should be numeric'),
            'plan_name.required' => trans('admin_validation.Plan name is required'),
            'expired_time.required' => trans('admin_validation.Expiration is required'),
            'number_of_property.required' => trans('admin_validation.Number of property is required'),
            'number_of_property.numeric' => trans('admin_validation.Number of property should be numeric'),
            'featured_property.required' => trans('admin_validation.Featured property is required'),
            'featured_property_qty.required' => trans('admin_validation.Number of featured property is required'),
            'featured_property_qty.numeric' => trans('admin_validation.Number of featured property should be numeric'),
            'top_property.required' => trans('admin_validation.Top property is required'),
            'top_property_qty.required' => trans('admin_validation.Number of top property is required'),
            'top_property_qty.numeric' => trans('admin_validation.Number of top property should be numeric'),
            'urgent_property.required' => trans('admin_validation.Urgent property is required'),
            'urgent_property_qty.required' => trans('admin_validation.Number of urgent property is required'),
            'urgent_property_qty.numeric' => trans('admin_validation.Number of urgent property should be numeric'),
            'serial.required' => trans('admin_validation.Serial is required'),
            'status.required' => trans('admin_validation.Status is required'),
            'max_agent_add.required' => trans('admin_validation.Maximum agent adding is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $item = new PricingPlan();
        $item->plan_slug = rand(10000000,99999999);
        $item->plan_type = $request->plan_type;
        $item->plan_price = $request->plan_price;
        $item->plan_name = $request->plan_name;
        $item->expired_time = $request->expired_time;
        $item->number_of_property = $request->number_of_property;
        $item->featured_property = $request->featured_property;
        $item->featured_property_qty = $request->featured_property_qty ? $request->featured_property_qty : 0;
        $item->top_property = $request->top_property;
        $item->top_property_qty = $request->top_property_qty ? $request->top_property_qty : 0;
        $item->urgent_property = $request->urgent_property;
        $item->urgent_property_qty = $request->urgent_property_qty ? $request->urgent_property_qty : 0;
        $item->serial = $request->serial;
        $item->max_agent_add = $request->max_agent_add;
        $item->status = $request->status;
        $item->save();

        $notification = trans('admin_validation.Created Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.pricing-plan.index')->with($notification);
    }

    public function edit($id){

        $item = PricingPlan::find($id);

        return view('admin.pricing_plan_edit', compact('item'));
    }

    public function update(Request $request, $id)
    {
        $rules = [
            'plan_type'=>'required',
            'plan_price'=>'required|numeric',
            'plan_name'=>'required',
            'expired_time'=>'required',
            'number_of_property'=>'required|numeric',
            'featured_property'=>'required',
            'featured_property_qty'=> $request->featured_property == 'enable' ?  'required|numeric' : '',
            'top_property'=>'required',
            'top_property_qty'=> $request->top_property_qty == 'enable' ?  'required|numeric' : '',
            'urgent_property'=>'required',
            'urgent_property_qty'=> $request->urgent_property_qty == 'enable' ?  'required|numeric' : '',
            'serial'=>'required',
            'max_agent_add'=>'required',
            'status'=>'required',

        ];
        $customMessages = [
            'plan_type.required' => trans('admin_validation.Plan type is required'),
            'plan_price.required' => trans('admin_validation.Plan price is required'),
            'plan_price.numeric' => trans('admin_validation.Plan price should be numeric'),
            'plan_name.required' => trans('admin_validation.Plan name is required'),
            'expired_time.required' => trans('admin_validation.Expiration is required'),
            'number_of_property.required' => trans('admin_validation.Number of property is required'),
            'number_of_property.numeric' => trans('admin_validation.Number of property should be numeric'),
            'featured_property.required' => trans('admin_validation.Featured property is required'),
            'featured_property_qty.required' => trans('admin_validation.Number of featured property is required'),
            'featured_property_qty.numeric' => trans('admin_validation.Number of featured property should be numeric'),
            'top_property.required' => trans('admin_validation.Top property is required'),
            'top_property_qty.required' => trans('admin_validation.Number of top property is required'),
            'top_property_qty.numeric' => trans('admin_validation.Number of top property should be numeric'),
            'urgent_property.required' => trans('admin_validation.Urgent property is required'),
            'urgent_property_qty.required' => trans('admin_validation.Number of urgent property is required'),
            'urgent_property_qty.numeric' => trans('admin_validation.Number of urgent property should be numeric'),
            'serial.required' => trans('admin_validation.Serial is required'),
            'status.required' => trans('admin_validation.Status is required'),
            'max_agent_add.required' => trans('admin_validation.Maximum agent adding is required'),

        ];
        $this->validate($request, $rules,$customMessages);


        $item = PricingPlan::find($id);
        $item->plan_type = $request->plan_type;
        $item->plan_price = $request->plan_price;
        $item->plan_name = $request->plan_name;
        $item->expired_time = $request->expired_time;
        $item->number_of_property = $request->number_of_property;
        $item->featured_property = $request->featured_property;
        $item->featured_property_qty = $request->featured_property_qty ? $request->featured_property_qty : 0;
        $item->top_property = $request->top_property;
        $item->top_property_qty = $request->top_property_qty ? $request->top_property_qty : 0;
        $item->urgent_property = $request->urgent_property;
        $item->urgent_property_qty = $request->urgent_property_qty ? $request->urgent_property_qty : 0;
        $item->serial = $request->serial;
        $item->max_agent_add = $request->max_agent_add;
        $item->status = $request->status;
        $item->save();

        if($request->apply_to_existing_user){
            Order::where('pricing_plan_id', $item->id)->update(['max_agent_add' => $request->max_agent_add]);
        }

        $notification = trans('admin_validation.Updated Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.pricing-plan.index')->with($notification);
    }

    public function destroy($id)
    {
        $item = PricingPlan::find($id);
        $item->delete();

        $notification = trans('admin_validation.Delete Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
}
