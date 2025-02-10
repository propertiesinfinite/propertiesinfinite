<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Slider;
use App\Models\Setting;
use Image;
use File;
class SliderController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        $slider = Slider::first();

        $setting = Setting::first();
        $selected_theme = $setting->selected_theme;

        return view('admin.create_slider', compact('slider','selected_theme'));
    }

    public function update(Request $request, $id){
        $rules = [
            'home1_title_1' => 'required',
            'home1_title_2' => 'required',
            'home1_title_3' => 'required',
            'home1_item1' => 'required',
            'home1_item2' => 'required',
            'home1_item3' => 'required',
            'home1_btn_text' => 'required',
        ];
        $customMessages = [
            'home1_title_1.required' => trans('admin_validation.Title is required'),
            'home1_title_2.required' => trans('admin_validation.Title is required'),
            'home1_title_3.required' => trans('admin_validation.Title is required'),
            'home1_item1.required' => trans('admin_validation.Item is required'),
            'home1_item2.required' => trans('admin_validation.Item is required'),
            'home1_item3.required' => trans('admin_validation.Item is required'),
            'home1_btn_text.required' => trans('admin_validation.Button is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $slider = Slider::find($id);

        $slider->home1_title_1 = $request->home1_title_1;
        $slider->home1_title_2 = $request->home1_title_2;
        $slider->home1_title_3 = $request->home1_title_3;
        $slider->home1_item1 = $request->home1_item1;
        $slider->home1_item2 = $request->home1_item2;
        $slider->home1_item3 = $request->home1_item3;
        $slider->home1_btn_text = $request->home1_btn_text;
        $slider->save();


        if($request->file('image')){
            $old_background = $slider->home1_bg;
            $extention = $request->image->getClientOriginalExtension();
            $image_name = 'slider'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_path = 'uploads/website-images/'.$image_name;
            $request->image->move(public_path('uploads/website-images/'),$image_name);
            $slider->home1_bg = $image_path;
            $slider->save();

            if($old_background){
                if(File::exists(public_path().'/'.$old_background))unlink(public_path().'/'.$old_background);
            }
        }




        if($request->image3){
            $existing_slider = $slider->home3_image;
            $extention = $request->image3->getClientOriginalExtension();
            $slider_image = 'slider'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $slider_image = 'uploads/website-images/'.$slider_image;
            Image::make($request->image3)
                ->save(public_path().'/'.$slider_image);
            $slider->home3_image = $slider_image;
            $slider->save();
            if($existing_slider){
                if(File::exists(public_path().'/'.$existing_slider))unlink(public_path().'/'.$existing_slider);
            }
        }



        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function home2_update(Request $request){
        $rules = [
            'home2_title' => 'required'
        ];
        $customMessages = [
            'home2_title.required' => trans('admin_validation.Title is required')
        ];
        $this->validate($request, $rules,$customMessages);

        $slider = Slider::first();
        $slider->home2_title = $request->home2_title;
        $slider->save();

        if($request->file('image')){
            $old_background = $slider->home2_bg;
            $extention = $request->image->getClientOriginalExtension();
            $image_name = 'slider'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_path = 'uploads/website-images/'.$image_name;
            $request->image->move(public_path('uploads/website-images/'),$image_name);
            $slider->home2_bg = $image_path;
            $slider->save();

            if($old_background){
                if(File::exists(public_path().'/'.$old_background))unlink(public_path().'/'.$old_background);
            }
        }

        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }

    public function home3_update(Request $request){
        $rules = [
            'home3_title' => 'required',
            'home3_item1' => 'required',
            'home3_item2' => 'required',
            'home3_item3' => 'required',
            'home3_btn_text' => 'required',
        ];
        $customMessages = [
            'home3_title.required' => trans('admin_validation.Title is required'),
            'home3_item1.required' => trans('admin_validation.Item is required'),
            'home3_item2.required' => trans('admin_validation.Item is required'),
            'home3_item3.required' => trans('admin_validation.Item is required'),
            'home3_btn_text.required' => trans('admin_validation.Button is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $slider = Slider::first();
        $slider->home3_title = $request->home3_title;
        $slider->home3_item1 = $request->home3_item1;
        $slider->home3_item2 = $request->home3_item2;
        $slider->home3_item3 = $request->home3_item3;
        $slider->home3_btn_text = $request->home3_btn_text;
        $slider->save();

        if($request->file('image')){
            $old_background = $slider->home3_image;
            $extention = $request->image->getClientOriginalExtension();
            $image_name = 'slider'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_path = 'uploads/website-images/'.$image_name;
            $request->image->move(public_path('uploads/website-images/'),$image_name);
            $slider->home3_image = $image_path;
            $slider->save();

            if($old_background){
                if(File::exists(public_path().'/'.$old_background))unlink(public_path().'/'.$old_background);
            }
        }

        $notification= trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }






}
