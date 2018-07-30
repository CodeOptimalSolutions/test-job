<?php

namespace DTApi\Repository;

use Event;
use Carbon\Carbon;
use Monolog\Logger;
use DTApi\Models\Job;
use DTApi\Models\User;
use DTApi\Models\Language;
use DTApi\Models\Throttles;
use Illuminate\Http\Request;
use DTApi\Models\Translator;
use DTApi\Events\JobWasCreated;
use DTApi\Helpers\DateTimeHelper;
use DTApi\Mailers\MailerInterface;
use Illuminate\Support\Facades\DB;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\FirePHPHandler;

/**
 * Class BookingRepository
 * @package DTApi\Repository
 */
class AdminBookingRepository extends BaseRepository
{

    protected $model;
    protected $mailer;
    protected $logger;

    /**
     * @param Job $model
     */
    function __construct(Job $model, MailerInterface $mailer)
    {
        parent::__construct($model);
        $this->mailer = $mailer;
        $this->logger = new Logger('admin_logger');

        $this->logger->pushHandler(new StreamHandler(storage_path('logs/admin/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
    }

    public function getAll(Request $request)
    {
        $requestdata = $request->all();
        $cuser = $request->__authenticatedUser;
        if ($cuser && $cuser->user_type == env('SUPERADMIN_ROLE_ID')) {
            $allJobs = Job::query();

            if (isset($requestdata['id']) && $requestdata['id'] != '') {
                $allJobs->where('id', $requestdata['id']);
                $requestdata = array_only($requestdata, ['id']);
            }

            if (isset($requestdata['lang']) && $requestdata['lang'] != '') {
                $allJobs->whereIn('from_language_id', $requestdata['lang']);
            }
            if (isset($requestdata['status']) && $requestdata['status'] != '') {
                $allJobs->whereIn('status', $requestdata['status']);
            }
            if (isset($requestdata['customer_email']) && $requestdata['customer_email'] != '') {
                $user = DB::table('users')->where('email', $requestdata['customer_email'])->first();
                if ($user) {
                    $allJobs->where('user_id', '=', $user->id);
                }
            }
            if (isset($requestdata['translator_email']) && $requestdata['translator_email'] != '') {
                $user = DB::table('users')->where('email', $requestdata['translator_email'])->first();
                if ($user) {
                    $allJobIDs = DB::table('translator_job_rel')->where('user_id', $user->id)->lists('job_id');
                    $allJobs->whereIn('id', $allJobIDs);
                }
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "created") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('created_at', '>=', $requestdata["from"]);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('created_at', '<=', $to);
                }
                $allJobs->orderBy('created_at', 'desc');
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "due") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('due', '>=', $requestdata["from"]);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('due', '<=', $to);
                }
                $allJobs->orderBy('due', 'desc');
            }

            if (isset($requestdata['job_type']) && $requestdata['job_type'] != '') {
                $allJobs->whereIn('job_type', $requestdata['job_type']);
            }
            $allJobs->orderBy('created_at', 'desc');
            $allJobs->with('user', 'language', 'feedback.user', 'translatorJobRel.user', 'distance');
            $allJobs = $allJobs->paginate(15);

        } else {


            $allJobs = Job::query();

            if (isset($requestdata['id']) && $requestdata['id'] != '') {
                $allJobs->where('id', $requestdata['id']);
                $requestdata = array_only($requestdata, ['id']);
            }

            if ($consumer_type == 'RWS') {
                $allJobs->where('job_type', '=', 'rws');
            } else {
                $allJobs->where('job_type', '=', 'unpaid');
            }
            if (isset($requestdata['lang']) && $requestdata['lang'] != '') {
                $allJobs->whereIn('from_language_id', $requestdata['lang']);
            }
            if (isset($requestdata['status']) && $requestdata['status'] != '') {
                $allJobs->whereIn('status', $requestdata['status']);
            }
            if (isset($requestdata['job_type']) && $requestdata['job_type'] != '') {
                $allJobs->whereIn('job_type', $requestdata['job_type']);
            }
            if (isset($requestdata['customer_email']) && $requestdata['customer_email'] != '') {
                $user = DB::table('users')->where('email', $requestdata['customer_email'])->first();
                if ($user) {
                    $allJobs->where('user_id', '=', $user->id);
                }
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "created") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('created_at', '>=', $requestdata["from"]);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('created_at', '<=', $to);
                }
                $allJobs->orderBy('created_at', 'desc');
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "due") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('due', '>=', $requestdata["from"]);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('due', '<=', $to);
                }
                $allJobs->orderBy('due', 'desc');
            }
            $allJobs->orderBy('created_at', 'desc');
            $allJobs->with('user', 'language', 'feedback.user', 'translatorJobRel.user', 'distance');
            $allJobs = $allJobs->paginate(15);

        }
        return $allJobs;
    }

    public function alerts(Request $request)
    {
        $jobs = Job::all();
        $sesJobs = [];
        $jobId = [];
        $diff = [];
        $i = 0;

        foreach ($jobs as $job) {
            $sessionTime = explode(':', $job->session_time);
            if (count($sessionTime) >= 3) {
                $diff[$i] = ($sessionTime[0] * 60) + $sessionTime[1] + ($sessionTime[2] / 60);

                if ($diff[$i] >= $job->duration) {
                    if ($diff[$i] >= $job->duration * 2) {
                        $sesJobs[$i] = $job;
                        $jobId[] = $job->id;
                    }
                }
                $i++;
            }
        }

//        foreach ($sesJobs as $job) {
//            $jobId [] = $job->id;
//        }

        $languages = Language::where('active', '1')->orderBy('language')->get();
        $requestdata = $request->all();
        $all_customers = DB::table('users')->where('user_type', '1')->lists('email');
        $all_translators = DB::table('users')->where('user_type', '2')->lists('email');

        $cuser = $request->__authenticatedUser;
        $consumer_type = $cuser->userMeta['consumer_type'];

        $allJobs = [];
        if ($cuser && ($cuser->user_type == env('ADMIN_ROLE_ID') || $cuser->user_type == env('SUPERADMIN_ROLE_ID'))) {
            $allJobs = Job::query();
            if($cuser->user_type == env('ADMIN_ROLE_ID'))
            {
                if ($consumer_type == 'RWS') {
                    $allJobs->where('job_type', '=', 'rws');
                } else {
                    $allJobs->where('job_type', '=', 'unpaid');
                }
            }
            if (isset($requestdata['lang']) && $requestdata['lang'] != '') {
                $allJobs->whereIn('from_language_id', $requestdata['lang'])
                    ->where('ignore', 0);
                /*$allJobs->where('jobs.from_language_id', '=', $requestdata['lang']);*/
            }
            if (isset($requestdata['status']) && $requestdata['status'] != '') {
                $allJobs->whereIn('status', $requestdata['status'])
                    ->where('ignore', 0);
                /*$allJobs->where('jobs.status', '=', $requestdata['status']);*/
            }
            if (isset($requestdata['customer_email']) && $requestdata['customer_email'] != '') {
                $user = DB::table('users')->where('email', $requestdata['customer_email'])->first();
                if ($user) {
                    $allJobs->where('user_id', '=', $user->id)
                        ->where('ignore', 0);
                }
            }
            if (isset($requestdata['translator_email']) && $requestdata['translator_email'] != '') {
                $user = DB::table('users')->where('email', $requestdata['translator_email'])->first();
                if ($user) {
                    $allJobIDs = DB::table('translator_job_rel')->where('user_id', $user->id)->lists('job_id');
                    $allJobs->whereIn('id', $allJobIDs)
                        ->where('ignore', 0);
                }
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "created") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('created_at', '>=', $requestdata["from"])
                        ->where('ignore', 0);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('created_at', '<=', $to)
                        ->where('ignore', 0);
                }
                $allJobs->orderBy('created_at', 'desc');
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "due") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('due', '>=', $requestdata["from"])
                        ->where('ignore', 0);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('due', '<=', $to)
                        ->where('ignore', 0);
                }
                $allJobs->orderBy('due', 'desc');
            }

            if (isset($requestdata['job_type']) && $requestdata['job_type'] != '') {
                $allJobs->whereIn('job_type', $requestdata['job_type'])
                    ->where('ignore', 0);
                /*$allJobs->where('jobs.job_type', '=', $requestdata['job_type']);*/
            }
            $allJobs->where('ignore', '0');
            $allJobs->whereIn('id', $jobId);
            $allJobs->with('user', 'language', 'feedback.user', 'translatorJobRel.user', 'distance');
            $allJobs->orderBy('created_at', 'desc');
            $allJobs = $allJobs->paginate(15);
        }

        return ['allJobs' => $allJobs, 'languages' => $languages, 'all_customers' => $all_customers, 'all_translators' => $all_translators, 'requestdata' => $requestdata];
    }

    public function alertsCount()
    {
        $jobs = Job::where('ignore', 0)->get();
        $sesJobs = [];
        $jobId = [];
        $diff = [];
        $i = 0;

        foreach ($jobs as $job) {
            $sessionTime = explode(':', $job->session_time);
            if (count($sessionTime) >= 3) {
                $diff[$i] = ($sessionTime[0] * 60) + $sessionTime[1] + ($sessionTime[2] / 60);

                if ($diff[$i] >= $job->duration) {
                    if ($diff[$i] >= $job->duration * 2) {
                        $sesJobs[$i] = $job;
                        $jobId[] = $job->id;
                    }
                }
                $i++;
            }
        }

        return ['count' => count($jobId)];
    }

    public function userLoginFailed()
    {
        $throttles = Throttles::where('ignore', 0)->with('user')->paginate(15);

        return view('admin.jobs.failed-logins', ['throttles' => $throttles]);
    }

    public function bookingExpireNoAccepted(Request $request)
    {
        $languages = Language::where('active', '1')->orderBy('language')->get();
        $requestdata = $request->all();
        $all_customers = DB::table('users')->where('user_type', '1')->lists('email');
        $all_translators = DB::table('users')->where('user_type', '2')->lists('email');

        $cuser = $request->__authenticatedUser;
        $consumer_type = $cuser->user_meta['consumer_type'];


        if ($cuser && ($cuser->user_type == env('ADMIN_ROLE_ID') || $cuser->user_type == env('SUPERADMIN_ROLE_ID'))) {
            $allJobs = Job::query();
            if($cuser->user_type == env('ADMIN_ROLE_ID'))
            {
                if ($consumer_type == 'RWS') {
                    $allJobs->where('job_type', '=', 'rws');
                } else {
                    $allJobs->where('job_type', '=', 'unpaid');
                }
            }
            $allJobs->where('jobs.ignore_expired', 0);
            $allJobs->with('user', 'language', 'feedback.user', 'translatorJobRel.user', 'distance');
            if (isset($requestdata['lang']) && $requestdata['lang'] != '') {
                $allJobs->whereIn('from_language_id', $requestdata['lang'])
                    ->where('status', 'pending')
                    ->where('ignore_expired', 0);
                /*$allJobs->where('from_language_id', '=', $requestdata['lang']);*/
            }
            if (isset($requestdata['status']) && $requestdata['status'] != '') {
                $allJobs->whereIn('status', $requestdata['status'])
                    ->where('status', 'pending')
                    ->where('ignore_expired', 0);
                /*$allJobs->where('status', '=', $requestdata['status']);*/
            }
            if (isset($requestdata['customer_email']) && $requestdata['customer_email'] != '') {
                $user = DB::table('users')->where('email', $requestdata['customer_email'])->first();
                if ($user) {
                    $allJobs->where('user_id', '=', $user->id)
                        ->where('status', 'pending')
                        ->where('ignore_expired', 0);
                }
            }
            if (isset($requestdata['translator_email']) && $requestdata['translator_email'] != '') {
                $user = DB::table('users')->where('email', $requestdata['translator_email'])->first();
                if ($user) {
                    $allJobIDs = DB::table('translator_job_rel')->where('user_id', $user->id)->lists('job_id');
                    $allJobs->whereIn('id', $allJobIDs)
                        ->where('status', 'pending')
                        ->where('ignore_expired', 0);
                }
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "created") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('created_at', '>=', $requestdata["from"])
                        ->where('status', 'pending')
                        ->where('ignore_expired', 0);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('created_at', '<=', $to)
                        ->where('status', 'pending')
                        ->where('ignore_expired', 0);
                }
                $allJobs->orderBy('created_at', 'desc');
            }
            if (isset($requestdata['filter_timetype']) && $requestdata['filter_timetype'] == "due") {
                if (isset($requestdata['from']) && $requestdata['from'] != "") {
                    $allJobs->where('due', '>=', $requestdata["from"])
                        ->where('status', 'pending')
                        ->where('ignore_expired', 0);
                }
                if (isset($requestdata['to']) && $requestdata['to'] != "") {
                    $to = $requestdata["to"] . " 23:59:00";
                    $allJobs->where('due', '<=', $to)
                        ->where('status', 'pending')
                        ->where('ignore_expired', 0);
                }
                $allJobs->orderBy('due', 'desc');
            }

            if (isset($requestdata['job_type']) && $requestdata['job_type'] != '') {
                $allJobs->whereIn('job_type', $requestdata['job_type'])
                    ->where('status', 'pending')
                    ->where('ignore_expired', 0);
                /*$allJobs->where('job_type', '=', $requestdata['job_type']);*/
            }
            $currentTime = Carbon::now()->format('Y-m-d H:i:s');
            $allJobs->where('status', 'pending')
                ->where('ignore_expired', 0);
