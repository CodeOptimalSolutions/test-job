<?php

namespace DTApi\Http\Controllers;

use DTApi\Helpers\SendSMSHelper;
use DTApi\Models\Job;
use DTApi\Models\User;
use DTApi\Models\UserMeta;
use DTApi\Repository\BookingRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class SMSController
 * @package DTApi\Http\Controllers
 */
class SMSController extends Controller
{

    /**
     * @var BookingRepository
     */
    protected $repository;

    /**
     * BookingController constructor.
     * @param BookingRepository $bookingRepository
     */
    public function __construct(BookingRepository $bookingRepository)
    {
        $this->repository = $bookingRepository;
    }

    /**
     * Handle incoming SMS responses by translators for job offers
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function receive(Request $request)
    {
        Log::info($request->input());

        if ( $request->input('sms_key') == env('SMS_COMM_KEY') ) {
            // Find the translator
            $fromNumber = $request->input('from');
            $translator = User::where('mobile', $fromNumber)->first();

            if (!$translator) {
                // No translator found with this number, so treating it as anon
                Log::info('Translator not found');
                echo trans('sms.translator_not_found');
                return response('', 200);
            }

            // Extract the job id from the message
            $content = str_replace('"', '', trim($request->input('message')));

            // If they respond with "Nej" we ignore it and do not reply
            if ('Nej' == $content) {
                return response('', 200);
            }

            if (strpos($content, 'Avboka') !== false || strpos($content, 'avboka') !== false) {
                echo trans('sms.cancel_receiving_messages');
                return response('', 200);
            }

            // If they repond with just "Ja" we need to let them know we need JobID
            if ('Ja' == $content) {
                echo trans('sms.missing_jobid');
                return response('', 200);
            }

            $parts = explode('Ja', $content);
            $jobId = end($parts);
            Log::info('Job id: ' . $jobId);

            if (!is_numeric($jobId)) {
                // It's not a numeric jobId, failing out
                Log::info('Not a numeric job ID');
                echo trans('sms.not_suitable_reply');
                return response('', 200);
            }

            // Find and check the job
            $job = Job::find($jobId);

            if (!$job) {
                Log::info('Job not found');
                return response('', 200);
            }

            // Prepare job message template variables
            $jobPosterMeta = UserMeta::where('user_id', $job->user_id)->first();
            $date = date('d.m.Y', strtotime($job->due));
            $time = date('H:i', strtotime($job->due));
            $address = $job->address ? $job->address : $jobPosterMeta->address;
            $city = $job->city ? $job->city : $jobPosterMeta->city;
            $instructions = $job->instructions ? $job->instructions : '';
            $jobId = $job->id;

            // Determine job type
            $jobType = SendSMSHelper::determineJobType($job);

            // Check if it's possible to assign the job to the translator
            $status = $this->repository->acceptJobWithId($job->id, $translator);

            if ( $status['status'] != 'success' ) {
                // Job assign failed, reply with an error message
                Log::info('Job assign failed (JobId: ' . $jobId . ') for ' . $translator->email);
                echo trans('sms.job_assign_failed', ['jobId' => $jobId]);
            } else {
                // Job assign succeeded, reply accordingly
                if ('phone' == $jobType) {
                    Log::info('Phone job assign success! (JobId: ' . $jobId . ') for ' . $translator->email);
                    echo trans('sms.phone_job_assign_success', ['time' => $time, 'date' => $date, 'jobId' => $jobId]);
                } else {
                    Log::info('Physical job assign success! (JobId: ' . $jobId . ') for ' . $translator->email);
                    echo trans('sms.physical_job_assign_success', ['jobId' => $jobId, 'time' => $time, 'date' => $date, 'address' => $address, 'town' => $city, 'instructions' => $instructions]);
                }
            }

            // Everything OK
        } else {
            // Key not found
            Log::info('SMS Communications key not set, canceling.');
            return response('');
        }
    }

}