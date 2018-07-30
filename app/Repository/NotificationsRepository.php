<?php

namespace DTApi\Repository;

use Carbon\Carbon;
use Monolog\Logger;
use DTApi\Models\Job;
use DTApi\Helpers\TeHelper;
use DTApi\Events\SessionStarted;
use DTApi\Helpers\DateTimeHelper;
use DTApi\Mailers\MailerInterface;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\FirePHPHandler;
use DTApi\Notifications\INotifications;
use DTApi\Events\SessionStartedTranslator;

class NotificationsRepository implements INotifications
{

    protected $mailer;
    protected $logger;
    protected $bookingRepository;

    public function __construct(MailerInterface $mailer, BookingRepository $bookingRepository)
    {
        $this->mailer = $mailer;
        $this->bookingRepository = $bookingRepository;
        $this->logger = new Logger('admin_logger');

        $this->logger->pushHandler(new StreamHandler(storage_path('logs/cron/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
    }

    //cron for session start
    public function sessionStart()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'assigned')->where('due', '<=', $currentTime)->get();

        $this->logger->addInfo('Start assign jobs ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $job->status = 'started';
            $job->save();
            $translator = Job::getJobsAssignedTranslatorDetail($job);
            event(new SessionStarted($job));
            event(new SessionStartedTranslator($translator->id));
        }
    }

    //Booking immidiate not accepted
    public function bookingNotAccepted()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')->where('immediate', 'yes')->where('emailsent', '0')->where('due', '<', $currentTime)->get();

        $this->logger->addInfo('jobs ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $user = $job->user()->get()->first();

            if (!empty($job->user_email)) {
                $email = $job->user_email;
            } else {
                $email = $user->email;
            }
            $name = $user->name;
            $subject = 'Beklagar att vi inte kunde lösa ert tolkbehov (bokning # ' . $job->id . ')';
            $data = [
                'user' => $user,
                'job'  => $job
            ];
            $this->mailer->send($email, $name, $subject, 'emails.job-immediate-not-accepted', $data);

            $job->emailsent = 1;
            $job->status = 'timedout';
            $job->expired_at = date('Y-m-d H:i:s');
            $job->save();
            $this->bookingRepository->sendExpiredNotification($job, $user);        // send Expired Push Notification to user
            $this->logger->addInfo('job  ' . $job->id, ['job' => $job->toArray()]);
        }
    }

    /*
     * Booking within 24 hours: Suppliers/translators have 90minutes to respond - if not, we email customer informing we haven’t managed to arrange a customer.
     */
    public function bookingWithing24h()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('emailsent', '0')
            ->whereRaw('time_to_sec(timediff(  due , created_at )) / 3600 <= 24')
            ->where(function ($q) use ($currentTime) {
                $q->whereRaw("(time_to_sec(timediff(  '$currentTime' , created_at )) / 60) >= 90 ")
                    ->orWhere('due', '<', $currentTime);
            })->get();

