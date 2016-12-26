<?php

namespace Exp\Discuss\Controllers\Auth;

use Exp\Discuss\Models\Country;
use Exp\Discuss\Models\Profile;
use Exp\Discuss\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\RegistersUsers;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    use RegistersUsers;

    /**
     * Where to redirect users after login / registration.
     *
     * @var string
     */
    protected $redirectTo = 'forum/discuss';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'name' => 'required|max:255|unique:users,name',
            'email' => 'required|email|max:255|unique:users',
            'password' => 'required|min:6|confirmed',
        ]);
    }
    protected function message()
    {
        return [
            'name.required'=>'Name is required.',
            'name.max'=>'Your name is too long!',
            'name.unique'=>'Username taken. Try something else.',
            'email.required'=>'Email is required.',
            'email.email'=>'The email must be a valid email address.',
            'email.max'=>'The email must be a valid email address.',
            'email.unique'=>'Email taken. Do you already have an account?',
            'password.required'=>'Password is required.',
            'password.min'=>'Your password is too short.',
            'password.confirmed'=>'Password confirmation does not match.Try again, please.',
        ];
    }
    public function register()
    {
        return view('forum::auth.register');
    }

    /**
     * Create a new user instance after a valid registration.
     *
     * @param  array  $data
     * @return User
     */
    protected function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => bcrypt($data['password']),
        ]);
        $profile = new Profile;
        $profile->user_id = $user->id;
        $profile->country_id = Country::where('short_name','us')->first()->id;
        $profile->save();
        return $user;
    }
}
