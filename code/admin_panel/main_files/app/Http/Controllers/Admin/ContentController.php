<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MaintainanceText;
use App\Models\Setting;
use App\Models\SeoSetting;
use App\Models\Homepage;
use Image;
use File;
class ContentController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function maintainanceMode()
    {
        $maintainance = MaintainanceText::first();
        return view('admin.maintainance_mode', compact('maintainance'));
    }

    public function maintainanceModeUpdate(Request $request)
    {
        $rules = [
            'description'=> $request->maintainance_mode ? 'required' : ''
        ];
        $customMessages = [
            'description.required' => trans('admin_validation.Description is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $maintainance = MaintainanceText::first();
        if($request->image){
            $old_image=$maintainance->image;
            $image=$request->image;
            $ext=$image->getClientOriginalExtension();
            $image_name= 'maintainance-mode-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $image_name='uploads/website-images/'.$image_name;
            Image::make($image)
                ->save(public_path().'/'.$image_name);
            $maintainance->image=$image_name;
            $maintainance->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }
        $maintainance->status = $request->maintainance_mode ? 1 : 0;
        $maintainance->description = $request->description;
        $maintainance->save();

        $notification= trans('admin_validation.Updated Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function headerPhoneNumber(){
        $setting = Setting::first();
        return view('admin.header_phone_number',compact('setting'));
    }

    public function updateHeaderPhoneNumber(Request $request){
        $rules = [
            'topbar_phone'=>'required',
            'topbar_email'=>'required',
            'opening_time'=>'required'
        ];
        $customMessages = [
            'topbar_phone.required' => trans('admin_validation.Header phone is required'),
            'topbar_email.required' => trans('admin_validation.Header email is required'),
            'opening_time.required' => trans('admin_validation.Opening time is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $setting = Setting::first();
        $setting->topbar_phone = $request->topbar_phone;
        $setting->topbar_email = $request->topbar_email;
        $setting->opening_time = $request->opening_time;
        $setting->save();

        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function mobileApp(){
        $setting = Setting::first();

        $mobile_app = array(
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
        $mobile_app = (object) $mobile_app;

        return view('admin.mobile_app',compact('mobile_app'));
    }


    public function updateMobileApp(Request $request){
        $rules = [
            'full_title'=>'required',
            'description'=>'required',
            'play_store'=>'required',
            'app_store'=>'required',
        ];
        $customMessages = [
            'full_title.required' => trans('admin_validation.Title is required'),
            'description.required' => trans('admin_validation.Description is required'),
            'play_store.required' => trans('admin_validation.Play store is required'),
            'app_store.required' => trans('admin_validation.App store is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $setting = Setting::first();
        $setting->app_full_title = $request->full_title;
        $setting->app_description = $request->description;
        $setting->google_playstore_link = $request->play_store;
        $setting->app_store_link = $request->app_store;
        $setting->apple_btn_text1 = $request->apple_btn_text1;
        $setting->apple_btn_text2 = $request->apple_btn_text2;
        $setting->google_btn_text1 = $request->google_btn_text1;
        $setting->google_btn_text2 = $request->google_btn_text2;
        $setting->save();




        if($request->image){
            $old_image = $setting->app_image;
            $extention=$request->image->getClientOriginalExtension();
            $image_name = 'mobile-app-bg-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name ='uploads/website-images/'.$image_name;
            Image::make($request->image)
                ->save(public_path().'/'.$image_name);
            $setting->app_image = $image_name;
            $setting->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->image2){
            $old_image = $setting->home2_app_image;
            $extention=$request->image2->getClientOriginalExtension();
            $image_name = 'mobile-app-bg-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name ='uploads/website-images/'.$image_name;
            Image::make($request->image2)
                ->save(public_path().'/'.$image_name);
            $setting->home2_app_image = $image_name;
            $setting->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }

        if($request->image3){
            $old_image = $setting->home3_app_image;
            $extention=$request->image3->getClientOriginalExtension();
            $image_name = 'mobile-app-bg-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name ='uploads/website-images/'.$image_name;
            Image::make($request->image3)
                ->save(public_path().'/'.$image_name);
            $setting->home3_app_image = $image_name;
            $setting->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }


        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function seoSetup(){
        $pages = SeoSetting::all();
        return view('admin.seo_setup', compact('pages'));
    }

    public function updateSeoSetup(Request $request, $id){
        $rules = [
            'seo_title' => 'required',
            'seo_description' => 'required'
        ];
        $customMessages = [
            'seo_title.required' => trans('admin_validation.Seo title is required'),
            'seo_description.required' => trans('admin_validation.Seo description is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $page = SeoSetting::find($id);
        $page->seo_title = $request->seo_title;
        $page->seo_description = $request->seo_description;
        $page->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }

    public function defaultAvatar(){
        $setting = Setting::first();
        $default_avatar = array(
            'image' => $setting->default_avatar
        );
        $default_avatar = (object) $default_avatar;
        return view('admin.default_profile_image', compact('default_avatar'));
    }

    public function updateDefaultAvatar(Request $request){
        $setting = Setting::first();
        if($request->avatar){
            $existing_avatar = $setting->default_avatar;
            $extention = $request->avatar->getClientOriginalExtension();
            $default_avatar = 'default-avatar'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $default_avatar = 'uploads/website-images/'.$default_avatar;
            Image::make($request->avatar)
                ->save(public_path().'/'.$default_avatar);
            $setting->default_avatar = $default_avatar;
            $setting->save();
            if($existing_avatar){
                if(File::exists(public_path().'/'.$existing_avatar))unlink(public_path().'/'.$existing_avatar);
            }
        }

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function login_page(){
        $setting = Setting::first();

        $login_page = array(
            'image' => $setting->login_image,
            'login_top_item' => $setting->login_top_item,
            'login_top_item_qty' => $setting->login_top_item_qty,
            'login_footer_item' => $setting->login_footer_item,
            'login_footer_item_qty' => $setting->login_footer_item_qty,
            'login_page_logo' => $setting->login_page_logo,
            'login_bg_image' => $setting->login_bg_image,

        );
        $login_page = (object) $login_page;
        return view('admin.login_page', compact('login_page'));
    }

    public function update_login_page(Request $request){
        $setting = Setting::first();
        if($request->image){
            $existing_avatar = $setting->default_avatar;
            $extention = $request->image->getClientOriginalExtension();
            $default_avatar = 'login-page'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $default_avatar = 'uploads/website-images/'.$default_avatar;
            Image::make($request->image)
                ->save(public_path().'/'.$default_avatar);
            $setting->login_image = $default_avatar;
            $setting->save();
            if($existing_avatar){
                if(File::exists(public_path().'/'.$existing_avatar))unlink(public_path().'/'.$existing_avatar);
            }
        }

        if($request->logo){
            $old_logo = $setting->login_page_logo;
            $image = $request->logo;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'login-logo-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $logo_name = 'uploads/website-images/'.$logo_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$logo_name);
            $setting->login_page_logo = $logo_name;
            $setting->save();
            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }
        }

        if($request->login_bg_image){
            $old_logo = $setting->login_bg_image;
            $image = $request->login_bg_image;
            $ext = $image->getClientOriginalExtension();
            $logo_name = 'login-logo-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $image_path = 'uploads/website-images/'.$logo_name;
            $image->move(public_path('uploads/website-images/'),$logo_name);

            $setting->login_bg_image = $image_path;
            $setting->save();
            if($old_logo){
                if(File::exists(public_path().'/'.$old_logo))unlink(public_path().'/'.$old_logo);
            }
        }



        $setting->login_top_item = $request->login_top_item;
        $setting->login_top_item_qty = $request->login_top_item_qty;
        $setting->login_footer_item = $request->login_footer_item;
        $setting->login_footer_item_qty = $request->login_footer_item_qty;
        $setting->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function homepage(){

        $homepage = Homepage::first();
        return view('admin.homepage', compact('homepage'));
    }

    public function update_homepage(Request $request){

        $homepage = Homepage::first();
        $homepage->location_title = $request->location_title;
        $homepage->location_description = $request->location_description;
        $homepage->show_location = $request->location_status ? 'enable' : 'disable';

        $homepage->property_title = $request->property_title;
        $homepage->property_description = $request->property_description;
        $homepage->property_item = $request->property_item;
        $homepage->show_property = $request->property_status ? 'enable' : 'disable';

        $homepage->urgent_property_title = $request->urgent_property_title;
        $homepage->urgent_property_description = $request->urgent_property_description;
        $homepage->urgent_property_item = $request->urgent_property_item;
        $homepage->show_urgent_property = $request->urgent_property_status ? 'enable' : 'disable';

        $homepage->why_choose_title = $request->why_choose_title;
        $homepage->why_choose_description = $request->why_choose_description;
        $homepage->show_why_choose_us = $request->why_choose_us_status ? 'enable' : 'disable';

        $homepage->agent_title = $request->agent_title;
        $homepage->agent_description = $request->agent_description;
        $homepage->agent_item = $request->agent_item;
        $homepage->show_agent = $request->agent_status ? 'enable' : 'disable';

        if($request->home2_agent_bg){
            $old_bg = $homepage->home2_agent_bg;
            $image = $request->home2_agent_bg;
            $ext = $image->getClientOriginalExtension();
            $bg_name = 'agent-bg-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $bg_name = 'uploads/website-images/'.$bg_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$bg_name);
            $homepage->home2_agent_bg = $bg_name;
            $homepage->save();
            if($old_bg){
                if(File::exists(public_path().'/'.$old_bg))unlink(public_path().'/'.$old_bg);
            }
        }

        if($request->testimonial_bg){
            $old_bg = $homepage->testimonial_bg;
            $image = $request->testimonial_bg;
            $ext = $image->getClientOriginalExtension();
            $bg_name = 'testimonial-bg-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $bg_name = 'uploads/website-images/'.$bg_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$bg_name);
            $homepage->testimonial_bg = $bg_name;
            $homepage->save();
            if($old_bg){
                if(File::exists(public_path().'/'.$old_bg))unlink(public_path().'/'.$old_bg);
            }
        }

        $homepage->testimonial_title = $request->testimonial_title;
        $homepage->testimonial_description = $request->testimonial_description;
        $homepage->testimonial_item = $request->testimonial_item;
        $homepage->show_testimonial = $request->testimonial_status ? 'enable' : 'disable';

        $homepage->blog_title = $request->blog_title;
        $homepage->blog_description = $request->blog_description;
        $homepage->blog_item = $request->blog_item;
        $homepage->show_blog = $request->blog_status ? 'enable' : 'disable';

        $homepage->pricing_plan_title = $request->pricing_plan_title;
        $homepage->pricing_plan_description = $request->pricing_plan_description;
        $homepage->show_pricing_plan = $request->pricing_plan_status ? 'enable' : 'disable';

        $homepage->category_item = $request->category_item;
        $homepage->show_category = $request->category_status ? 'enable' : 'disable';

        $homepage->show_about_us = $request->about_us_status ? 'enable' : 'disable';
        $homepage->show_faq = $request->faq_status ? 'enable' : 'disable';
        $homepage->show_mobile_app = $request->mobile_app_status ? 'enable' : 'disable';
        $homepage->show_counter = $request->counter_status ? 'enable' : 'disable';

        $homepage->partner_title = $request->partner_title;
        $homepage->partner_item = $request->partner_item;
        $homepage->show_partner = $request->partner_status ? 'enable' : 'disable';



        $homepage->save();

        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function create_property(){
        $setting = Setting::first();
        return view('admin.create_property', compact('setting'));
    }

    public function update_create_property(Request $request){
        $rules = [
            'rent_title' => 'required',
            'rent_description' => 'required',
            'rent_btn_text' => 'required',
            'sale_title' => 'required',
            'sale_description' => 'required',
            'sale_btn_text' => 'required',

        ];
        $customMessages = [
            'rent_title.required' => trans('admin_validation.Title is required'),
            'rent_description.required' => trans('admin_validation.Description is required'),
            'rent_btn_text.required' => trans('admin_validation.Button text is required'),
            'sale_title.required' => trans('admin_validation.Title is required'),
            'sale_description.required' => trans('admin_validation.Description is required'),
            'sale_btn_text.required' => trans('admin_validation.Button text is required'),

        ];
        $this->validate($request, $rules,$customMessages);

        $setting = Setting::first();
        $setting->rent_title = $request->rent_title;
        $setting->rent_description = $request->rent_description;
        $setting->rent_btn_text = $request->rent_btn_text;
        $setting->sale_title = $request->sale_title;
        $setting->sale_description = $request->sale_description;
        $setting->sale_btn_text = $request->sale_btn_text;
        $setting->save();

        if($request->rent_logo){
            $old_bg = $setting->rent_logo;
            $image = $request->rent_logo;
            $ext = $image->getClientOriginalExtension();
            $bg_name = 'rent-logo-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $bg_name = 'uploads/website-images/'.$bg_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$bg_name);
            $setting->rent_logo = $bg_name;
            $setting->save();
            if($old_bg){
                if(File::exists(public_path().'/'.$old_bg))unlink(public_path().'/'.$old_bg);
            }
        }

        if($request->sale_logo){
            $old_bg = $setting->sale_logo;
            $image = $request->sale_logo;
            $ext = $image->getClientOriginalExtension();
            $bg_name = 'sale-logo-'.date('Y-m-d-h-i-s-').rand(999,9999).'.'.$ext;
            $bg_name = 'uploads/website-images/'.$bg_name;
            $logo = Image::make($image)
                    ->save(public_path().'/'.$bg_name);
            $setting->sale_logo = $bg_name;
            $setting->save();
            if($old_bg){
                if(File::exists(public_path().'/'.$old_bg))unlink(public_path().'/'.$old_bg);
            }
        }



        $notification = trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);

    }



}
