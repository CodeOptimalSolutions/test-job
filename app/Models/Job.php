<?php

namespace DTApi\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Job
 * @package DTApi\Models
 */
class Job extends Model
{
    /**
     * @var array
     */
    protected $dates = ['due', 'created_at', 'b_created_at', 'expired_at', 'will_expired_at', 'end_at'];
    /**
     * @var array
     */
    protected $fillable = [
        'description',
        'from_language_id',
        'to_language_id',
        'duration',
        'user_id',
        'user_email',
        'status',
        'immediate',
        'gender',
        'certified',
        'due',
        'job_type',
        'customer_phone_type',
        'customer_physical_type',
        'specific_transaltor',
        'admin_comments',
        'session_time',
        'admincomments',
        'b_created_at',
        'expired_at',
        'ignore',
        'flagged',
        'ignore_expired',
        'reference',
        'will_expire_at',
        'by_admin'
    ];

    /**
     * Function to get the user info
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Function to get the user language
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function language()
    {
        return $this->belongsTo(Language::class, 'from_language_id', 'id');
    }

    /**
     * Function to get the user language
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function translatorJobRel()
    {
        return $this->hasMany(Translator::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function feedback()
    {
        return $this->hasMany(Feedback::class);
    }
    
    public function distance()
    {
        return $this->hasOne(Distance::class);
    }

    /**
     * Function to get the jobs for the particular transltors
     * @param $user_id
     * @param string $dateType
     * @return mixed
     */
    public static function getTranslatorJobs($user_id, $dateType = '')
    {

        $datewhere = [];

        $query = Translator::where('user_id', $user_id);

        if (!empty($dateType)) {
            if ($dateType == 'historic') {
                $datewhere = ['completed', 'withdrawbefore24', 'withdrawafter24', 'timedout'];

                $results = $query->whereHas('jobs', function ($query) use ($datewhere) {
                    $query->whereIn('status', $datewhere)->orderBy('due', 'asc');
                })->get();
            } else {
                $datewhere = ['pending', 'assigned', 'started'];

                $results = $query->whereHas('jobs', function ($query) use ($datewhere) {
                    $query->whereIn('status', $datewhere)->orderBy('due', 'asc');
                    $query->where('cancel_at', NULL);
                })->with('jobs.language', 'jobs.user.userMeta', 'jobs.user.average', 'jobs.feedback', 'jobs.translatorJobRel')->get();
            }
        }

        return $results;

    }

    /**
     * Function to check is the job is assigned to Specific Translator or Not
     * @param $userId
     * @param $jobs_id
     * @return string
     */
    public static function assignedToPaticularTranslator($userId, $jobs_id)
    {
        $checkJobForUser = Job::where(['id' => $jobs_id])->where(function ($query) use ($userId) {
            $query->where('specific_transaltor', '=', $userId)
                ->orWhere('specific_transaltor', '=', 0)
                ->orWhere('specific_transaltor', '=', Null);
        })->first();

        $jobResult = $checkJobForUser;
        $result = "";
        if (!empty($jobResult)) {
            $result = "SpecificJob";
        } else {
            $result = "NotaSpecificJob";
        }
        return $result;
    }

