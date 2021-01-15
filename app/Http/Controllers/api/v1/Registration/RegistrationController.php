<?php

namespace App\Http\Controllers\api\v1\Registration;

use App\Mail\ForgetPassword;
use App\Model\UserOtpModel;
use App\repo\datavalue;
use App\repo\Response;
use App\User;
use Exception;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Mail;
use Illuminate\Support\Facades\Validator;

class RegistrationController extends Controller
{
    public function signup(Request $request)
    {
        $msg = [
            'f_name.required' => 'Enter Your First Name.',
            'l_name.required' => 'Enter Your Last Name.',
            'email.required' => 'Enter Your Email.',
            'email.unique' => 'Email is already exists.',
            'mobile.required' => 'Enter your email.',
            'street_address.required' => 'Enter Street Address.',
            'city.required' => 'Enter City.',
            'state.required' => 'Enter State.',
            'd_o_b.required' => 'Enter Date Of Brith.',
            'password.required' => 'Password is required.',
            'confirm_password.required' => 'Confirm Password is required.',
            'confirm_password.same' => 'Passwords not matched.'
        ];
        $validator = Validator::make($request->all(), [
            'f_name' => 'required|string|max:255',
            'l_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'mobile' => 'required',
            'street_address' => 'required',
            'city' => 'required',
            'state' => 'required',
            'd_o_b' => 'required',
            'password' => 'required|string|min:6',
            'confirm_password' => 'required|same:password'
        ], $msg);
        if ($validator->passes()) {
            DB::beginTransaction();
            try {
                $fname = $request->get('f_name');
                $lname = $request->get('l_name');
                $email = $request->get('email');
                $mobile = $request->get('mobile');
                $street_address = $request->get('street_address');
                $city = $request->get('city');
                $state = $request->get('state');
                $d_o_b = $request->get('d_o_b');
                $pass = $request->get('password');
                $user =  User::create([
                    'f_name' => $fname,
                    'l_name' => $lname,
                    'email' => $email,
                    'mobile' => $mobile,
                    'street_address'=>$street_address,
                    'city' => $city,
                    'state' => $state,
                    'd_o_b' => $d_o_b,
                    'password' => bcrypt($pass),
                    'api_token' => sha1(time()),
                ]);
                DB::commit();
                $data = [];
                $msg = 'Successfully Registered.';
                return Response::Success($data, $msg);
            } catch (Exception $e) {
                DB::rollback();
                $data = [];
                $msg = 'Registration Failed.';
                return Response::Error($data, $msg);
            }
        } else {
            $data = $validator->errors()->first();
            $msg = 'Registration Failed.';
            return Response::Error($data, $msg);
        }
    }


    public function forget_password(Request $request)
    {
        $msg = [
            'email.required' => 'Enter Your Email.',
        ];
        $this->validate($request, [
            'email' => 'required|email',
        ], $msg);

        $email = $request->get('email');
        try {
            $check_email = User::where('email', $email)->count();
            if ($check_email == 1) {
                $otp = mt_rand(100000, 999999);
                $user = User::where('email', $email)->first();
                $name = $user->user_name;
                $api_token = User::where('email', $email)->value('api_token');

                if ($api_token != '') {
                    $check_otp = UserOtpModel::where('token', $api_token)->count();
                    if ($check_otp == 0) {
                        UserOtpModel::create([
                            'token' => $api_token,
                            'otp' => $otp,
                        ]);
                    } else {
                        UserOtpModel::where('token', $api_token)->update([
                            'token' => $api_token,
                            'otp' => $otp,
                        ]);
                    }

                    Mail::to($email)->send(new ForgetPassword($name, $otp));
                    $data=['token' => $api_token];
                    $msg=  'Please check your mail to get otp.';
                    return Response::Success($data,$msg);

                } else {
                    $data=[];
                    $msg=  ' Token Not Found.';
                    return Response::Error($data,$msg);
                }
            } else {
                $data=[];
                $msg=  ' Email Not valid.';
                return Response::Error($data,$msg);
            }
        }catch (Exception $e){
            $data=[];
            $msg=  'Failed.';
            return Response::Error($data,$msg);
        }

    }

    public function check_otp(Request $request)
    {
        $otp = $request->get('otp');
        $token = $request->get('token');
        try {
            if ($otp != '') {
                $user_otp = UserOtpModel::where('token', $token)->value('otp');
                if ($user_otp == $otp) {

                    UserOtpModel::where('token', $token)->update([
                        'otp' =>  mt_rand(100000, 999999),
                    ]);

                    $data=['token' => $token];
                    $msg=  'Otp Matched';
                    return Response::Success($data,$msg);
                } else {
                    $data=[];
                    $msg=  'Otp Not Matched.';
                    return Response::Error($data,$msg);
                }
            }else{
                $data=[];
                $msg=  'Enter Your otp.';
                return Response::Error($data,$msg);
            }
        }catch (Exception $e){
            $data=[];
            $msg=  'Failed.';
            return Response::Error($data,$msg);
        }
    }

    public function reset_password(Request $request)
    {

        $msg = [
            'n_password.required' => 'Enter Your New Password.',
            'c_password.required' => 'Enter Your Confirm Password.',
        ];
        $validator = Validator::make($request->all(), [
            'n_password' => 'required',
            'c_password' => 'required',
        ], $msg);
        if ($validator->passes()) {
            try {
                $n_password = $request->get('n_password');
                $c_password = $request->get('c_password');
                $token = $request->get('token');
                if ($n_password == $c_password) {
                    User::where('api_token', $token)->update([
                        'password' => bcrypt($n_password)
                    ]);
                    UserOtpModel::where('token', $token)->update([
                        'otp' => mt_rand(100000, 999999),
                    ]);
                    $data = ['token' => $token];
                    $msg = 'Password Updated Successfully';
                    return Response::Success($data, $msg);
                } else {
                    $data = [];
                    $msg = 'New Password and Confirm not matched.';
                    return Response::Error($data, $msg);
                }
            } catch (Exception $e) {
                $data = [];
                $msg = 'Failed.';
                return Response::Error($data, $msg);
            }
        }else{
            $data = $validator->errors()->first();
            $msg = 'Registration Failed.';
            return Response::Error($data, $msg);
        }
    }

}
