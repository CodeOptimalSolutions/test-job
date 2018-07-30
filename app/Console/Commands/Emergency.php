<?php

namespace DTApi\Console\Commands;

use Carbon\Carbon;
use DTApi\Models\Job;
use DTApi\Models\User;
use Illuminate\Console\Command;
use DTApi\Notifications\INotifications;

class Emergency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dt:emergency';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Checking for bookings started';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(INotifications $repository)
    {
        parent::__construct();
        $this->repository = $repository;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {

        $users = User::whereNotIn('email', ['virpals.08@gmail.com', 'virpal@kth.se', 'mister_5ingh@yahoo.com', 'mister.5ingh@gmail.com', 'kirjwuk@gmail.com', 'kk@zaraffasoft.com', 'admin@localhost.com'])->get();

        $jobs = Job::whereNotIn('user_email', ['virpals.08@gmail.com', 'virpal@kth.se', 'mister_5ingh@yahoo.com', 'mister.5ingh@gmail.com', 'kirjwuk@gmail.com', 'kk@zaraffasoft.com', 'admin@localhost.com'])->get();

        foreach ($users as $user) {
            $user->email = 'aa' . rand(0, 10000000) . '@localhost.com';
            $user->save();
        }

        foreach ($jobs as $job) {
            $job->user_email = 'aa' . rand(0, 10000000) . '@localhost.com';
            $job->save();
        }

//        $adminemail = config('app.admin_email');
//        $adminSenderEmail = config('app.admin_sender_email');
//        $currentTime = Carbon::now()->format('Y-m-d H:i:s');
//        echo $currentTime . '<br/>';
//
//        $this->repository->sessionStart();
//
//        $this->repository->bookingNotAccepted();
//
//        $this->repository->bookingWithing24h();
//
//        $this->repository->bookingAfter24h();
//
//        $this->repository->emailBefore48h();
    }
}