    /**
     * If the booking type is only Physical then this function will compare the towns of Customer with Translators.
     * @param $userId
     * @param $trid
     * @return bool
     */
    public static function checkTowns($userId, $trid)
    {
        $usertowns = UserTowns::where('user_id', $userId)->get();
        $newusertowns = collect($usertowns)->pluck('town_id')->all();

        $translatortowns = UserTowns::where('user_id', $trid)->get();
        $newtrtowns = collect($translatortowns)->pluck('town_id')->all();

        $result = array_intersect($newusertowns, $newtrtowns);
        if (count($result) > 0) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Function to check the particular job if the job is canceled by admin. Then translator cannot see that job in the potential jobs.
     * @param $userId
     * @param $job
     * @return string
     */
    public static function checkParticularJob($userId, $job)
    {
//        $resl = Translator::where('job_id', $jobs_id)->where('user_id', $userId)->orderBy('id')->first();
        $result = $job->translatorJobRel->where('user_id', $userId)->first();
        $usercheck = "";
        $jobs_id = $job->id;
//        foreach ($result as $resl) {
        if (!is_null($result)) {
            $getnextid = $result->id + 1;
            $nextjobrel = Translator::where('job_id', $jobs_id)->where('id', $getnextid)->orderBy('id', 'desc')->first();
            if (!is_null($nextjobrel)) {
                if ($nextjobrel->cancel_at != NULL) {
                    $usertype = User::find($nextjobrel->user_id);
                    if (($usertype->user_type == 3) || ($usertype->user_type == 4)) {
                        $usercheck = "userCanNotAcceptJob";
                    } else {
                        $usercheck = "userCanAcceptJob";
                    }
                } else {
                    $usercheck = "userCanAcceptJob";
                }
            } else {
                $usercheck = "userCanAcceptJob";
            }
        } else {
            $usercheck = "userCanAcceptJob";
        }
//        }
        return $usercheck;
    }

    /**
     * Function to get jobs for historic tab
     * @param $user_id
     * @param string $dateType
     * @param int $page
     * @return mixed
     */
    public static function getTranslatorJobsHistoric($user_id, $dateType = '', $page = 1)
    {
        $end = 15;
        $start = ($page - 1) * 15;

        $datewhere = [];

        $query = Translator::where('user_id', $user_id);

        if (!empty($dateType)) {
            if ($dateType == 'historic') {
                $datewhere = ['completed', 'withdrawbefore24', 'withdrawafter24', 'timedout'];
                $orderby = 'desc';
            } else {
                $datewhere = ['pending', 'assigned', 'started'];
                $orderby = 'asc';
            }
        }

        $query->whereHas('jobs', function ($q) use ($datewhere, $orderby) {
            $q->whereIn('status', $datewhere)->orderBy('due', $orderby);
        });

        if (!empty($dateType)) {
            if ($dateType == 'historic') {
                $query->whereNull('cancel_at');
                $query->groupBy('job_id');
            }

            $jobs = $query->get();

            $results = Job::whereIn('id', collect($jobs)->pluck('job_id')->all())->orderBy('due', 'desc')->with('user.userMeta', 'user.average', 'language', 'feedback', 'distance', 'translatorJobRel.user.average')->paginate(15);

            return $results;
        }

        $results = $query->with('jobs.user.userMeta', 'jobs.user.average', 'jobs.language', 'jobs.feedback', 'jobs.distance', 'jobs.translatorJobRel.user.average')->orderBy('completed_at', 'desc')->paginate(15);

        return $results;

    }

    /**
     * @param $job_id
     * @return mixed
     */
    public static function checkAssignJob($job_id)
    {
        return Translator::where(['job_id' => $job_id, 'cancel_at' => NULL])->count();
    }

    /**
     * Function to get the total of the historic jobs of paticular translator
     * @param $user_id
     * @param string $dateType
     * @return mixed
     */
    public static function getTranslatorJobsHistoricTotal($user_id, $dateType = '')
    {

        $datewhere = [];

        $query = Translator::where('user_id', $user_id);

        if (!empty($dateType)) {
            if ($dateType == 'historic') {
                $datewhere = ['completed', 'withdrawbefore24', 'withdrawafter24', 'timedout'];
            } else {
                $datewhere = ['pending', 'assigned', 'started'];
            }
        }

        $results = $query->whereHas('jobs', function ($query) use ($datewhere) {
            $query->whereIn('status', $datewhere)->orderBy('due', 'asc');
        })->count();

        return $results;

    }

    /**
     * Function to get the jobs for the particular translator for the potential tab
     * @param $userId
     * @param bool $job_type
     * @param string $status
     * @param array $languages
     * @param string $gender
     * @param string $translator_level
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public static function getJobs($userId, $job_type = false, $status = 'pending', $languages = [], $gender = '', $translator_level = '')
    {
        $blacklist = UsersBlacklist::where('translator_id', $userId)->get();

        $blacklist = collect($blacklist)->pluck('user_id')->all();
        $jobs = Job::query();

        if ($job_type) {
            $jobs->where('job_type', $job_type);
        }

//        if ($userId) {
//            $jobs->where('specific_transaltor', $userId);
//        }

        if ($languages) {
            $jobs->whereIn('from_language_id', $languages);
        }

        if (!empty($gender)) {
            $jobs->where(function ($query) use ($gender) {
                $query->where('gender', $gender)
                    ->orWhere('gender', null);
            });
        }

        if (!empty($translator_level)) {
            if ($translator_level == 'Certified') {
                $jobs->where(function ($query) {
                    $query->where('certified', 'yes')
                        ->orWhere('certified', null)
                        ->orWhere('certified', 'both');
                });
            }
            elseif ($translator_level == 'Certified with specialisation in law') {
                $jobs->where(function ($query) {
                    $query->where('certified', 'yes')
                        ->orWhere('certified', null)
                        ->orWhere('certified', 'law')
                        ->orWhere('certified', 'n_law')
                        ->orWhere('certified', 'both');
                });
            }
            elseif($translator_level == 'Certified with specialisation in health care')
            {
                $jobs->where(function ($query) {
                    $query->where('certified', 'yes')
                        ->orWhere('certified', null)
                        ->orWhere('certified', 'health')
                        ->orWhere('certified', 'n_health')
                        ->orWhere('certified', 'both');
                });
            }
            else if ($translator_level == 'Layman' || $translator_level == 'Read Translation courses') {
                $jobs->where(function ($query) {
                    $query->where('certified', 'normal')
                        ->orWhere('certified', null)
                        ->orWhere('certified', 'n_law')
                        ->orWhere('certified', 'n_health')
                        ->orWhere('certified', 'both');
                });
            }
        }

        $jobs->where('status', $status);
        $jobs->whereNotIn('user_id', $blacklist);
        $jobs->where('due', '>=', date('Y-m-d H:i:s'));
        $jobs->orderBy('due', 'asc');
        $jobs->with('language');

        return $jobs->get();

    }

    /**
     * Function to check if the translator is not already booked for the particular due time
     * @param $job_id
     * @param $user_id
     * @param $due
     * @return bool
     */
    public static function isTranslatorAlreadyBooked($job_id, $user_id, $due)
    {
        $jobsCount = Job::where('status', 'assigned')->where('due', '>=', $due)->where('due', '<=', DB::raw('due + INTERVAL duration MINUTE'))->whereHas('translatorJobRel', function ($q) use ($job_id, $user_id) {
            $q->where('job_id', $job_id);
            $q->where('user_id', $user_id);
            $q->where('cancel_at', NULL);
        })->count();

        return $jobsCount > 0 ? true : false;
    }

    /**
     * Function to save the particular job related data, like when the job is created, updated, and updated by which user
     * @param $user_id
     * @param $job_id
     * @return mixed
     */
    public static function insertTranslatorJobRel($user_id, $job_id)
    {
        $data = array();
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['user_id'] = $user_id;
        $data['job_id'] = $job_id;
        return DB::table('translator_job_rel')->insertGetId($data);
    }

    /**
     * Function to delete the entries from the table translator_job_rel if the job is canceled.
     * @param $user_id
     * @param $job_id
     */
    public static function deleteTranslatorJobRel($user_id, $job_id)
    {
        $canceldate = date('Y-m-d H:i:s');
        Translator::where('user_id', '=', $user_id)->where('job_id', '=', $job_id)->orderBy('created_at', 'desc')->first()->update(['cancel_at' => $canceldate]);
    }

    /**
     * Function to count the translator jobs total
     * @param $user_id
     * @param string $status
     * @return mixed
     */
    public static function getTranslatorJobsCount($user_id, $status = '')
    {
        $query = Job::query();
        if (!empty($status)) {
            $query->where('status', $status);
        }
        $query->whereHas('translatorJobRel', function ($q) use ($user_id) {
            $q->where('user_id', $user_id);
        });
        return $query->count();
    }

    /**
     * Function to count the customer jobs total
     * @param $user_id
     * @param string $status
     * @return mixed
     */
    public static function getCustomerJobsCount($user_id, $status = '')
    {
        $query = Job::where('user_id', $user_id);
        if (!empty($status)) {
            $query->where('status', $status);
        }
        return $query->count();
    }

    /**
     * Function to get the details of the jobs that are assigned to translator
     * @param $job
     * @return null
     */
    public static function getJobsAssignedTranslatorDetail($job)
    {
//        $query = Translator::select('user_id')->where('job_id', $job_id)->orderBy('created_at', 'desc')->first();
        $query = $job->translatorJobRel()->where('cancel_at', NULL)->first();
        if (!is_null($query)) {
            $user = $query->user;
            return $user;
        } else {
            return null;
        }
    }

    /**
     * Function to get the details of the user for the job
     * @param $job_id
     * @return mixed
     */
    public static function getJobsUserDetail($job_id)
    {
        $job = Job::findOrFail($job_id);
        return $job->user()->get()->first();
    }
}
