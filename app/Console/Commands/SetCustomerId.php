<?php

namespace DTApi\Console\Commands;

use DTApi\Models\User;
use Illuminate\Console\Command;

class SetCustomerId extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dt:set_c_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::with('userMeta')->where('user_type', env('CUSTOMER_ROLE_ID'))->get();

        $customer_id = 4001;
        foreach ($users as $user) {
            if ($user->userMeta->consumer_type == 'paid')
                $user->userMeta->customer_id = $customer_id;
            else
                $user->userMeta->customer_id = '';
            $user->userMeta->save();
            if ($user->userMeta->consumer_type == 'paid')
                $customer_id++;
        }
    }
}
