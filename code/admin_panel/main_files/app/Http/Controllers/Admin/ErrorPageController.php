<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ErrorPage;
use Image;
use File;
class ErrorPageController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index(){
        $errorpage = ErrorPage::first();
        return view('admin.error_page', compact('errorpage'));
    }

    public function update(Request $request, $id)
    {
        $errorPage = ErrorPage::find($id);

        $rules = [
            'header'=>'required',
            'button_text'=>'required',
        ];
        $customMessages = [
            'header.required' => trans('admin_validation.Title is required'),
            'button_text.required' => trans('admin_validation.Button text is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $errorPage->header=$request->header;
        $errorPage->button_text=$request->button_text;
        $errorPage->save();

        if($request->image){
            $old_image = $errorPage->image;
            $extention=$request->image->getClientOriginalExtension();
            $image_name = 'error-page-'.date('-Y-m-d-h-i-s-').rand(999,9999).'.'.$extention;
            $image_name ='uploads/website-images/'.$image_name;
            Image::make($request->image)
                ->save(public_path().'/'.$image_name);
            $errorPage->image = $image_name;
            $errorPage->save();
            if($old_image){
                if(File::exists(public_path().'/'.$old_image))unlink(public_path().'/'.$old_image);
            }
        }


        $notification= trans('admin_validation.Updated Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }
}