//                ->where(function ($query) use ($currentTime) {
//                    $query->where(function ($q) use ($currentTime) {
//                        $q->where(DB::raw('time_to_sec(timediff(  due , created_at )) / 3600'), '>=', 24)
//                            ->where(DB::raw('time_to_sec(timediff(  due , created_at )) / 3600'), '<=', 72)
//                            ->where(DB::raw("time_to_sec(timediff(  '$currentTime' , created_at )) / 60"), '>=', 240);
//                    })
//                        ->orWhere(function ($q) use ($currentTime) {
//                            $q->where(DB::raw('time_to_sec(timediff(  due , created_at )) / 3600'), '>=', 72)
//                                ->where(DB::raw("time_to_sec(timediff(  '$currentTime' , due )) / 3600"), '<=', 96);
//                        });
//                });
//                ->where('due', '<=', Carbon::now()->addHours(24));
            $allJobs->orderBy('due', 'asc');
            $allJobs = $allJobs->paginate(15);

        }

        return ['allJobs' => $allJobs, 'languages' => $languages, 'all_customers' => $all_customers, 'all_translators' => $all_translators, 'requestdata' => $requestdata];
    }

    public function bookingExpireNoAcceptedCount()
    {

        $allJobs = Job::query();
        $allJobs->where('jobs.ignore_expired', 0)->where('status', 'pending');
        $allJobs = $allJobs->count();

        return ['count' => $allJobs];
    }

    public function ignoreExpiring($id)
    {
        $job = Job::find($id);
        $job->ignore = 1;
        $job->save();
        return ['success', 'Changes saved'];
    }

    public function ignoreExpired($id)
    {
        $job = Job::find($id);
        $job->ignore_expired = 1;
        $job->save();
        return ['success', 'Changes saved'];
    }

    public function ignoreThrottle($id)
    {
        $throttle = Throttles::find($id);
        $throttle->ignore = 1;
        $throttle->save();
        return ['success', 'Changes saved'];
    }

    public function ignoreFeedback($id)
    {
        $job = Job::find($id);
        $job->ignore_feedback = 1;
        $job->save();
        return ['success', 'Changes saved'];
    }

    public function ignorePhysicalPhone($id)
    {
        $job = Job::find($id);
        $job->ignore_physical_phone = 1;
        $job->save();
        return ['success', 'Changes saved'];
    }

    public function ignorePhysical($id)
    {
        $job = Job::find($id);
        $job->ignore_physical = 1;
        $job->save();
        return ['success', 'Changes saved'];
    }

    public function ignoreFlagged($id)
    {
        $job = Job::find($id);
        $job->ignore_flagged = 1;
        $job->save();
        return ['success', 'Changes saved'];
    }

    public function ignoreNoSalary($id)
    {
        $job = Job::find($id);
        $job->ignore_no_salary = 1;
        $job->save();
        return ['success', 'Changes saved'];
    }

}