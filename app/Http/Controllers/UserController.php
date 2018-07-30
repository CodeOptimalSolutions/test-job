<?php

namespace DTApi\Http\Controllers;

use Monolog\Logger;
use DTApi\Models\Job;
use DTApi\Models\User;
use DTApi\Http\Requests;
use DTApi\Models\UserMeta;
use DTApi\Models\UserTowns;
use Illuminate\Http\Request;
use DTApi\Models\UsersBlacklist;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use DTApi\Repository\UserRepository;
use DTApi\Repository\BookingRepository;

class UserController extends Controller
{

    public function __construct()
    {
        $this->middleware(['current.user']);
    }

    public function index(Request $request)
    {   
        $query = User::query();
        $limit = 15;

        $cuser = $request->__authenticatedUser;
        $consumer_type = $cuser->userMeta->consumer_type;
        if ($cuser && $cuser->user_type == env('ADMIN_ROLE_ID'))
        {
//            $query->whereHas('userMeta', function ($q) {
//               $q->where('consumer_type', '!=', 'paid');
//               $q->where('translator_type', '!=', 'professional');
//            });
//        }
//        else {
            if ($consumer_type == 'RWS') {
                $query->whereHas('userMeta', function ($q) {
                    $q->where(function($q_or_where) {
                        $q_or_where->orWhere('consumer_type', 'rwsconsumer');
                        $q_or_where->orWhere('translator_type', 'rwstranslator');
                    });
                });
            }
            else
            {
                $query->whereHas('userMeta', function ($q) {
                    $q->where(function($q_or_where) {
                        $q_or_where->orWhere('consumer_type', 'ngo');
                        $q_or_where->orWhere('translator_type', 'volunteer');
                    });
                });
            }
        }
    
        if($request->has('user_type'))
            $query->where('user_type', $request->get('user_type'));
        else
            $query->where(function ($orQuery2) {
                $orQuery2->Where('user_type', '=', env('CUSTOMER_ROLE_ID'));
                $orQuery2->orWhere('user_type', '=', env('TRANSLATOR_ROLE_ID'));
            });
        if($request->has('q')) {
            $text = $request->get('q');
            $query->where(function ($orQuery) use ($text) {
                $orQuery->Where('name', 'LIKE', '%' . $text . '%');
                $orQuery->orWhere('email', 'LIKE', '%' . $text . '%');
            });
        }
        if($request->has('limit')) $limit = $request->get('limit');

        if($request->has('department_id'))
            $query->where('department_id', $request->get('department_id'));

        $query->with(['languages', 'userMeta', 'average', 'jobs', 'translator.jobs', 'towns']);

        // Query for user's DOB code
        if ($request->has('dob')) {
            // it should accept any dash variant, but clean the string for search
            $dob = str_replace('-', '', $request->get('dob'));
            $query->where('dob_or_orgid', $dob)->orWhere('dob_or_orgid', $request->get('dob'));
        }

        // Show users with certain language
        if ($request->has('l')) {
            $languageId = $request->get('l');
            $query->whereHas('languages', function ($q) use ($languageId) {
                $q->where('lang_id', $languageId);
            });
        }

        if($request->has('all') && $request->get('all') == 'true')
            $users = $query->with('salaries')->get();
        else
            $users = $query->paginate($limit);

        return response($users);
    }

    public function show($id, Request $request)
    {
        if ($request->has('save_setting') && $request->get('save_setting') == 'yes') {
            $requestdata = $request->all();
            $not_get_notification = 'yes';
            $not_get_emergency = 'yes';
            $not_get_nighttime = 'yes';

            if (isset($requestdata['get_notification']) && $requestdata['get_notification'] == 'yes') {
                $not_get_notification = 'no';
            }
            if (isset($requestdata['get_emergency']) && $requestdata['get_emergency'] == 'yes') {
                $not_get_emergency = 'no';
            }
            if (isset($requestdata['get_nighttime']) && $requestdata['get_nighttime'] == 'yes') {
                $not_get_nighttime = 'no';
            }
            $this->saveNotificationSetting($id, 'not_get_notification', $not_get_notification);
            $this->saveNotificationSetting($id, 'not_get_emergency', $not_get_emergency);
            $this->saveNotificationSetting($id, 'not_get_nighttime', $not_get_nighttime);
        }
        if($request->has('translator_ex_save'))
        {

            if ($id) {
                $translatorId = $request['translator_ex'];
                $already_exist = UsersBlacklist::translatorExist($id, $translatorId);
                if ($already_exist == 0) {
                    $blacklist = new UsersBlacklist();
                    $blacklist->user_id = $id;
                    $blacklist->translator_id = $translatorId;
                    $blacklist->save();
                }
                else {
                    UsersBlacklist::where('user_id', $id)->where('translator_id', $translatorId)->delete();
                }
            }

//            $blacklistUpdated = [];
//            $userBlacklist = UsersBlacklist::where('user_id', $id)->get();
//            $userTranslId = collect($userBlacklist)->pluck('translator_id')->all();

//            $diff = null;
//            if ($request['translator_ex']) {
//                $diff = array_intersect($userTranslId, $request['translator_ex']);
//            }
//            if ($diff || $request['translator_ex']) {
//                foreach ($request['translator_ex'] as $translatorId) {
//                    $blacklist = new UsersBlacklist();
//                    if ($id) {
//                        $already_exist = UsersBlacklist::translatorExist($id, $translatorId);
//                        if ($already_exist == 0) {
//                            $blacklist->user_id = $id;
//                            $blacklist->translator_id = $translatorId;
//                            $blacklist->save();
//                        }
//                        $blacklistUpdated [] = $translatorId;
//                    }
//
//                }
//                if ($blacklistUpdated) {
//                    UsersBlacklist::deleteFromBlacklist($id, $blacklistUpdated);
//                }
//            } else {
//                UsersBlacklist::where('user_id', $id)->delete();
//            }
        }

        $user = User::with(['languages.language', 'userMeta', 'towns', 'average', 'usersBlacklist.user', 'salaries.customer', 'invoices', 'customerSalary', 'inconvenienceSettings'])->find($id);

        return response($user);
    }

