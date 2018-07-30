<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Company;
use DTApi\Models\Department;
use DTApi\Models\UserMeta;
use Monolog\Logger;
use DTApi\Models\User;
use DTApi\Models\Language;
use DTApi\Models\UserTowns;
use Illuminate\Http\Request;
use DTApi\Models\UsersBlacklist;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;

class AdminUsersController extends Controller
{

    public static function index(Request $request)
    {
        if ($request->input('limit') || !session()->has('l5cp-adminuser-limit')) {
            session(['l5cp-adminuser-limit' => $request->input('limit', 15)]);
        }

        if (null !== $request->input('q') || !session()->has('l5cp-adminuser-search')) {
            session(['l5cp-adminuser-search' => $request->input('q', '')]);
        }

        if (null !== $request->input('sort') || !session()->has('l5cp-adminuser-sort')) {
            session(['l5cp-adminuser-sort' => $request->input('sort', 'name')]);
        }


        if (null !== $request->input('order') || !session()->has('l5cp-adminuser-order')) {
            session(['l5cp-adminuser-order' => $request->input('order', 'desc')]);
        }

        //Query
        $users = User::with('jobs', 'userMeta')
            ->where('name', 'LIKE', '%' . $request->input('q') . '%');
        if (($request->input('q') != "") || ($request->input('q') != NULL)) {
            $users->where('name', 'LIKE', '%' . $request->input('q') . '%');
            if (null !== $request->input('q')) {
                $users->orWhere('email', 'LIKE', '%' . $request->input('q') . '%');
            }
        }
        $users->where(function ($orQuery) {
            $orQuery->orWhere('user_type', '=', env('ADMIN_ROLE_ID'));
            $orQuery->orWhere('user_type', '=', env('SUPERADMIN_ROLE_ID'));
        });

        $users = $users->paginate(session('l5cp-adminuser-limit'));

        return response($users);
    }

    public function show($id)
    {
        $user = User::with(['languages.language', 'userMeta', 'towns', 'average', 'usersBlacklist'])->find($id);

        return response($user);
    }

    public static function create($request)
    {
        $languages = Language::where('active', '1')->orderBy('language')->get();
        $towns = UserTowns::all();
        $translators = User::where('user_type', 2)->get();
        return view('admin.user.admin_create_edit', ['languages' => $languages, 'towns' => $towns, 'translators' => $translators])->withSuccess(trans('validation.created'));
    }

    public static function store(Request $request)
    {
        $user = self::createOrUpdate(null, $request);

        $logger = new Logger('admin_logger');

        $logger->pushHandler(new StreamHandler(storage_path('logs/admin/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $logger->pushHandler(new FirePHPHandler());

//        $logger->addInfo('USER #' . Auth::user()->id . '(' . Auth::user()->name . ')' . ' create new user with data:  ', ['user' => $user->toArray()]);

        return $user;
    }

    public static function edit($id, Request $request)
    {
        $languages = Language::where('active', '1')->orderBy('language')->get();

        $towns = Towns::all();
        $userTowns = new UserTowns();
        $user_towns = $userTowns->userAllTowns($id);
        $user_town = [];
        foreach ($user_towns as $onetown) {
            $user_town[] = $onetown->town_id;
        }

        $users = User::with('userMeta')->all();
        $customers = $users->where('user_type', 1)->get();
        $translators = $users->where('user_type', 2)->get();

        $user = User::with('languages')->findOrFail($id);
        $userTransl = User::with('usersBlacklist')->findOrFail($id);
        $userTranslId = collect($userTransl->usersBlacklist)->pluck('translator_id')->all();
        $translatorsId = collect($translators)->pluck('id')->all();
        return ['languages' => $languages, 'userTowns' => $user_town, 'towns' => $towns, 'translators' => $translators, 'customers' => $customers, 'translatorsId' => $translatorsId, 'userTranslId' => $userTranslId, 'user' => $user];
    }

    public static function update($id, Request $request)
    {
        $user = self::createOrUpdate($id, $request);
        return $user;
    }

    public static function destroy($id, $request)
    {
        DB::table('loginlogs')->where('user_id', '=', $id)->delete();
        UserMeta::where('user_id', $id)->delete();
        UserTowns::where('user_id', $id)->delete();
        $user = User::findOrFail($id);
        $user->delete();
        return ['Deleted'];
    }

    public static function enable($id)
    {
        $user = User::findOrFail($id);
        $user->status = '1';
        $user->save();

    }

    public static function disable($id)
    {
        $user = User::findOrFail($id);
        $user->status = '0';
        $user->save();

    }

    public static function user_type($user_id)
    {

        $user = User::findOrFail($user_id);
        if (empty($user->user_type)) {
            return 'Admin';
        } else {
            $role = Role::findOrFail($user->user_type);
            return @$role->name;
        }
    }

    public static function createOrUpdate($id = null, $request)
    {
        $model = is_null($id) ? new User : User::findOrFail($id);
        $model->user_type = $request['role'];
        $model->name = $request['name'];
        $model->email = $request['email'];
        $model->dob_or_orgid = $request['dob_or_orgid'];
        $model->phone = $request['phone'];
        $model->mobile = $request['mobile'];


        if (!$id || $id && $request['password']) $model->password = bcrypt($request['password']);
        $model->detachAllRoles();
        $model->save();
        $model->attachRole($request['role']);

        $data = array();

        $user_meta = UserMeta::firstOrCreate(['user_id' => $model->id]);

        if ($request['role'] == env('ADMIN_ROLE_ID')) {
            $data['consumer_type'] = $request['consumer_type'];
            $user_meta->consumer_type = $request['consumer_type'];
        }
        $data['username'] = $request['username'];
        $data['post_code'] = $request['post_code'];
        $data['city'] = $request['city'];
        $data['town'] = $request['town'];
        $data['country'] = $request['country'];

        $user_meta->username = $request['username'];
        $user_meta->post_code = $request['post_code'];
        $user_meta->city = $request['city'];
        $user_meta->town = $request['town'];
        $user_meta->country = $request['country'];

        $user_meta->save();


        $blacklistUpdated = [];
        $userBlacklist = UsersBlacklist::where('user_id', $id)->get();
        $userTranslId = collect($userBlacklist)->pluck('translator_id')->all();

        $diff = null;
        if ($request['translator_ex']) {
            $diff = array_intersect($userTranslId, $request['translator_ex']);
        }
        if ($diff || $request['translator_ex']) {
            foreach ($request['translator_ex'] as $translatorId) {
                $blacklist = new UsersBlacklist();
                if ($model->id) {
                    $already_exist = UsersBlacklist::translatorExist($model->id, $translatorId);
                    if ($already_exist == 0) {
                        $blacklist->user_id = $model->id;
                        $blacklist->translator_id = $translatorId;
                        $blacklist->save();
                    }
                    $blacklistUpdated [] = $translatorId;
                }

            }
            if ($blacklistUpdated) {
                UsersBlacklist::deleteFromBlacklist($model->id, $blacklistUpdated);
            }
        } else {
            UsersBlacklist::where('user_id', $model->id)->delete();
        }



        if ($request['status'] == '1') {
            if ($model->status != '1') {
//                AdminHelper::enable($model->id);
            }
        } else {
            if ($model->status != '0') {
//                AdminHelper::disable($model->id);
            }
        }
        return $model ? $model : false;
    }

}