        $this->logger->addInfo('Booking within 24 hours jobs ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $user = $job->user()->get()->first();
            if (!empty($job->user_email)) {
                $email = $job->user_email;
            } else {
                $email = $user->email;
            }
            $name = $user->name;
            $subject = 'Beklagar att vi inte kunde lösa ert tolkbehov (bokning # ' . $job->id . ')';
            $data = [
                'user' => $user,
                'job'  => $job
            ];
            $this->mailer->send($email, $name, $subject, 'emails.job-not-accepted', $data);

            $job->status = 'timedout';
            $job->expired_at = date('Y-m-d H:i:s');
            $job->emailsent = 1;
            $job->save();
            $this->bookingRepository->sendExpiredNotification($job, $user);        // send Expired Push Notification to user
            $this->logger->addInfo('job  ' . $job->id, ['job' => $job->toArray()]);
        }
    }

    //If no translator has accepted after 45 minutes - an email is sent to virpal@digitaltolk.se informing that a booking has been placed but not accepted by any supplier.
    public function bookingNotAccepted45m()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('emailsenttovirpal', '0')
            ->whereRaw('time_to_sec(timediff(  due , created_at )) / 3600 <= 24')
            ->whereRaw("time_to_sec(timediff(  '$currentTime' , created_at )) / 60 >= 45")
            ->get();

        $this->logger->addInfo('Booking within 24 hours jobs translator not accepted ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $user = $job->user()->get()->first();
            if ($user->userMeta->consumer_type != 'ngo' && $user->userMeta->consumer_type != 'RWS') {
                $email = config('app.admin_email');
                $name = 'Admin';
                $subject = 'A booking was placed an hour ago, please consider manually handling it - booking # ' . $job->id;
                $data = [
                    'user'  => $user,
                    'job'   => $job,
                    'hours' => 'an hour'
                ];
                $this->mailer->send($email, $name, $subject, 'emails.job-not-accepted-admin', $data);
                $job->emailsenttovirpal = 1;
                $job->save();
                $this->logger->addInfo('job  ' . $job->id, ['job' => $job->toArray()]);
            } else {
                $job->emailsenttovirpal = 1;
                $job->save();
                $this->logger->addInfo('NGO or RWS customer. Admin email not sent. job  ' . $job->id, ['job' => $job->toArray()]);
            }


        }
    }

    //Booking after 24 hours: Suppliers/translators have 16 hours to respond - if not, we email customer informing we haven’t managed to arrange a customer.
    public function bookingAfter24h()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('emailsent', '0')
            ->whereRaw('time_to_sec(timediff(  due , created_at )) / 3600 > 24')
            ->whereRaw("time_to_sec(timediff(  '$currentTime' , created_at )) / 60 >= 960")
            ->whereRaw("time_to_sec(timediff(  due , created_at )) / 3600 < 72")
            ->get();

        $this->logger->addInfo('Booking after 24 hours jobs ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $user = $job->user()->get()->first();
            if (!empty($job->user_email)) {
                $email = $job->user_email;
            } else {
                $email = $user->email;
            }
            $name = $user->name;
            $subject = 'Beklagar att vi inte kunde lösa ert tolkbehov #' . $job->id;
            $data = [
                'user' => $user,
                'job'  => $job
            ];
            $this->mailer->send($email, $name, $subject, 'emails.job-not-accepted', $data);

            $job->status = 'timedout';
            $job->expired_at = date('Y-m-d H:i:s');
            $job->emailsent = 1;
            $job->save();
            $this->bookingRepository->sendExpiredNotification($job, $user);        // send Expired Push Notification to user
            $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
        }
    }

    //If no translator has accepted after 6 hours - an email is sent to virpal@digitaltolk.se informing that a booking has been placed but not accepted by any supplier.
    public function bookingNotAcceptedAfter6h()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('emailsenttovirpal', '0')
            ->whereRaw('time_to_sec(timediff(  due , created_at )) / 3600 > 24')
            ->whereRaw("time_to_sec(timediff(  '$currentTime' , created_at )) / 60 >= 360")
            ->get();

        $this->logger->addInfo('Booking within 24 hours jobs no translator accepted ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $user = $job->user()->get()->first();
            if ($user->userMeta->consumer_type != 'ngo' && $user->userMeta->consumer_type != 'RWS') {
                $email = config('app.admin_email');
                $name = 'Admin';
                $subject = 'Reminder About Job # ' . $job->id;
                $data = [
                    'user'  => $user,
                    'job'   => $job,
                    'hours' => 'six hours'
                ];

                $this->mailer->send($email, $name, $subject, 'emails.job-not-accepted-admin', $data);

                $job->emailsenttovirpal = 1;
                $job->save();
                $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
            } else {
                $job->emailsenttovirpal = 1;
                $job->save();
                $this->logger->addInfo('NGO or RWS customer. Admin email not sent. job  ' . $job->id, ['job' => $job->toArray()]);
            }
        }
    }

    //end session after 8 hours
    public function endSessionAfter8h()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'started')
            ->whereRaw("time_to_sec(timediff(  '$currentTime' , due )) / 60 >= 480")
            ->with('translatorJobRel.user')
            ->get();

        $this->logger->addInfo('End session after 8 hours ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $job->end_at = date('Y-m-d H:i:s');
            $job->status = 'completed';

            $completeddate = date('Y-m-d H:i:s');
            $jobid = $job->id;
            $duedate = $job->due;
            $start = date_create($duedate);
            $end = date_create('now');
            $diff = date_diff($end, $start);
            $interval = $diff->h . ':' . $diff->i . ':' . $diff->s;
            $job->end_at = date('Y-m-d H:i:s');
            $job->status = 'completed';
            $job->session_time = $interval;

            $user = $job->user()->get()->first();
            if (!empty($job->user_email)) {
                $email = $job->user_email;
            } else {
                $email = $user->email;
            }
            $name = $user->name;
            $subject = 'Information om avslutad tolkning för bokningsnummer # ' . $job->id;
            $session_time = $diff->h . ' tim ' . $diff->i . ' min';
            $data = [
                'user'         => $user,
                'job'          => $job,
                'session_time' => $session_time,
                'for_text'     => 'faktura'
            ];

            $this->mailer->send($email, $name, $subject, 'emails.session-ended', $data);
            $user = $job->translatorJobRel->where('completed_at', Null)->where('cancel_at', Null)->first();

            $email = $user->user->email;
            $name = $user->user->name;
            $subject = 'Information om avslutad tolkning för bokningsnummer # ' . $job->id;
            $data = [
                'user'         => $user,
                'job'          => $job,
                'session_time' => $session_time,
                'for_text'     => 'lön'
            ];
            $this->mailer->send($email, $name, $subject, 'emails.session-ended', $data);

            $job->save();
            $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
        }
    }

    /* START 16 HOUR EMAIL*/
    public function emailAfter16h()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $currentTimes = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('cust_16_hour_email', '0')
            ->whereRaw("time_to_sec(timediff( due , created_at )) / 3600 > 24")
            ->whereRaw("time_to_sec(timediff( due , created_at )) / 3600 <= 72")
            ->get();

        $this->logger->addInfo('16 Hour Email ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $created_at = $job->created_at;
            $currentTimeStr = strtotime($currentTimes);
            $a16HoursCurrentTime = strtotime(date('Y-m-d H:i', strtotime('+16 hours', strtotime($created_at))));
            if ($a16HoursCurrentTime < $currentTimeStr) {
                $temp = $job;
                $user = $temp->user()->get()->first();
                $due_Date = explode(" ", $temp->due);
                $due_date = $due_Date[0];
                $due_time = $due_Date[1];

                if (!empty($temp->user_email)) {
                    $email = $temp->user_email;
                } else {
                    $email = $user->email;
                }
                $name = $user->name;
                $subject = 'Beklagar att vi inte kunde lösa ert tolkbehov för bokningsnr: # ' . $job->id;
                $data = [
                    'user' => $user,
                    'job'  => $temp
                ];
                $this->mailer->send($email, $name, $subject, 'emails.job-not-acceptednew', $data);

                $temp->cust_16_hour_email = 1;
                $temp->save();
                $this->logger->addInfo(' job  ' . $temp->id, ['job' => $temp->toArray()]);
            }
        }
    }

    /* START 48 HOUR EMAIL*/
    public function emailBefore48h()
    {
        $currentTimes = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('cust_48_hour_email', '0')
            ->whereRaw("time_to_sec(timediff( due , created_at )) / 3600 > 72")
            ->get();

        $this->logger->addInfo('48 Hour Email ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $created_at = $job->due;
            $currentTimeStr = strtotime($currentTimes);
            $a48HoursCurrentTime = strtotime(date('Y-m-d H:i', strtotime('-48 hours', strtotime($created_at))));
            if ($a48HoursCurrentTime < $currentTimeStr) {
                $temp = $job;

                $user = $temp->user()->get()->first();
                if (!empty($temp->user_email)) {
                    $email = $temp->user_email;
                } else {
                    $email = $user->email;
                }
                $name = $user->name;
                $subject = 'Beklagar att vi inte kunde lösa ert tolkbehov för bokningsnr: # ' . $job->id;
                $data = [
                    'user' => $user,
                    'job'  => $temp
                ];
                $this->mailer->send($email, $name, $subject, 'emails.job-not-acceptednew', $data);

                $temp->status = 'timedout';
                $temp->expired_at = date('Y-m-d H:i:s');
                $temp->cust_48_hour_email = 1;
                $temp->save();
                $this->logger->addInfo(' job  ' . $temp->id, ['job' => $temp->toArray()]);
            }
        }
    }

    /*
    * Code for check 4 hours after booking time 24-72 and due-60 hours for 72+ to send push notification to translators again
    */
    public function due60h()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('sended_push_again', '0')
            ->whereRaw('time_to_sec(timediff(  due , created_at )) / 3600 >= 24')
            ->whereRaw('time_to_sec(timediff(  due , created_at )) / 3600 <= 72')
            ->whereRaw("time_to_sec(timediff(  '$currentTime' , created_at )) / 60 >= 240")
            ->get();

        $this->logger->addInfo('Send push notification after 4 hour for 24-72 ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {

            $job->sended_push_again = 1;
            $job->save();

            $job_data = $this->bookingRepository->jobToData($job);

            $this->bookingRepository->sendNotificationTranslator($job, $job_data, '*');
            $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
        }

        $jobs = Job::where('status', 'pending')
            ->where('immediate', 'no')
            ->where('sended_push_again', '0')
            ->whereRaw('time_to_sec(timediff(  due , created_at )) / 3600 >= 72')
            ->whereRaw("time_to_sec(timediff(  '$currentTime' , due )) / 3600 <= 60")
            ->get();

        $this->logger->addInfo('Send push notification before 60 hours to due 72+ ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {

            $job->sended_push_again = 1;
            $job->save();

            $job_data = $this->bookingRepository->jobToData($job);

            $this->bookingRepository->sendNotificationTranslator($job, $job_data, '*');
            $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
        }
    }

    /**
     *  this function is for checking the session starting time, if yes send Push to Translator/Customer to remind them for starting session
     */
    public function checkingSessionStartRemindTime()
    {
        $remind_cron_interval = config('app.sessionReminderInterval');
        $remind_time_afternoon = config('app.sessionStartReminderTimeForAfternoon');
        $remind_time_morning = config('app.sessionStartReminderTimeForMorning');

        $current_time = strtotime(date('Y-m-d H:i:s'));
        $jobs = Job::where('status', '=', 'assigned')->get();
        foreach ($jobs as $job) {

            $time_diff = DateTimeHelper::getTimeDiff(strtotime($job->due), $current_time);
            $due_H = date("H", strtotime($job->due));

            if ($due_H >= 0 && $due_H < 12) {
                $remind_time = $remind_time_morning;
            } else {
                $remind_time = $remind_time_afternoon;
            }
            if ($time_diff > $remind_time - $remind_cron_interval && $time_diff <= $remind_time) {
                $customer = $job->user()->get()->first();
                $translator = Job::getJobsAssignedTranslatorDetail($job);
                $language = TeHelper::fetchLanguageFromJobId($job->from_language_id);

                $this->sendNotificationMailToTranslator($translator, $job);

                $this->sendSessionStartRemindNotification($translator, $job, $language, $job->due, $job->duration);
                $this->sendSessionStartRemindNotification($customer, $job, $language, $job->due, $job->duration);
            }
        }
    }

    public function sendNotificationMailToTranslator($translator, $job)
    {

        $email = $translator->email;
        $name = $translator->name;
        $subject = 'Påminnelse om tolkning, bokning #' . $job->id;
        $data = [
            'user' => $translator,
            'job'  => $job
        ];
        $this->mailer->send($email, $name, $subject, 'emails.remind-translator-about-assigned-job', $data);

    }

    /*
     * this function is for checking the session ending time came, if yes send Push to Translator/Customer to reminde them for finishing session
     */
    public function checkingSessionEndRemind()
    {
        $remind_cron_interval = config('app.sessionReminderInterval');
        $remind_time_interval = config('app.sessionEndReminderTimeInterval');
        $remind_repeat_count = config('app.sessionEndReminderRepeatCount');
        $val_for_mod = intval($remind_time_interval / $remind_cron_interval);
        $remind_end_time = $remind_time_interval * $remind_repeat_count + $remind_cron_interval;

        $this->logger->pushHandler(new StreamHandler(storage_path('logs/cron/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
        $current_time = strtotime(date('Y-m-d H:i:s'));
        $jobs = Job::where('status', '=', 'started')->get();

        $this->logger->addInfo('Checking Session End Remind ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {

            $time_diff = DateTimeHelper::getTimeDiff($current_time, strtotime($job->due)) - $job->duration;

            if ($time_diff >= 0 && $time_diff < $remind_end_time && (intval($time_diff / $remind_cron_interval)) % $val_for_mod === 0) {
                $customer = $job->user()->get()->first();

                $this->logger->addInfo('Checking Session End Remind ', ['customer' => $customer->email, 'time_diff' => $time_diff, 'remind_end_time' => $remind_end_time, 'remind_cron_interval' => $remind_cron_interval, 'val_for_mod' => $val_for_mod]);

                $translator = Job::getJobsAssignedTranslatorDetail($job);
                $this->logger->addInfo('Checking Session End Remind ', ['translator' => $translator->email]);
                if ($job->endedemail == 0) {
                    $email = $customer->email;
                    if (!empty($job->user_email)) {
                        $email = $job->user_email;
                    } else {
                        $email = $customer->email;
                    }
                    $name = $customer->name;
                    $subject = 'Kom ihåg att trycka "Avsluta tolkning" för bokning # ' . $job->id;
                    $data = [
                        'user' => $customer,
                        'job'  => $job
                    ];
                    $this->mailer->send($email, $name, $subject, 'emails.session-not-ended', $data);
                    $data = [
                        'user' => $translator,
                        'job'  => $job
                    ];
                    $this->mailer->send($translator->email, $translator->name, $subject, 'emails.session-not-ended', $data);

                    $job->endedemail = 1;
                    $job->save();
                }
                $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
                $this->sendSessionEndRemindNotification($translator, $job->id);
                $this->sendSessionEndRemindNotification($customer, $job->id);
            }
        }
    }

    /*
     * send session start remind notification
     */
    public function sendSessionStartRemindNotification($user, $job, $language, $due, $duration)
    {

        $this->logger->pushHandler(new StreamHandler(storage_path('logs/cron/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
        $data = array();
        $data['notification_type'] = 'session_start_remind';
        $due_explode = explode(' ', $due);
        if ($job->customer_physical_type == 'yes')
            $msg_text = array(
                "en" => 'Detta är en påminnelse om att du har en ' . $language . 'tolkning (på plats i ' . $job->town . ') kl ' . $due_explode[1] . ' på ' . $due_explode[0] . ' som vara i ' . $duration . ' min. Lycka till och kom ihåg att ge feedback efter utförd tolkning!'
            );
        else
            $msg_text = array(
                "en" => 'Detta är en påminnelse om att du har en ' . $language . 'tolkning (telefon) kl ' . $due_explode[1] . ' på ' . $due_explode[0] . ' som vara i ' . $duration . ' min.Lycka till och kom ihåg att ge feedback efter utförd tolkning!'
            );

        if ($this->bookingRepository->isNeedToSendPush($user->id)) {
            $users_array = array($user);
            $this->bookingRepository->sendPushNotificationToSpecificUsers($users_array, $job->id, $data, $msg_text, $this->bookingRepository->isNeedToDelayPush($user->id));
            $this->logger->addInfo('sendSessionStartRemindNotification ', ['job' => $job->id]);
        }
    }

    /*
     * send session end remind notification
     */
    public function sendSessionEndRemindNotification($user, $job_id)
    {

        $this->logger->pushHandler(new StreamHandler(storage_path('logs/cron/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());
        $data = array();
        $data['notification_type'] = 'session_ended';
        $msg_text = array(
            "en" => 'Kom ihåg att trycka på Avsluta Tolkning-knappen när tolkningen är klar. Tack!'
        );

        if ($this->bookingRepository->isNeedToSendPush($user->id)) {
            $users_array = array($user);
            $this->bookingRepository->sendPushNotificationToSpecificUsers($users_array, $job_id, $data, $msg_text, $this->bookingRepository->isNeedToDelayPush($user->id));
            $this->logger->addInfo('sendSessionEndRemindNotification ', ['job' => $job_id, 'users' => $users_array]);
        }
    }

    public function reminderToAddDuration()
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::where('customer_physical_type', 'yes')
            ->where('status', 'completed')
            ->where('remind_distance', 'no')
            ->whereHas('translatorJobRel', function ($q) use ($currentTime) {
                $q->whereRaw("time_to_sec(timediff( '$currentTime' , completed_at )) / 3600 > 2");
            })
            ->whereDoesntHave('distance')
            ->get();

        $this->logger->addInfo('reminderToAddDuration ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {

            $translator = Job::getJobsAssignedTranslatorDetail($job);
            $subject = 'Vänligen ange din restid för uppdragsnummer # ' . $job->id;

            $data = [
                'user' => $translator,
                'job'  => $job,
                'date' => $job->due->format('d.m.Y'),
                'time' => $job->due->format('H:i')
            ];
            $this->mailer->send($translator->email, $translator->name, $subject, 'emails.remind-add-distance-translator', $data);

            $job->remind_distance = 'yes';
            $job->save();
            $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
        }

    }

    public function sendPushToTranslators($time_start, $time_end, $time_for_push)
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::query();
        $jobs->where('immediate', 'no')
            ->where('status', 'pending')
            ->where('sended_push_again', '0')
            ->whereRaw("time_to_sec(timediff(  due , created_at )) / 3600 >= $time_start");
        if ($time_end != '0') {
            $jobs->whereRaw("time_to_sec(timediff(  due , created_at )) / 3600 <= $time_end");
            $jobs->whereRaw("time_to_sec(timediff(  '$currentTime' , created_at )) / 3600 >= $time_for_push");
        } else {
            $jobs->whereRaw("time_to_sec(timediff(  due, '$currentTime' )) / 3600 <= $time_for_push");
        }
        $jobs = $jobs->get();

        $this->logger->addInfo('Send push notification after ' . $time_for_push . ' hour for ' . $time_start . '-' . $time_end . ' ', ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {

            $job->sended_push_again = 1;
            $job->save();

            $job_data = $this->bookingRepository->jobToData($job);

            $this->bookingRepository->sendNotificationTranslator($job, $job_data, '*');
            $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
        }
    }

    public function checkExpiringBookings($time_start, $time_end, $time_for_push, $email_template = 'job-not-accepted')
    {
        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
        $jobs = Job::query();
        $jobs->where('status', 'pending')
            ->where('immediate', 'no')
            ->where('emailsent', '0')
            ->whereRaw("time_to_sec(timediff(  due , created_at )) / 3600 > $time_start");
        if ($time_end != '0') {
            $jobs->whereRaw("time_to_sec(timediff(  due , created_at )) / 3600 < $time_end");
            $jobs->where(function ($q) use ($currentTime, $time_for_push) {
                $q->whereRaw("time_to_sec(timediff(  '$currentTime' , created_at )) / 3600 >= $time_for_push")
                    ->orWhere('due', '<', $currentTime);
            });
        } else {
            $jobs->whereRaw("time_to_sec(timediff(  due, '$currentTime' )) / 3600 <= $time_for_push");
        }
        $jobs = $jobs->get();

        $this->logger->addInfo("Booking after $time_start hours and before $time_end jobs ", ['jobs' => collect($jobs)->pluck('id')->all()]);
        foreach ($jobs as $job) {
            $user = $job->user()->get()->first();
            if (!empty($job->user_email)) {
                $email = $job->user_email;
            } else {
                $email = $user->email;
            }
            $name = $user->name;
            $subject = 'Beklagar att vi inte kunde lösa ert tolkbehov #' . $job->id;
            $data = [
                'user' => $user,
                'job'  => $job
            ];
            $this->mailer->send($email, $name, $subject, 'emails.' . $email_template, $data);

            $job->status = 'timedout';
            $job->expired_at = date('Y-m-d H:i:s');
            $job->emailsent = 1;
            $job->save();
            $this->bookingRepository->sendExpiredNotification($job, $user);        // send Expired Push Notification to user
            $this->logger->addInfo(' job  ' . $job->id, ['job' => $job->toArray()]);
        }
    }

}