    public function store(Request $request, UserRepository $userRepository)
    {
        $user = $userRepository->createOrUpdate(null, $request);

        $logger = new Logger('admin_logger');
//
        $logger->pushHandler(new StreamHandler(storage_path('logs/admin/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());
//
        $logger->addInfo('USER #' . $request->__authenticatedUser->id . '(' . $request->__authenticatedUser->name . ')' . ' create new user with data:  ', ['user' => $user->toArray()]);

        return response($user);
    }

    public function update($id, Request $request, UserRepository $userRepository)
    {

        $old_user = User::with('userMeta', 'towns.towns')->find($id)->toArray();
        $user = $userRepository->createOrUpdate($id, $request);
        $new_user = User::with('userMeta', 'towns.towns')->find($id)->toArray();
        $old_user_meta = array_diff($old_user['user_meta'], $new_user['user_meta']);

        $old_user_info = array_diff(array_except($old_user, ['user_meta', 'towns']), array_except($new_user, ['user_meta', 'towns']));
        $new_user_meta = array_diff($new_user['user_meta'], $old_user['user_meta']);
        $new_user_info = array_diff(array_except($new_user, ['user_meta', 'towns']), array_except($old_user, ['user_meta', 'towns']));
        $new_user_towns = array_diff(collect($new_user['towns'])->pluck('town_id')->all(), collect($old_user['towns'])->pluck('town_id')->all());
        $logger = new Logger('admin_logger');
        $logger->pushHandler(new StreamHandler(storage_path('logs/admin/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());

        $log_data = [
            'old_user_meta' => array_except($old_user_meta, 'updated_at'),
            'old_user'      => array_except($old_user_info, 'updated_at'),
            "new_user_meta" => array_except($new_user_meta, 'updated_at'),
            "new_user"      => array_except($new_user_info, 'updated_at'),
        ];

        if (collect($old_user['towns'])->pluck('town_id')->count() != collect($new_user['towns'])->pluck('town_id')->count()) {
            $log_data['old_user_towns'] = collect($old_user['towns'])->pluck('town_id')->all();
            $log_data['new_user_towns'] = collect($new_user['towns'])->pluck('town_id')->all();
        }

        return $new_user;
        $logger->addInfo('USER #' . $request->__authenticatedUser->id . ' update user with data:  ', ['id' => $id, $log_data]);

    }

    public function destroy($id, Request $request)
    {
        $user = User::findOrFail($id);
        UserMeta::where('user_id', $id)->delete();
        UserTowns::where('user_id', $id)->delete();

        $logger = new Logger('admin_logger');

        $logger->pushHandler(new StreamHandler(storage_path('logs/admin/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());

        $logger->addInfo('USER #' . $request->__authenticatedUser->id . ' delete user with data:  ', ['id' => $id, 'user' => $user->toArray()]);
        $user->delete();
        DB::table('login_logs')->where('user_id', '=', $id)->delete();

        return response('success');
    }

    public function saveNotificationSetting($user_id, $key, $val)
    {
        $userMeta = UserMeta::where('user_id', $user_id)->first();

        $userMeta->$key = $val;
        $userMeta->save();
    }

    public function getPotentialTranslators($id, BookingRepository $bookingRepository)
    {
        $job = Job::find($id);
        return response($bookingRepository->getPotentialTranslators($job));
    }

    public function getTranslators(UserRepository $userRepository)
    {
        return response($userRepository->getTranslators());
    }

}
