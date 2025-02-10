<?php

namespace App\Http\Controllers\Admin;

use Str;
use File;
use Image;
use App\Models\City;
use App\Models\User;
use App\Models\Country;

use App\Models\Property;

use Illuminate\Http\Request;
use App\Exports\CountryExport;
use App\Imports\CountryImport;
use App\Http\Controllers\Controller;
use Maatwebsite\Excel\Facades\Excel;

class CountryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:admin');
    }

    public function index()
    {
        $countries = Country::orderBy('id', 'desc')->get();

        return view('admin.country', compact('countries'));
    }


    public function create()
    {
        return view('admin.create_country');
    }

    public function store(Request $request)
    {
        $rules = [
            'name'=>'required|unique:countries'
        ];

        $customMessages = [
            'name.required' => trans('Country is required'),
            'name.unique' => trans('Country already exist'),
        ];
        $this->validate($request, $rules,$customMessages);

        $country=new Country();
        $country->name=$request->name;
        $country->save();

        $notification=trans('admin_validation.Created Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->back()->with($notification);
    }


    public function edit($id)
    {
        $country = Country::find($id);

        return view('admin.edit_country', compact('country'));
    }


    public function update(Request $request, $id)
    {
        $country = Country::find($id);
        $rules = [
            'name'=>'required|unique:countries,name,'.$country->id
        ];
        $customMessages = [
            'name.required' => trans('Country is required'),
            'name.unique' => trans('Country already exist'),
        ];
        $this->validate($request, $rules,$customMessages);

        $country->name=$request->name;
        $country->save();

        $notification=trans('admin_validation.Update Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.country.index')->with($notification);
    }


    public function destroy($id)
    {
        $city_count = City::where('country_id', $id)->count();

        if($city_count > 0){
            $notification=trans('admin_validation.In this item multiple city exist, so you can not delete this item');
            $notification=array('messege'=>$notification,'alert-type'=>'error');
            return redirect()->route('admin.country.index')->with($notification);
        }

        $country = Country::find($id);
        $country->delete();

        $notification=trans('admin_validation.Delete Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.country.index')->with($notification);
    }

    public function country_import(){
        return view('admin.country_import');
    }

    public function country_export(){
        return Excel::download(new CountryExport, 'countries.xlsx');
    }

    public function store_import_Country(Request $request){
        Excel::import(new CountryImport, $request->file('file'));

        $notification=trans('admin_validation.Uploaded Successfully');
        $notification=array('messege'=>$notification,'alert-type'=>'success');
        return redirect()->route('admin.country.index')->with($notification);
    }







}
