<?php

namespace App\Http\Controllers\API\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;

use App\Models\BreadcrumbImage;
use App\Models\GoogleRecaptcha;
use App\Models\User;
use App\Rules\Captcha;
use App\Mail\UserForgetPassword;
use App\Mail\UserForgetPasswordForOTP;
use App\Helpers\MailHelper;
use App\Models\EmailTemplate;
use App\Models\SocialLoginInformation;
use App\Models\Setting;
use Mail;
use Str;
use Validator,Redirect,Response,File;
use Socialite;
use Auth;
use Hash;
use Session;
use Exception;

class LoginController extends Controller
{

    use AuthenticatesUsers;
    protected $redirectTo = '/user/dashboard';

    public function __construct()
    {
        $this->middleware('guest:api')->except('userLogout');
    }

    public function loginPage(){
        $breadcrumb = BreadcrumbImage::where(['id' => 11])->first();
        $recaptcha_setting = GoogleRecaptcha::first();
        $social_login = SocialLoginInformation::first();

        $setting = Setting::first();
        $login_page = array(
            'image' => $setting->login_image,
            'login_top_item' => $setting->login_top_item,
            'login_top_item_qty' => $setting->login_top_item_qty,
            'login_footer_item' => $setting->login_footer_item,
            'login_footer_item_qty' => $setting->login_footer_item_qty,
            'login_page_logo' => $setting->login_page_logo,

        );
        $login_page = (object) $login_page;


        return view('login')->with([
            'recaptcha_setting' => $recaptcha_setting,
            'social_login' => $social_login,
            'login_page' => $login_page,
        ]);
    }

