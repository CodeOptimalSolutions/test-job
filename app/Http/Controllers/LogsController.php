<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use DTApi\Models\Salary;
use DTApi\Models\Throttles;
use DTApi\Models\Translator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use DTApi\Repository\LogsRepository;
use Arcanedev\LogViewer\Facades\LogViewer;
use DTApi\Repository\AdminBookingRepository;

class LogsController extends Controller
{
    public function index(LogsRepository $logsRepository)
    {
        $logs = $logsRepository->with('user.userMeta')->get();

        return response($logs);
    }

    public function alerts(Request $request, AdminBookingRepository $repository, $count = null)
    {
        if (is_null($count))
            $response = $repository->alerts($request);
        else
            $response = $repository->alertsCount();

        return response($response);
    }

    public function userLoginFailed(Request $request, $count = null)
    {
        if (is_null($count))
            $throttles = Throttles::where('ignore', 0)->with('user')->paginate(15);
        else
            $throttles = Throttles::where('ignore', 0)->count();

        return response($throttles);
    }

    public function bookingExpireNoAccepted(Request $request, AdminBookingRepository $repository, $count = null)
    {
        if (is_null($count))
            $response = $repository->bookingExpireNoAccepted($request);
        else
            $response = $repository->bookingExpireNoAcceptedCount($request);

        return response($response);
    }

    public function noSalaries(Request $request)
    {
        $salaries = Salary::get();
        $jobs = Translator::whereHas('jobs', function ($q) {
            $q->whereIn('status', ['assigned', 'completed', 'withdrawbefore24']);
            $q->where('ignore_no_salary', 0);
        })->whereNull('cancel_at')->with('jobs.user', 'jobs.language', 'jobs.feedback.user', 'jobs.translatorJobRel.user', 'jobs.distance')->get();

        foreach ($jobs as $k => $job) {
            $salary = collect($salaries)->where('user_id', $job['user_id'])->where('customer_id', $job['jobs']['user_id'])->toArray();
            if (count($salary) > 0) unset($jobs[$k]);
        }

        return collect($jobs)->pluck('jobs')->all();
    }

    public function ignoreAlert($type, $id, Request $request, AdminBookingRepository $repository)
    {

        $parts = explode('-', $type);
        if (count($parts) > 0) {
            foreach ($parts as $key => $value) {
                $parts[$key] = ucfirst($value);
            }
            $method = 'ignore' . implode('', $parts);
        } else
            $method = 'ignore' . ucfirst($type);

        if ($id == 'all')
            foreach ($request->get('ignore') as $job_id) {
                $response = $repository->$method($job_id);
            }
        else
            $response = $repository->$method($id);

        return response($response);
    }

    public function ignoreExpiring($id, Request $request, AdminBookingRepository $repository)
    {
        if ($id == 'all')
            foreach ($request->get('ignore') as $job_id) {
                $response = $repository->ignoreExpiring($job_id);
            }
        else
            $response = $repository->ignoreExpiring($id);

        return response($response);
    }

    public function ignoreExpired($id, Request $request, AdminBookingRepository $repository)
    {
        if ($id == 'all')
            foreach ($request->get('ignore') as $job_id) {
                $response = $repository->ignoreExpired($job_id);
            }
        else
            $response = $repository->ignoreExpired($id);

        return response($response);
    }

    public function ignoreThrottle($id, Request $request, AdminBookingRepository $repository)
    {
        if ($id == 'all')
            foreach ($request->get('ignore') as $job_id) {
                $response = $repository->ignoreThrottle($job_id);
            }
        else
            $response = $repository->ignoreThrottle($id);

        return response($response);
    }

    public function ignoreFeedback($id, Request $request, AdminBookingRepository $repository)
    {
        if ($id == 'all')
            foreach ($request->get('ignore') as $job_id) {
                $response = $repository->ignoreFeedback($job_id);
            }
        else
            $response = $repository->ignoreFeedback($id);

        return response($response);
    }

    public function adminLogs()
    {
        return response(LogViewer::all());
    }

    public function showLog($date)
    {
        Log::info($date);
        return response(LogViewer::get($date));
    }

}
