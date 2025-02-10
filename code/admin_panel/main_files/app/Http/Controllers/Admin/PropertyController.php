<?php

namespace App\Http\Controllers\Admin;

use Auth;
use File;
use Image;
use App\Models\City;
use App\Models\User;
use App\Models\Order;
use App\Models\Review;
use App\Models\Aminity;
use App\Models\Booking;
use App\Models\Compare;
use App\Models\Country;
use App\Models\Setting;
use App\Models\Category;
use App\Models\Property;
use App\Models\Wishlist;
use App\Models\PropertyPlan;
use Illuminate\Http\Request;
use App\Models\PropertySlider;
use App\Models\NearestLocation;
use App\Models\PropertyAminity;
use App\Http\Controllers\Controller;
use App\Models\AdditionalInformation;
use App\Models\PropertyNearestLocation;


class PropertyController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin')->except('check_slug');
    }

    public function index(){

        $properties = Property::with('property_type')->orderBy('id','desc')->where('agent_id', 0)->get();

        return view('admin.own_property', compact('properties'));
    }

    public function agent_property(Request $request){

        if($request->agent_id){
            $properties = Property::with('property_type')->orderBy('id','desc')->where('agent_id', $request->agent_id);

            if($request->type){
                $properties = $properties->where('status', $request->type);
            }
            $properties = $properties->get();
        }else{
            $properties = Property::with('property_type')->orderBy('id','desc')->where('agent_id', '!=', 0)->get();
        }

        return view('admin.agent_properties', compact('properties'));
    }

    public function agent_pending_property(Request $request){

        if($request->agent_id){
            $properties = Property::with('property_type')->orderBy('id','desc')->where('agent_id', $request->agent_id)->where('approve_by_admin', 'pending')->get();
        }else{
            $properties = Property::with('property_type')->orderBy('id','desc')->where('agent_id', '!=', 0)->where('approve_by_admin', 'pending')->get();
        }

        return view('admin.agent_pending_property', compact('properties'));
    }

    public function agent_reject_property(Request $request){

        if($request->agent_id){
            $properties = Property::with('property_type')->orderBy('id','desc')->where('agent_id', $request->agent_id)->where('approve_by_admin', 'reject')->get();
        }else{
            $properties = Property::with('property_type')->orderBy('id','desc')->where('agent_id', '!=', 0)->where('approve_by_admin', 'reject')->get();
        }

        return view('admin.agent_reject_property', compact('properties'));
    }




    public function create(){

        $types = Category::where('status', 1)->get();
        $cities = City::all();
        $aminities = Aminity::all();
        $nearest_locations = NearestLocation::orderBy('id', 'desc')->where('status', 1)->get();
        $countries = Country::orderBy('id', 'desc')->get();
        $setting = Setting::first();

        $agent_order = Order::groupBy('agent_id')->select('agent_id')->get();
        $agent_arr = array();

        foreach($agent_order as $agent){
            $agent_arr[] = $agent->agent_id;
        }

        $agents = User::whereIn('id', $agent_arr)->select('id','name','email','phone')->get();

        return view('admin.property_create')->with([
            'types' => $types,
            'cities' => $cities,
            'aminities' => $aminities,
            'nearest_locations' => $nearest_locations,
            'agents' => $agents,
            'countries' => $countries,
            'setting' => $setting,
        ]);
    }

    public function store(Request $request){

        if($request->owner_id != 0){
            $agent_id = $request->owner_id;
            $agent_order = Order::where('agent_id', $agent_id)->orderBy('id','desc')->first();

            if($agent_order){

                $available = 'disable';

                $expiration_date = $agent_order->expiration_date;

                if($expiration_date != 'lifetime'){
                    if(date('Y-m-d') > $expiration_date){
                        $notification = trans('admin_validation.Pricing plan date is expired');
                        $notification = array('messege'=>$notification,'alert-type'=>'error');
                        return redirect()->back()->with($notification);
                    }
                }

                $number_of_property = $agent_order->number_of_property;

                if($number_of_property == -1){
                    $available = 'enable';
                }else{
                    $property_count = Property::where('agent_id', $agent_id)->count();
                    if($property_count < $number_of_property){
                        $available = 'enable';
                    }
                }

                if($available == 'disable'){
                    $notification = trans('admin_validation.You can not add property more than limit quantity');
                    $notification = array('messege'=>$notification,'alert-type'=>'error');
                    return redirect()->back()->with($notification);
                }

            }else{
                $notification = trans('admin_validation.Agent does not have any pricing plan');
                $notification = array('messege'=>$notification,'alert-type'=>'error');
                return redirect()->back()->with($notification);
            }
        }

        $live_map = Setting::first()->live_map;

        $rules = [
            'title'=>'required|unique:properties',
            'slug'=>'required|unique:properties',
            'property_type_id'=>'required',
            'purpose'=> 'required',
            'rent_period'=> $request->purpose == 'rent' ? 'required' : '',
            'price'=>'required',
            'description'=>'required',
            'city_id'=>'required',
            'country_id'=>'required',
            'address'=>'required',
            'address_description'=>'required',
            'google_map'=> $live_map == 'no' ? 'required' : '',
            'total_area'=>'required',
            'total_unit'=>'required',
            'total_bedroom'=>'required',
            'total_bathroom'=>'required',
            'total_garage'=>'required',
            'total_kitchen'=>'required',
            'thumbnail_image'=>'required',
            'lat' => $live_map == 'yes' ? 'required' : '',
            'lng' => $live_map == 'yes' ? 'required' : ''
        ];
        $customMessages = [
            'title.required' => trans('admin_validation.Title is required'),
            'title.unique' => trans('admin_validation.Title already exist'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'property_type_id.required' => trans('admin_validation.Property type is required'),
            'purpose.required' => trans('admin_validation.Purpose is required'),
            'rent_period.required' => trans('admin_validation.Rent period is required'),
            'price.required' => trans('admin_validation.Price is required'),
            'description.required' => trans('admin_validation.Description is required'),
            'city_id.required' => trans('admin_validation.City is required'),
            'country_id.required' => trans('admin_validation.Country is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'address_description.required' => trans('admin_validation.Address details is required'),
            'google_map.required' => trans('admin_validation.Google map is required'),
            'total_area.required' => trans('admin_validation.Total area is required'),
            'total_unit.required' => trans('admin_validation.Total unit is required'),
            'total_bedroom.required' => trans('admin_validation.Total bedroom is required'),
            'total_bathroom.required' => trans('admin_validation.Total bathroom is required'),
            'total_garage.required' => trans('admin_validation.Total garage is required'),
            'total_kitchen.required' => trans('admin_validation.Total kitchen is required'),
            'thumbnail_image.required' => trans('admin_validation.Thumbnail image is required'),
            'lat.required' => trans('admin_validation.The latitude is required'),
            'lng.required' => trans('admin_validation.The longitude is required'),
        ];

        $this->validate($request, $rules,$customMessages);

        $property = new Property();
        $property->agent_id = $request->owner_id;
        $property->title = $request->title;
        $property->slug = $request->slug;
        $property->property_type_id = $request->property_type_id;
        $property->purpose = $request->purpose;
        $property->rent_period = $request->purpose == 'rent' ? $request->rent_period : '';
        $property->price = $request->price;
        $property->description = $request->description;

        $property->total_area = $request->total_area;
        $property->total_unit = $request->total_unit;
        $property->total_bedroom = $request->total_bedroom;
        $property->total_bathroom = $request->total_bathroom;
        $property->total_garage = $request->total_garage;
        $property->total_kitchen = $request->total_kitchen;
        $property->total_bathroom = $request->total_bathroom;

        $property->city_id = $request->city_id;
        $property->country_id = $request->country_id;
        $property->address = $request->address;
        $property->address_description = $request->address_description;
        $property->google_map = $request->google_map;
        $property->lat = $request->lat;
        $property->lon = $request->lng;

        $property->video_id = $request->video_id;
        $property->video_description = $request->video_description;

        if($request->thumbnail_image){
            $extention = $request->thumbnail_image->getClientOriginalExtension();
            $image_name = 'property-thumb'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->thumbnail_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $property->thumbnail_image = $image_name;
        }

        if($request->video_thumbnail){
            $extention = $request->video_thumbnail->getClientOriginalExtension();
            $image_name = 'video-thumb'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->video_thumbnail)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $property->video_thumbnail = $image_name;
        }


        $property->seo_title = $request->seo_title ? $request->seo_title : $request->title;
        $property->seo_meta_description = $request->seo_meta_description ? $request->seo_meta_description : $request->title;
        $property->status = $request->status ? 'enable' : 'disable';
        $property->is_featured = $request->is_featured ? 'enable' : 'disable';
        $property->is_top = $request->is_top ? 'enable' : 'disable';
        $property->is_urgent = $request->is_urgent ? 'enable' : 'disable';
        $property->approve_by_admin = 'approved';
        if($request->owner_id != 0){
            if($agent_order->expiration_date == 'lifetime'){
                $property->expired_date = null;
            }else{
                $property->expired_date = $agent_order->expiration_date;
            }
        }
        $property->date_from = $request->date_form;
        $property->date_to = $request->date_to;
        $property->time_from = $request->time_form;
        $property->time_to = $request->time_to;
        $property->save();

        if($request->aminities){
            foreach($request->aminities as $aminity){
                $item = new PropertyAminity();
                $item->aminity_id = $aminity;
                $item->property_id = $property->id;
                $item->save();
            }
        }

        if($request->slider_images){
            foreach($request->slider_images as $index => $image){
                $extention = $image->getClientOriginalExtension();
                $image_name = 'Property-slider'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                $image_name = 'uploads/custom-images/'.$image_name;
                Image::make($image)
                    ->encode('webp', 80)
                    ->save(public_path().'/'.$image_name);

                $slider = new PropertySlider();
                $slider->property_id = $property->id;
                $slider->image = $image_name;
                $slider->save();
            }
        }

        if($request->nearest_locations && $request->distances){
            foreach($request->nearest_locations as $index => $nearest_location){
                if($request->nearest_locations[$index] != '' && $request->distances[$index] != ''){
                    $new_loc = new PropertyNearestLocation();
                    $new_loc->property_id = $property->id;
                    $new_loc->nearest_location_id = $request->nearest_locations[$index];
                    $new_loc->distance = $request->distances[$index];
                    $new_loc->save();
                }
            }
        }

        if($request->add_keys && $request->add_values){
            foreach($request->add_keys as $index => $add_key){
                if($request->add_keys[$index] != '' && $request->add_values[$index] != ''){
                    $new_loc = new AdditionalInformation();
                    $new_loc->property_id = $property->id;
                    $new_loc->add_key = $request->add_keys[$index];
                    $new_loc->add_value = $request->add_values[$index];
                    $new_loc->save();
                }
            }
        }

        if($request->plan_images && $request->plan_titles && $request->plan_descriptions){
            foreach($request->plan_images as $index => $image){
                if($request->plan_images[$index] && $request->plan_titles[$index] && $request->plan_descriptions[$index]){
                    $extention = $image->getClientOriginalExtension();
                    $image_name = 'Property-plan'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                    $image_name = 'uploads/custom-images/'.$image_name;
                    Image::make($image)
                        ->encode('webp', 80)
                        ->save(public_path().'/'.$image_name);

                    $plan = new PropertyPlan();
                    $plan->property_id = $property->id;
                    $plan->image = $image_name;
                    $plan->title = $request->plan_titles[$index];
                    $plan->description = $request->plan_descriptions[$index];
                    $plan->save();
                }
            }
        }

        $notification = trans('admin_validation.Created succssfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function edit($id){

        $property = Property::find($id);

        $types = Category::where('status', 1)->get();
        $cities = City::all();
        $aminities = Aminity::all();
        $nearest_locations = NearestLocation::orderBy('id', 'desc')->where('status', 1)->get();
        $existing_sliders = PropertySlider::where('property_id', $id)->get();
        $existing_properties = PropertyAminity::where('property_id', $id)->get();
        $existing_nearest_locations = PropertyNearestLocation::where('property_id', $id)->get();
        $existing_add_informations = AdditionalInformation::where('property_id', $id)->get();
        $existing_plans = PropertyPlan::where('property_id', $id)->get();
        $countries = Country::orderBy('id', 'desc')->get();

        $featured_property = 'disable';
        $top_property = 'disable';
        $urgent_property = 'disable';

        if($property->agent_id == 0){
            $featured_property = 'enable';
            $top_property = 'enable';
            $urgent_property = 'enable';
        }else{
            $agent_id = $property->agent_id;
            $agent_order = Order::where('agent_id', $agent_id)->orderBy('id','desc')->first();

            if($agent_order){
                $is_featured = $agent_order->featured_property;
                $featured_property_qty = $agent_order->featured_property_qty;

                $is_top = $agent_order->top_property;
                $top_property_qty = $agent_order->top_property_qty;

                $is_urgent = $agent_order->urgent_property;
                $urgent_property_qty = $agent_order->urgent_property_qty;


                if($top_property_qty == -1){
                    $top_property = 'enable';
                }else{
                    $top_property_count = Property::where('agent_id', $agent_id)->where('is_top', 'enable')->count();
                    if($top_property_count < $top_property_qty){
                        $top_property = 'enable';
                    }
                }

                if($urgent_property_qty == -1){
                    $urgent_property = 'enable';
                }else{
                    $urgent_property_count = Property::where('agent_id', $agent_id)->where('is_urgent', 'enable')->count();
                    if($urgent_property_count < $urgent_property_qty){
                        $urgent_property = 'enable';
                    }
                }

                if($featured_property_qty == -1){
                    $featured_property = 'enable';
                }else{
                    $featured_property_count = Property::where('agent_id', $agent_id)->where('is_featured', 'enable')->count();
                    if($featured_property_count < $featured_property_qty){
                        $featured_property = 'enable';
                    }
                }
            }else{
                $notification = trans('admin_validation.Agent does not have any pricing plan');
                $notification = array('messege'=>$notification,'alert-type'=>'error');
                return redirect()->back()->with($notification);
            }
        }

        $setting = Setting::first();

        return view('admin.property_edit')->with([
            'property' => $property,
            'types' => $types,
            'cities' => $cities,
            'aminities' => $aminities,
            'nearest_locations' => $nearest_locations,
            'existing_sliders' => $existing_sliders,
            'existing_properties' => $existing_properties,
            'existing_nearest_locations' => $existing_nearest_locations,
            'existing_add_informations' => $existing_add_informations,
            'existing_plans' => $existing_plans,
            'featured_property' => $featured_property,
            'top_property' => $top_property,
            'urgent_property' => $top_property,
            'countries' => $countries,
            'setting' => $setting
        ]);
    }

    public function update(Request $request, $id){

        $property = Property::find($id);
        $live_map = Setting::first()->live_map;

        $rules = [
            'title'=>'required|unique:properties,title,'.$id,
            'slug'=>'required|unique:properties,slug,'.$id,
            'property_type_id'=>'required',
            'purpose'=> 'required',
            'rent_period'=> $request->purpose == 'rent' ? 'required' : '',
            'price'=>'required',
            'description'=>'required',
            'country_id'=>'required',
            'city_id'=>'required',
            'address'=>'required',
            'address_description'=>'required',
            'google_map'=> $live_map == 'no' ? 'required' : '',
            'total_area'=>'required',
            'total_unit'=>'required',
            'total_bedroom'=>'required',
            'total_bathroom'=>'required',
            'total_garage'=>'required',
            'total_kitchen'=>'required',
        ];
        $customMessages = [
            'title.required' => trans('admin_validation.Title is required'),
            'title.unique' => trans('admin_validation.Title already exist'),
            'slug.required' => trans('admin_validation.Slug is required'),
            'slug.unique' => trans('admin_validation.Slug already exist'),
            'property_type_id.required' => trans('admin_validation.Property type is required'),
            'purpose.required' => trans('admin_validation.Purpose is required'),
            'rent_period.required' => trans('admin_validation.Rent period is required'),
            'price.required' => trans('admin_validation.Price is required'),
            'description.required' => trans('admin_validation.Description is required'),
            'country_id.required' => trans('admin_validation.Country is required'),
            'city_id.required' => trans('admin_validation.City is required'),
            'address.required' => trans('admin_validation.Address is required'),
            'address_description.required' => trans('admin_validation.Address details is required'),
            'google_map.required' => trans('admin_validation.Google map is required'),
            'total_area.required' => trans('admin_validation.Total area is required'),
            'total_unit.required' => trans('admin_validation.Total unit is required'),
            'total_bedroom.required' => trans('admin_validation.Total bedroom is required'),
            'total_bathroom.required' => trans('admin_validation.Total bathroom is required'),
            'total_garage.required' => trans('admin_validation.Total garage is required'),
            'total_kitchen.required' => trans('admin_validation.Total kitchen is required'),
        ];

        $this->validate($request, $rules,$customMessages);

        $property->title = $request->title;
        $property->slug = $request->slug;
        $property->property_type_id = $request->property_type_id;
        $property->purpose = $request->purpose;
        $property->rent_period = $request->purpose == 'rent' ? $request->rent_period : '';
        $property->price = $request->price;
        $property->description = $request->description;

        $property->total_area = $request->total_area;
        $property->total_unit = $request->total_unit;
        $property->total_bedroom = $request->total_bedroom;
        $property->total_bathroom = $request->total_bathroom;
        $property->total_garage = $request->total_garage;
        $property->total_kitchen = $request->total_kitchen;
        $property->total_bathroom = $request->total_bathroom;

        $property->country_id = $request->country_id;
        $property->city_id = $request->city_id;
        $property->address = $request->address;
        $property->address_description = $request->address_description;
        $property->google_map = $request->google_map;
        $property->lat = $request->has('lat') ? $request->lat : $property->lat;
        $property->lon = $request->has('lng') ? $request->lng : $property->lon;

        $property->video_id = $request->video_id;
        $property->video_description = $request->video_description;

        if($request->thumbnail_image){
            $old_thumbnail_image = $property->thumbnail_image;
            $extention = $request->thumbnail_image->getClientOriginalExtension();
            $image_name = 'property-thumb'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->thumbnail_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $property->thumbnail_image = $image_name;
            $property->save();

            if($old_thumbnail_image){
                if(File::exists(public_path().'/'.$old_thumbnail_image))unlink(public_path().'/'.$old_thumbnail_image);
            }
        }

        if($request->video_thumbnail){
            $old_video_thumbnail = $property->video_thumbnail;
            $extention = $request->video_thumbnail->getClientOriginalExtension();
            $image_name = 'video-thumb'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $image_name = 'uploads/custom-images/'.$image_name;
            Image::make($request->video_thumbnail)
                ->encode('webp', 80)
                ->save(public_path().'/'.$image_name);
            $property->video_thumbnail = $image_name;

            if($old_video_thumbnail){
                if(File::exists(public_path().'/'.$old_video_thumbnail))unlink(public_path().'/'.$old_video_thumbnail);
            }
        }

        $property->seo_title = $request->seo_title ? $request->seo_title : $request->title;
        $property->seo_meta_description = $request->seo_meta_description ? $request->seo_meta_description : $request->title;
        $property->status = $request->status ? 'enable' : 'disable';
        $property->is_featured = $request->is_featured ? 'enable' : 'disable';
        $property->is_top = $request->is_top ? 'enable' : 'disable';
        $property->is_urgent = $request->is_urgent ? 'enable' : 'disable';

        if($property->agent_id != 0){
            $property->approve_by_admin = $request->approve_by_admin;
        }
        $property->date_from = $request->date_form;
        $property->date_to = $request->date_to;
        $property->time_from = $request->time_form;
        $property->time_to = $request->time_to;
        $property->save();

        PropertyAminity::where('property_id', $id)->delete();

        if($request->aminities){
            foreach($request->aminities as $aminity){
                $item = new PropertyAminity();
                $item->aminity_id = $aminity;
                $item->property_id = $property->id;
                $item->save();
            }
        }

        if($request->slider_images){
            foreach($request->slider_images as $index => $image){
                $extention = $image->getClientOriginalExtension();
                $image_name = 'Property-slider'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                $image_name = 'uploads/custom-images/'.$image_name;
                Image::make($image)
                    ->encode('webp', 80)
                    ->save(public_path().'/'.$image_name);

                $slider = new PropertySlider();
                $slider->property_id = $property->id;
                $slider->image = $image_name;
                $slider->save();
            }
        }

        if($request->existing_nearest_locations && $request->existing_distances){
            foreach($request->existing_nearest_locations as $index => $nearest_location){
                if($request->existing_nearest_locations[$index] != '' && $request->existing_distances[$index] != '' && $request->existing_nearest_ids[$index] != ''){
                    $new_loc = PropertyNearestLocation::find($request->existing_nearest_ids[$index]);
                    $new_loc->nearest_location_id = $request->existing_nearest_locations[$index];
                    $new_loc->distance = $request->existing_distances[$index];
                    $new_loc->save();
                }
            }
        }

        if($request->nearest_locations && $request->distances){
            foreach($request->nearest_locations as $index => $nearest_location){
                if($request->nearest_locations[$index] != '' && $request->distances[$index] != ''){
                    $new_loc = new PropertyNearestLocation();
                    $new_loc->property_id = $property->id;
                    $new_loc->nearest_location_id = $request->nearest_locations[$index];
                    $new_loc->distance = $request->distances[$index];
                    $new_loc->save();
                }
            }
        }

        if($request->existing_add_keys && $request->existing_add_values){
            foreach($request->existing_add_keys as $index => $add_key){
                if($request->existing_add_keys[$index] != '' && $request->existing_add_values[$index] != '' && $request->existing_add_ids[$index] != ''){
                    $new_loc = AdditionalInformation::find($request->existing_add_ids[$index]);
                    $new_loc->add_key = $request->existing_add_keys[$index];
                    $new_loc->add_value = $request->existing_add_values[$index];
                    $new_loc->save();
                }
            }
        }

        if($request->add_keys && $request->add_values){
            foreach($request->add_keys as $index => $add_key){
                if($request->add_keys[$index] != '' && $request->add_values[$index] != ''){
                    $new_loc = new AdditionalInformation();
                    $new_loc->property_id = $property->id;
                    $new_loc->add_key = $request->add_keys[$index];
                    $new_loc->add_value = $request->add_values[$index];
                    $new_loc->save();
                }
            }
        }

        if($request->existing_plan_ids && $request->existing_plan_titles && $request->existing_plan_descriptions){
            foreach($request->existing_plan_ids as $index => $plan_id){

                if($request->existing_plan_ids[$index] && $request->existing_plan_titles[$index] && $request->existing_plan_descriptions[$index]){

                    $plan = PropertyPlan::find($request->existing_plan_ids[$index]);
                    $plan->title = $request->existing_plan_titles[$index];
                    $plan->description = $request->existing_plan_descriptions[$index];
                    $plan->save();

                    $ex_name = 'existing_plan_image_'.$plan_id;
                    $request_exist_image = $request->$ex_name;

                    if($request_exist_image){
                        $exist_image = $plan->image;
                        $extention = $request_exist_image->getClientOriginalExtension();
                        $image_name = 'Property-plan'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                        $image_name = 'uploads/custom-images/'.$image_name;
                        Image::make($request_exist_image)
                            ->encode('webp', 80)
                            ->save(public_path().'/'.$image_name);

                        $plan->image = $image_name;
                        $plan->save();
                        if($exist_image){
                            if(File::exists(public_path().'/'.$exist_image))unlink(public_path().'/'.$exist_image);
                        }
                    }

                }
            }
        }

        if($request->plan_images && $request->plan_titles && $request->plan_descriptions){
            foreach($request->plan_images as $index => $image){
                if($request->plan_images[$index] && $request->plan_titles[$index] && $request->plan_descriptions[$index]){
                    $extention = $image->getClientOriginalExtension();
                    $image_name = 'Property-plan'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
                    $image_name = 'uploads/custom-images/'.$image_name;
                    Image::make($image)
                        ->encode('webp', 80)
                        ->save(public_path().'/'.$image_name);

                    $plan = new PropertyPlan();
                    $plan->property_id = $property->id;
                    $plan->image = $image_name;
                    $plan->title = $request->plan_titles[$index];
                    $plan->description = $request->plan_descriptions[$index];
                    $plan->save();
                }
            }
        }

        if($property->agent_id != 0){
            $notification = trans('admin_validation.Update succssfully');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->route('admin.agent-property')->with($notification);
        }else{
            $notification = trans('admin_validation.Update succssfully');
            $notification = array('messege'=>$notification,'alert-type'=>'success');
            return redirect()->route('admin.property.index')->with($notification);
        }



    }

    public function destroy($id){

        $property = Property::find($id);

        PropertyAminity::where('property_id', $id)->delete();
        PropertyNearestLocation::where('property_id', $id)->delete();
        AdditionalInformation::where('property_id', $id)->delete();
        Wishlist::where('property_id', $id)->delete();
        Compare::where('property_id', $id)->delete();
        Review::where('property_id', $id)->delete();

        $existing_plans = PropertyPlan::where('property_id', $id)->get();

        foreach($existing_plans as $existing_plan){
            $old_image = $existing_plan->image;
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
            $existing_plan->delete();
        }

        $existing_sliders = PropertySlider::where('property_id', $id)->get();

        foreach($existing_sliders as $existing_slider){
            $old_slider = $existing_slider->image;
            if($old_slider){
                if(File::exists(public_path().'/'.$old_slider))unlink(public_path().'/'.$old_slider);
            }
            $existing_slider->delete();
        }


        $old_thumbnail_image = $property->thumbnail_image;
        if($old_thumbnail_image){
            if(File::exists(public_path().'/'.$old_thumbnail_image))unlink(public_path().'/'.$old_thumbnail_image);
        }

        $old_video_thumbnail = $property->video_thumbnail;
        if($old_video_thumbnail){
            if(File::exists(public_path().'/'.$old_video_thumbnail))unlink(public_path().'/'.$old_video_thumbnail);
        }

        $property->delete();

        $notification = trans('admin_validation.Deleted successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function remove_property_slider($id){
        $slider = PropertySlider::where('id', $id)->first();

        $old_slider = $slider->image;
        if($old_slider){
            if(File::exists(public_path().'/'.$old_slider))unlink(public_path().'/'.$old_slider);
        }

        $slider->delete();

        return response()->json(['message' => 'success']);

    }

    public function remove_nearest_location($id){
        PropertyNearestLocation::where('id', $id)->delete();

        return response()->json(['message' => 'success']);
    }

    public function remove_add_info($id){
        AdditionalInformation::where('id', $id)->delete();

        return response()->json(['message' => 'success']);
    }

    public function remove_plan($id){
        $plan = PropertyPlan::where('id', $id)->first();

        $old_image = $plan->image;
        if($old_image){
            if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
        }

        $plan->delete();

        return response()->json(['message' => 'success']);

    }

    public function check_slug($slug){
        $property = Property::where('slug', $slug)->first();
        if($property){
            return response()->json(['message' => trans('admin_validation.Slug already exist')],403);
        }else{
            return response()->json(['message' => 'available']);
        }
    }

    public function agent_plan_availability($agent_id){

        $agent_order = Order::where('agent_id', $agent_id)->orderBy('id','desc')->first();

        if($agent_order){

            $available = 'disable';
            $top_property = 'disable';
            $urgent_property = 'disable';

            $expiration_date = $agent_order->expiration_date;

            if($expiration_date != 'lifetime'){
                if(date('Y-m-d') > $expiration_date){
                    return response()->json(['message' => trans('admin_validation.Pricing plan date is expired'), 'available' => 'disable', 'top_property' => 'disable', 'urgent_property' => 'disable', 'featured_property' => 'disable'],403);
                }
            }

            $number_of_property = $agent_order->number_of_property;

            $is_featured = $agent_order->featured_property;
            $featured_property_qty = $agent_order->featured_property_qty;

            $is_top = $agent_order->top_property;
            $top_property_qty = $agent_order->top_property_qty;

            $is_urgent = $agent_order->urgent_property;
            $urgent_property_qty = $agent_order->urgent_property_qty;

            if($number_of_property == -1){
                $available = 'enable';
            }else{
                $property_count = Property::where('agent_id', $agent_id)->count();
                if($property_count < $number_of_property){
                    $available = 'enable';
                }
            }

            if($top_property_qty == -1){
                $top_property = 'enable';
            }else{
                $top_property_count = Property::where('agent_id', $agent_id)->where('is_top', 'enable')->count();
                if($top_property_count < $top_property_qty){
                    $top_property = 'enable';
                }
            }

            if($urgent_property_qty == -1){
                $urgent_property = 'enable';
            }else{
                $urgent_property_count = Property::where('agent_id', $agent_id)->where('is_urgent', 'enable')->count();
                if($urgent_property_count < $urgent_property_qty){
                    $urgent_property = 'enable';
                }
            }

            $featured_property = 'disable';
            if($featured_property_qty == -1){
                $featured_property = 'enable';
            }else{
                $featured_property_count = Property::where('agent_id', $agent_id)->where('is_featured', 'enable')->count();
                if($featured_property_count < $featured_property_qty){
                    $featured_property = 'enable';
                }
            }

            return response()->json(['message' => 'success' ,'available' => $available , 'top_property' => $top_property, 'urgent_property' => $urgent_property, 'featured_property' => $featured_property]);

        }else{
            return response()->json(['message' => trans('admin_validation.Agent does not have any pricing plan'), 'available' => 'disable', 'top_property' => 'disable', 'urgent_property' => 'disable', 'featured_property' => 'disable'],403);
        }

    }


    public function assign_slider_property(){
        $properties = Property::where('status', 'enable')->get();

        return view('admin.assign_slider_property', compact('properties'));
    }

    public function store_assign_slider_property(Request $request){

        $rules = [
            'property_id'=>'required',
            'serial'=>'required',
        ];

        $customMessages = [
            'property_id.required' => trans('admin_validation.Property is required'),
            'serial.required' => trans('admin_validation.Serial is required'),
        ];

        $this->validate($request, $rules,$customMessages);

        $property = Property::find($request->property_id);

        $count = Property::where('id', $request->property_id)->where('show_slider', 'enable')->count();

        if($count > 0){
            $notification=trans('admin_validation.Property already assign');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->back()->with($notification);
        }

        $property->show_slider = 'enable';
        $property->serial = $request->serial;
        $property->save();

        $notification=trans('admin_validation.Assign successful');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function remove_intro_slider($id){

        $property = Property::find($id);
        $property->show_slider = 'disable';
        $property->serial = 0;
        $property->save();

        $notification=trans('admin_validation.Deleted successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function review_list(Request $request){
        if($request->agent_id){
            $reviews = Review::orderBy('id','desc')->where('agent_id', $request->agent_id)->get();
        }else{
            $reviews = Review::orderBy('id','desc')->get();
        }


        return view('admin.review', compact('reviews'));
    }

    public function show_review($id){
        $review = Review::find($id);

        return view('admin.show_review', compact('review'));
    }

    public function update_review(Request $request, $id){
        $review = Review::find($id);
        $review->status = $request->status;
        $review->save();

        $notification=trans('admin_validation.Updated successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

    public function delete_review($id){
        $review = Review::find($id);
        $review->delete();

        $notification=trans('admin_validation.Deleted successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.review-list')->with($notification);

    }


    public function Booking()
    {

        $booking = Booking::with('property')->where('agent_id', 0)->paginate(10);

        return view('admin.booking')->with(['booking' => $booking]);
    }

    public function showBooking($id){
        $booking = Booking::where('id', $id)->first();
        return view('admin.booking_show')->with(['booking' => $booking]);
    }

    public function changeStatus($id){
        $Booking = Booking::find($id);
        if($Booking->status==1){
            $Booking->status=0;
            $Booking->save();
            $message= trans('admin_validation.Pending Successfully');
        }else{
            $Booking->status=1;
            $Booking->save();
            $message= trans('admin_validation.Confirmed Successfully');
        }
        return response()->json($message);
    }

    public function remove($id)
    {
        $Booking = Booking::find($id);
        $Booking->delete();

        $notification = trans('admin_validation.Deleted successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

    public function property_city_list(Request $request, $id)
    {
        $cities = City::where('country_id', $id)->orderBY('name', 'asc')->get();
        $city_id = $request->city_id ?? null;

        return response()->json([
            'template' => view('admin.city_partials', compact('cities', 'city_id'))->render()
        ], 200);
    }


}