    public function storeLogin(Request $request){
        $rules = [
            'email'=>'required',
            'password'=>'required',
            'g-recaptcha-response'=>new Captcha()
        ];
        $customMessages = [
            'email.required' => trans('user_validation.Email is required'),
            'password.required' => trans('user_validation.Password is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $credential=[
            'email'=> $request->email,
            'password'=> $request->password
        ];
        $user = User::where('email',$request->email)->select('id','name','email','phone','user_name','status','password','image','address','designation','about_me','facebook','twitter','linkedin','instagram', 'kyc_status', 'is_agency', 'owner_id')->first();
        if($user){
            if($user->status==1){
                if(Hash::check($request->password,$user->password)){
                    if($token = Auth::guard('api')->attempt($credential)){
                        return $this->respondWithToken($token,$user);
                    }else{
                        return response()->json(['message' => 'Unauthorized'], 401);
                    }
                }else{
                    $notification = trans('user_validation.Credentials does not exist');
                    return response()->json(['message' => $notification], 403);
                }

            }else{
                $notification = trans('user_validation.Disabled Account');
                return response()->json(['message' => $notification], 403);
            }
        }else{
            $notification = trans('user_validation.Email does not exist');
            return response()->json(['message' => $notification], 403);
        }
    }

    protected function respondWithToken($token,$user)
    {
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth('api')->factory()->getTTL() * 60,
            'user' => $user
        ]);
    }


    public function forgetPage(){
        $recaptcha_setting = GoogleRecaptcha::first();
        $social_login = SocialLoginInformation::first();

        $setting = Setting::first();
        $login_page = array(
            'image' => $setting->login_image,
            'login_top_item' => $setting->login_top_item,
            'login_top_item_qty' => $setting->login_top_item_qty,
            'login_footer_item' => $setting->login_footer_item,
            'login_footer_item_qty' => $setting->login_footer_item_qty,
            'login_page_logo' => $setting->login_page_logo,

        );
        $login_page = (object) $login_page;

        return view('forget_password')->with([
            'recaptcha_setting' => $recaptcha_setting,
            'social_login' => $social_login,
            'login_page' => $login_page,
        ]);
    }

    public function sendForgetPassword(Request $request){
        $rules = [
            'email'=>'required',
            'g-recaptcha-response'=>new Captcha()
        ];
        $customMessages = [
            'email.required' => trans('user_validation.Email is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user = User::where('email', $request->email)->first();

        if($user){
            $user->forget_password_otp = random_int(100000, 999999);
            $user->save();

            try{
                MailHelper::setMailConfig();
                $template = EmailTemplate::where('id',1)->first();
                $subject = $template->subject;
                $message = $template->description;
                $message = str_replace('{{name}}',$user->name,$message);
                Mail::to($user->email)->send(new UserForgetPasswordForOTP($message,$subject,$user));
            }catch(Exception $ex){}

            $notification = trans('user_validation.Reset password link send to your email.');
            return response()->json(['message' => $notification]);

        }else{
            $notification = trans('user_validation.Email does not exist');
            return response()->json(['message' => $notification],403);
        }
    }


    public function resetPasswordPage($token){
        $rules = [
            'email'=>'required',
            'token'=>'required',
        ];
        $customMessages = [
            'email.required' => trans('user_validation.Email is required'),
            'token.required' => trans('user_validation.Token is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user = User::select('id','name','email','forget_password_token')->where('forget_password_token', $token)->first();

        $recaptcha_setting = GoogleRecaptcha::first();
        $social_login = SocialLoginInformation::first();

        $setting = Setting::first();
        $login_page = array(
            'image' => $setting->login_image,
            'login_top_item' => $setting->login_top_item,
            'login_top_item_qty' => $setting->login_top_item_qty,
            'login_footer_item' => $setting->login_footer_item,
            'login_footer_item_qty' => $setting->login_footer_item_qty,
            'login_page_logo' => $setting->login_page_logo,

        );
        $login_page = (object) $login_page;

        return view('reset_password')->with([
            'recaptcha_setting' => $recaptcha_setting,
            'social_login' => $social_login,
            'login_page' => $login_page,
            'user' => $user,
            'token' => $token,
        ]);
    }

    public function storeResetPasswordPage(Request $request){

        $rules = [
            'email'=>'required',
            'token'=>'required',
            'password'=>'required|min:4|confirmed',
            'g-recaptcha-response'=>new Captcha()
        ];
        $customMessages = [
            'email.required' => trans('user_validation.Email is required'),
            'password.required' => trans('user_validation.Password is required'),
            'password.min' => trans('user_validation.Password must be 4 characters'),
            'password.confirmed' => trans('user_validation.Confirm password does not match'),
            'token.required' => trans('user_validation.Token is required'),
        ];
        $this->validate($request, $rules,$customMessages);

        $user = User::where(['email' => $request->email, 'forget_password_otp' => $request->token])->first();
        if($user){
            $user->password=Hash::make($request->password);
            $user->forget_password_token=null;
            $user->forget_password_otp=null;
            $user->save();

            $notification = trans('user_validation.Password Reset successfully');
            return response()->json(['message' => $notification]);
        }else{
            $notification = trans('user_validation.Invalid token');
            return response()->json(['message' => $notification],403);
        }
    }

    public function userLogout(){
        Auth::guard('api')->logout();
        $notification= trans('user_validation.Logout Successfully');
        return response()->json(['message' => $notification]);
    }

    public function redirectToGoogle(){
        $googleInfo = SocialLoginInformation::first();
        \Config::set('services.google.client_id', $googleInfo->gmail_client_id);
        \Config::set('services.google.client_secret', $googleInfo->gmail_secret_id);
        \Config::set('services.google.redirect', $googleInfo->gmail_redirect_url);

        return Socialite::driver('google')->redirect();
    }

    public function googleCallBack(){

        $googleInfo = SocialLoginInformation::first();
        \Config::set('services.google.client_id', $googleInfo->gmail_client_id);
        \Config::set('services.google.client_secret', $googleInfo->gmail_secret_id);
        \Config::set('services.google.redirect', $googleInfo->gmail_redirect_url);

        $user = Socialite::driver('google')->user();
        $user = $this->createUser($user,'google');
        auth()->login($user);
        return redirect()->intended(route('user.dashboard'));
    }

    function createUser($getInfo,$provider){
        $user = User::where('provider_id', $getInfo->id)->first();
        if (!$user) {
            $user = User::create([
                'name'     => $getInfo->name,
                'email'    => $getInfo->email,
                'provider' => $provider,
                'provider_id' => $getInfo->id,
                'provider_avatar' => $getInfo->avatar,
                'status' => 1,
                'email_verified' => 1,
            ]);
        }
        return $user;
    }
}
