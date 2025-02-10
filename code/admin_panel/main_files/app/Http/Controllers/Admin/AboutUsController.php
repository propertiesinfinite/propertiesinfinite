<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AboutUs;
use App\Models\HowItWork;
use Illuminate\Http\Request;
use Image;
use File;

class AboutUsController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $about = AboutUs::first();

        return view('admin.about-us',compact('about'));
    }

    public function update_aboutus(Request $request){
        $rules = [
            'experience_text_1' => 'required',
            'experience_text_2' => 'required',
            'author_name' => 'required',
            'author_designation' => 'required',
            'short_title' => 'required',
            'long_title' => 'required',
            'description_1' => 'required',
            'description_2' => 'required',
            'item1_title' => 'required',
            'item1_title2' => 'required',
            'item2_title' => 'required',
            'item2_title2' => 'required',
            'item1_description' => 'required',
            'item2_description' => 'required',
        ];
        $customMessages = [
            'experience_text_1.required' => trans('admin_validation.Experience text is required'),
            'experience_text_2.required' => trans('admin_validation.Experience text is required'),
            'author_name.required' => trans('admin_validation.Author name is required'),
            'author_designation.required' => trans('admin_validation.Author designation is required'),
            'short_title.required' => trans('admin_validation.Short title is required'),
            'long_title.required' => trans('admin_validation.Long title is required'),
            'description_1.required' => trans('admin_validation.Description is required'),
            'description_2.required' => trans('admin_validation.Description is required'),
            'item1_title.required' => trans('admin_validation.Number is required'),
            'item1_title2.required' => trans('admin_validation.Title is required'),
            'item2_title.required' => trans('admin_validation.Number is required'),
            'item2_title2.required' => trans('admin_validation.Title is required'),
            'item1_description.required' => trans('admin_validation.Description is required'),
            'item2_description.required' => trans('admin_validation.Description is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $about = AboutUs::first();
        $about->short_title = $request->short_title;
        $about->long_title = $request->long_title;
        $about->experience_text_1 = $request->experience_text_1;
        $about->experience_text_2 = $request->experience_text_2;
        $about->description_1 = $request->description_1;
        $about->description_2 = $request->description_2;
        $about->author_name = $request->author_name;
        $about->author_designation = $request->author_designation;
        $about->item1_title = $request->item1_title;
        $about->item1_title2 = $request->item1_title2;
        $about->item1_description = $request->item1_description;
        $about->item2_title = $request->item2_title;
        $about->item2_title2 = $request->item2_title2;
        $about->item2_description = $request->item2_description;
        $about->save();

        if($request->background_image){
            $exist_banner = $about->background_image;
            $extention = $request->background_image->getClientOriginalExtension();
            $banner_name = 'about-us-bg'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $banner_name = 'uploads/website-images/'.$banner_name;
            Image::make($request->background_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$banner_name);
            $about->background_image = $banner_name;
            $about->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }

        if($request->author_image){
            $exist_banner = $about->author_image;
            $extention = $request->author_image->getClientOriginalExtension();
            $banner_name = 'author-image'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $banner_name = 'uploads/website-images/'.$banner_name;
            Image::make($request->author_image)
                ->encode('webp', 80)
                ->save(public_path().'/'.$banner_name);
            $about->author_image = $banner_name;
            $about->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }

        if($request->item1_icon){
            $exist_banner = $about->item1_icon;
            $extention = $request->item1_icon->getClientOriginalExtension();
            $banner_name = 'about-us-item-one'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $banner_name = 'uploads/website-images/'.$banner_name;
            $request->item1_icon->move(public_path('uploads/website-images/'),$banner_name);

            $about->item1_icon = $banner_name;
            $about->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }

        if($request->item2_icon){
            $exist_banner = $about->item2_icon;
            $extention = $request->item2_icon->getClientOriginalExtension();
            $banner_name = 'about-us-item-two'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $banner_name = 'uploads/website-images/'.$banner_name;
            $request->item2_icon->move(public_path('uploads/website-images/'),$banner_name);
            $about->item2_icon = $banner_name;
            $about->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }

        $notification = trans('admin_validation.Updated Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function home2()
    {
        $about = AboutUs::first();

        return view('admin.home2_about-us',compact('about'));
    }

    public function update_home2_aboutus(Request $request){

        $rules = [
            'home2_short_title' => 'required',
            'home2_long_title' => 'required',
            'home2_percentage' => 'required',
            'home2_percentage_text' => 'required',
            'home2_description1' => 'required',
            'home2_description2' => 'required',
            'home2_item1' => 'required',
            'home2_item2' => 'required',
        ];
        $customMessages = [
            'home2_short_title.required' => trans('admin_validation.Short title is required'),
            'home2_long_title.required' => trans('admin_validation.Long title is required'),
            'home2_percentage.required' => trans('admin_validation.Percentage is required'),
            'home2_percentage_text.required' => trans('admin_validation.Percentage text is required'),
            'home2_description1.required' => trans('admin_validation.Description is required'),
            'home2_description2.required' => trans('admin_validation.Description is required'),
            'home2_item1.required' => trans('admin_validation.Item one is required'),
            'home2_item2.required' => trans('admin_validation.Item two is required'),
        ];
        $this->validate($request, $rules,$customMessages);


        $about = AboutUs::first();
        $about->home2_short_title = $request->home2_short_title;
        $about->home2_long_title = $request->home2_long_title;
        $about->home2_percentage = $request->home2_percentage;
        $about->home2_percentage_text = $request->home2_percentage_text;
        $about->home2_description1 = $request->home2_description1;
        $about->home2_description2 = $request->home2_description2;
        $about->home2_item1 = $request->home2_item1;
        $about->home2_item2 = $request->home2_item2;
        $about->save();

        if($request->home2_image1){
            $exist_banner = $about->home2_image1;
            $extention = $request->home2_image1->getClientOriginalExtension();
            $banner_name = 'home2-about-us1'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $banner_name = 'uploads/website-images/'.$banner_name;
            Image::make($request->home2_image1)
                ->encode('webp', 80)
                ->save(public_path().'/'.$banner_name);
            $about->home2_image1 = $banner_name;
            $about->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }

        if($request->home2_image2){
            $exist_banner = $about->home2_image2;
            $extention = $request->home2_image2->getClientOriginalExtension();
            $banner_name = 'home2-aboutus-2'.date('-Y-m-d-h-i-s-').rand(999,9999).'.webp';
            $banner_name = 'uploads/website-images/'.$banner_name;
            Image::make($request->home2_image2)
                ->encode('webp', 80)
                ->save(public_path().'/'.$banner_name);
            $about->home2_image2 = $banner_name;
            $about->save();
            if($exist_banner){
                if(File::exists(public_path().'/'.$exist_banner))unlink(public_path().'/'.$exist_banner);
            }
        }



        $notification = trans('admin_validation.Updated Successfully');
        $notification = array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
}


