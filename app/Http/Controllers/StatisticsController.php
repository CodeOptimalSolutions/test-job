<?php

namespace DTApi\Http\Controllers;

use Carbon\Carbon;
use DTApi\Models\User;
use DTApi\Http\Requests;
use DTApi\Models\Feedback;
use Illuminate\Http\Request;
use DTApi\Models\Translator;
use Illuminate\Support\Facades\Log;
use DTApi\Repository\BookingRepository;
use DTApi\Repository\FeedBackRepository;

class StatisticsController extends Controller
{
    protected $repository;
    protected $feedBackRepository;

    public function __construct(BookingRepository $repository, FeedBackRepository $feedBackRepository)
    {
        $this->repository = $repository;
        $this->feedBackRepository = $feedBackRepository;
    }

    public function index(Request $request)
    {
        $today = Carbon::today();

        $statistics = [];
        $feedback = [];
        $canceled_sessions = [];
        if ($request->has('date_from') || $request->has('date_to')) {
            $data = $this->prepareData($request);
            $feedback = $this->prepareFeedback($request);
            $statistics = $this->prepareStatistic($data, $request->get('date_from'), $request->get('date_to'));
        }

        $canceled_sessions = $this->cancelledSessions($request);

        unset($request['filter_timetype']);
        unset($request['lang']);
        unset($request['consumer_type']);
        unset($request['booking_type']);
        unset($request['from']);
        unset($request['to']);

        $request['status'] = ['started'];
        $today_sessions = $this->repository->getAll($request, 'all');

        unset($request['status']);
        $request['filter_timetype'] = 'created';
        $request['from'] = $today;
        $made_today = $this->repository->getAll($request, 'all');

        unset($request['filter_timetype']);
        unset($request['from']);
        $request['status'] = ['pending'];
        $request['will_expire_at'] = $today;
        $to_expired_today = $this->repository->getAll($request, 'all');

        unset($request['will_expire_at']);
        $request['status'] = ['timedout'];
        $request['expired_at'] = $today;
        $expired_today = $this->repository->getAll($request, 'all');

        $canceled_today = Translator::where('cancel_at', '>=', $today)->groupBy('job_id')->get();

        $response = [
            'statistics'         => $statistics,
            'today_sessions'     => $today_sessions,
            'made_today'         => $made_today,
            'canceled_today'     => $canceled_today,
            'to_expired_today'   => $to_expired_today,
            'expired_today'      => $expired_today,
            'feedback'           => $feedback,
            'cancelled_sessions' => $canceled_sessions,
        ];

        return response($response);
    }

    private function prepareData(Request $request)
    {
        $request['filter_timetype'] = 'created';
        $request['from'] = $request->get('date_from');
        $request['to'] = $request->get('date_to');

        $bookings = $this->repository->getAll($request, 'all');

        $result = [];
        foreach ($bookings as $k => $booking) {
            $result[$k]['booking_id'] = $booking->id;
            $result[$k]['created_at'] = $booking->b_created_at->format('Y-m-d');
            $result[$k]['end_at'] = $booking->end_at->format('Y-m-d');
            $result[$k]['due'] = $booking->due->format('Y-m-d');
            $result[$k]['expired_at'] = $booking->expired_at->format('Y-m-d');
            $result[$k]['will_expired_at'] = is_null($booking->will_expired_at) ? '' : $booking->will_expired_at->format('Y-m-d');
            $result[$k]['language'] = $booking->language->language;
            $result[$k]['session_time'] = $this->calculateSessionTime($booking->session_time);
            $result[$k]['status'] = $booking->status;
            $result[$k]['booking_type'] = $booking->customer_physical_type ? 'physical' : 'phone';
            $result[$k]['user_type'] = $booking->user->userMeta->consumer_type;
            $result[$k]['manually_handled'] = $booking->manually_handled;
            $array = collect($booking->translatorJobRel)->where('cancel_at', NULL)->toArray();
            $accepted = (!isset($array) || !isset($array[0])) ? '' : collect($booking->translatorJobRel)->where('cancel_at', NULL)->toArray()[0]['created_at'];
            $result[$k]['accepted_at'] = ($accepted != '') ? Carbon::createFromFormat('Y-m-d H:i:s', $accepted)->format('Y-m-d') : '';
        }

        return $result;
    }

    private function prepareStatistic($data, $date_from, $date_to)
    {
        $date = $date_from;
        $i = 0;
        while ($date <= $date_to) {
            $collection = collect($data);
            $result[$i]['avg_session_length'] = is_null($collection->where('due', $date)->avg('session_time')) ? 0 : round($collection->where('due', $date)->avg('session_time'), 2);
            $result[$i]['canceled_before_24'] = $collection->where('created_at', $date)->where('status', 'withdrawbefore24')->count();
            $result[$i]['canceled_after_24'] = $collection->where('created_at', $date)->where('status', 'withdrawafter24')->count();
            $result[$i]['timedout'] = $collection->where('expired_at', $date)->where('status', 'timedout')->count();
            $result[$i]['completed'] = $collection->where('end_at', $date)->where('status', 'completed')->count();
            $result[$i]['accepted'] = $collection->where('accepted_at', $date)->count();
            $result[$i]['accepted_tDay'] = $collection->where('created_at', $date)->where('accepted_at', $date)->count();
            $result[$i]['created_tDay'] = $collection->where('created_at', $date)->count();
            $result[$i]['not_accepted'] = $collection->where('created_at', $date)->where('status', 'pending')->count();
            $result[$i]['manually_handled'] = $collection->where('manually_handled', 'yes')->where('job_type', 'paid')->count();
            $result[$i]['date'] = Carbon::createFromFormat('Y-m-d', $date)->format('d.m.Y');
            $i++;
            $date = date('Y-m-d', strtotime($date . ' + 1 days'));
        }

        return $result;
    }

    private function prepareFeedback(Request $request)
    {
        $data = [];
        $feedback = $this->feedBackRepository->all();
        $users = User::all();
        $customers = collect($users)->where('user_type', '1')->pluck('id')->all();
        $translators = collect($users)->where('user_type', '2')->pluck('id')->toArray();
        $data['customersFeedbacks'] = collect($feedback)->whereIn('by_user_id', $customers)->count();
        $data['customersAvg'] = round(collect($feedback)->whereIn('by_user_id', $customers)->avg('rating'), 2);
        $data['translatorsFeedbacks'] = collect($feedback)->whereIn('by_user_id', $translators)->count();
        $data['translatorsAvg'] = round(collect($feedback)->whereIn('by_user_id', $translators)->avg('rating'), 2);

        return $data;
    }

    private function cancelledSessions(Request $request)
    {
        $data = [];
        $translators = User::where('user_type', '2')->get();
        $translators = collect($translators)->pluck('id')->toArray();
        $cancelled_sessions = Translator::whereNotNull('cancel_at')->whereIn('user_id', $translators);

        $now = Carbon::now()->format('Y-m-d H:00:00');

        $data['this_hour'] = $cancelled_sessions->where('cancel_at', '>=', $now)->count();
        $data['this_hour'] = $cancelled_sessions->where('cancel_at', '>=', $now)->count();

        return $data;
    }

    private function calculateSessionTime($session_time)
    {
        if ($session_time == '') return $session_time;

        $time = explode(':', $session_time);
        $session_time = $time[0] + $time[1] / 60;

        return $session_time;
    }
}
