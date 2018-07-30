<?php

namespace DTApi\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use DTApi\Notifications\INotifications;

/**
 * Class CronJobs
 * @package App\Console\Commands
 */
class CronJobs extends Command
{

    /**
     * @var NotificationsRepository
     */
    protected $repository;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'digitaltolk:cron';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Cron jobs for Digital Tolk';


    /**
     * CronJobs constructor.
     * @param INotifications $notificationsRepository
     */
    public function __construct(INotifications $notificationsRepository)
    {
        parent::__construct();
        $this->repository = $notificationsRepository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        if(!Redis::exists('cron_server'))
            Redis::set('cron_server', 'master');
        if(Redis::get('cron_server') != env('CRON_SERVER'))
            die;
die;
        Log::info('cron server ', ['server' => Redis::get('cron_server')]);

        $adminemail = config('app.admin_email');
        $adminSenderEmail = config('app.admin_sender_email');

        $currentTime = Carbon::now()->format('Y-m-d H:i:s');

        $this->repository->sessionStart();

        $this->repository->bookingNotAccepted();

        $this->repository->checkExpiringBookings(0, 24, 1.5);

        $this->repository->bookingNotAccepted45m();

        $this->repository->checkExpiringBookings(24, 72, 16, 'job-not-acceptednew');

        $this->repository->bookingNotAcceptedAfter6h();
        
        $this->repository->endSessionAfter8h();

        $this->repository->emailAfter16h();

        $this->repository->checkExpiringBookings(72, 0, 48);

        $this->repository->sendPushToTranslators(0, 24, 1);

        $this->repository->sendPushToTranslators(24, 48, 10);

        $this->repository->sendPushToTranslators(48, 72, 12);

        $this->repository->sendPushToTranslators(73, 96, 20);

        $this->repository->sendPushToTranslators(96, 0, 84);

        $this->repository->checkingSessionStartRemindTime();
        
        $this->repository->checkingSessionEndRemind();

        $this->repository->reminderToAddDuration();
    }

}
