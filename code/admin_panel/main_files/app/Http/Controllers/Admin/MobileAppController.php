<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MobileApp;
use Image;
use Str;
use File;
class MobileAppController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function mobile_app(){

        $mobile_app = MobileApp::first();
        if(!$mobile_app){
            $mobile_app = new MobileApp();
            $mobile_app->splash_screen = 'splash_screen.jpg';
            $mobile_app->onboarding_one_title = 'onboarding_one_title';
            $mobile_app->onboarding_one_description = 'onboarding_one_description';
            $mobile_app->onboarding_one_icon = 'onboarding_one_icon.jpg';
            $mobile_app->onboarding_two_title = 'onboarding_two_title';
            $mobile_app->onboarding_two_description = 'onboarding_two_description';
            $mobile_app->onboarding_two_icon = 'onboarding_two_icon.jpg';
            $mobile_app->onboarding_three_title = 'onboarding_three_title';
            $mobile_app->onboarding_three_description = 'onboarding_three_description';
            $mobile_app->onboarding_three_icon = 'onboarding_three_icon.jpg';
            $mobile_app->login_bg_image = 'login_bg_image.jpg';
            $mobile_app->login_page_logo = 'login_page_logo.jpg';
            $mobile_app->save();
        }

        return view('admin.mobile_app_content', compact('mobile_app'));
    }

    public function update_mobile_app(Request $request){

        $mobile_app = MobileApp::first();

        $mobile_app->onboarding_one_title = $request->onboarding_one_title;
        $mobile_app->onboarding_one_description = $request->onboarding_one_description;
        $mobile_app->onboarding_two_title = $request->onboarding_two_title;
        $mobile_app->onboarding_two_description = $request->onboarding_two_description;
        $mobile_app->onboarding_three_title = $request->onboarding_three_title;
        $mobile_app->onboarding_three_description = $request->onboarding_three_description;
        $mobile_app->save();

        if($request->onboarding_one_icon){
            $existing_slider = $mobile_app->onboarding_one_icon;
            $extention = $request->onboarding_one_icon->getClientOriginalExtension();
            $slider_image = 'onboarding_one_icon'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $slider_image = 'uploads/website-images/'.$slider_image;
            Image::make($request->onboarding_one_icon)
                ->save(public_path().'/'.$slider_image);
            $mobile_app->onboarding_one_icon = $slider_image;
            $mobile_app->save();

            if($existing_slider){
                if(File::exists(public_path().'/'.$existing_slider))unlink(public_path().'/'.$existing_slider);
            }
        }

        if($request->onboarding_two_icon){
            $existing_slider = $mobile_app->onboarding_two_icon;
            $extention = $request->onboarding_two_icon->getClientOriginalExtension();
            $slider_image = 'onboarding_two_icon'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $slider_image = 'uploads/website-images/'.$slider_image;
            Image::make($request->onboarding_two_icon)
                ->save(public_path().'/'.$slider_image);
            $mobile_app->onboarding_two_icon = $slider_image;
            $mobile_app->save();

            if($existing_slider){
                if(File::exists(public_path().'/'.$existing_slider))unlink(public_path().'/'.$existing_slider);
            }
        }

        if($request->onboarding_three_icon){
            $existing_slider = $mobile_app->onboarding_three_icon;
            $extention = $request->onboarding_three_icon->getClientOriginalExtension();
            $slider_image = 'onboarding_three_icon'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $slider_image = 'uploads/website-images/'.$slider_image;
            Image::make($request->onboarding_three_icon)
                ->save(public_path().'/'.$slider_image);
            $mobile_app->onboarding_three_icon = $slider_image;
            $mobile_app->save();

            if($existing_slider){
                if(File::exists(public_path().'/'.$existing_slider))unlink(public_path().'/'.$existing_slider);
            }
        }

        if($request->login_bg_image){
            $existing_slider = $mobile_app->login_bg_image;
            $extention = $request->login_bg_image->getClientOriginalExtension();
            $slider_image = 'app-login-bg-image'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $slider_image = 'uploads/website-images/'.$slider_image;
            Image::make($request->login_bg_image)
                ->save(public_path().'/'.$slider_image);
            $mobile_app->login_bg_image = $slider_image;
            $mobile_app->save();

            if($existing_slider){
                if(File::exists(public_path().'/'.$existing_slider))unlink(public_path().'/'.$existing_slider);
            }
        }

        if($request->login_page_logo){
            $existing_slider = $mobile_app->login_page_logo;
            $extention = $request->login_page_logo->getClientOriginalExtension();
            $slider_image = 'app-login-page-logo'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $slider_image = 'uploads/website-images/'.$slider_image;
            Image::make($request->login_page_logo)
                ->save(public_path().'/'.$slider_image);
            $mobile_app->login_page_logo = $slider_image;
            $mobile_app->save();

            if($existing_slider){
                if(File::exists(public_path().'/'.$existing_slider))unlink(public_path().'/'.$existing_slider);
            }
        }

        $notification= trans('admin_validation.Updated Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
}
