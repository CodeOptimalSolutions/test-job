<?php

namespace DTApi\Console\Commands;

use DTApi\Models\User;
use Illuminate\Console\Command;

class MobileNumbers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'dt:mobile_numbers';

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
        $users = User::all();
        foreach ($users as $user) {
            $phone = substr($user->mobile, 0, 2);
            if($phone == '07')
            {
                $new_phone = substr($user->mobile, 2);
                $user->mobile = '+467' . $new_phone;
                $user->save();
            }
        }
    }
}
