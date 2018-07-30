<?php

use DTApi\Models\Role;
use DTApi\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Database\Eloquent\Model;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        \DTApi\Models\Application::create([
           'name' => 'DTWeb',
           'key' => '111222333',
           'secret' => 'aaabbbccc'
        ]);

        $admin_main = \DTApi\Models\User::create([
            'name' => 'Admin',
            'email' => 'kirjwuk@gmail.com',
            'password' => bcrypt('adminadmin'),
        ]);

        $customer_role = Role::create([
            'name' => 'Customer',
            'slug' => 'customer',
            'description' => '', // optional
            'parent_id' => NULL, // optional, set to NULL by default
        ]);

        $translator_role = Role::create([
            'name' => 'Translator',
            'slug' => 'translator',
            'description' => '', // optional
            'parent_id' => NULL, // optional, set to NULL by default
        ]);


        User::insert([
            'name' => 'admin',
            'email' => 'admin@localhost',
            'password' => bcrypt('password'),
        ]);

        $userMeta = new \DTApi\Models\UserMeta();
        $userMeta->user_id = 1;
        $userMeta->gender = 'male';
        $userMeta->save();

        /*customers*/
        $customer_ngo = User::create([
            'name' => 'Customer (NGO)',
            'email' => 'customer_ngo@localhost',
            'password' => bcrypt('customer_ngo'),
            'user_type'=>$customer_role->id
        ]);

        $userMeta = new \DTApi\Models\UserMeta();
        $userMeta->user_id = $customer_ngo->id;
        $userMeta->consumer_type = 'ngo';
        $userMeta->save();

        $customer_paid = User::create([
            'name' => 'Customer (Paid)',
            'email' => 'customer_paid@localhost',
            'password' => bcrypt('customer_paid'),
            'user_type'=>$customer_role->id
        ]);

        $userMeta = new \DTApi\Models\UserMeta();
        $userMeta->user_id = $customer_paid->id;
        $userMeta->consumer_type = 'paid';
        $userMeta->save();

        $translator_vol = User::create([
            'name' => 'Translator (volunteer)',
            'email' => 'translator_vol@localhost',
            'password' => bcrypt('translator_vol'),
            'user_type'=>$translator_role->id
        ]);

        $userMeta = new \DTApi\Models\UserMeta();
        $userMeta->user_id = $translator_vol->id;
        $userMeta->translator_type = 'volunteer';
        $userMeta->gender = 'female';
        $userMeta->translator_level = 'Certified';
        $userMeta->save();

        //user languages
        $userLang = new \DTApi\Models\UserLanguages();
        $userLang->user_id = $translator_vol->id;
        $userLang->lang_id = '1';
        $userLang->type = 'written';
        $userLang->save();

        $userLang = new \DTApi\Models\UserLanguages();
        $userLang->user_id = $translator_vol->id;
        $userLang->lang_id = '2';
        $userLang->type = 'written';
        $userLang->save();

        $userLang = new \DTApi\Models\UserLanguages();
        $userLang->user_id = $translator_vol->id;
        $userLang->lang_id = '3';
        $userLang->type = 'written';
        $userLang->save();

        $userLang = new \DTApi\Models\UserLanguages();
        $userLang->user_id = $translator_vol->id;
        $userLang->lang_id = '72';
        $userLang->type = 'written';
        $userLang->save();

        $translator_pro = User::create([
            'name' => 'Translator (Professional)',
            'email' => 'translator_pro@localhost',
            'password' => bcrypt('translator_pro'),
            'user_type'=>$translator_role->id
        ]);

        $userMeta = new \DTApi\Models\UserMeta();
        $userMeta->user_id = $translator_pro->id;
        $userMeta->translator_type = 'professional';
        $userMeta->gender = 'male';
        $userMeta->translator_level = 'Certified';
        $userMeta->save();

        //user languages
        $userLang = new \DTApi\Models\UserLanguages();
        $userLang->user_id = $translator_pro->id;
        $userLang->lang_id = '1';
        $userLang->type = 'written';
        $userLang->save();

        $userLang = new \DTApi\Models\UserLanguages();
        $userLang->user_id = $translator_pro->id;
        $userLang->lang_id = '72';
        $userLang->type = 'written';
        $userLang->save();

        $superadmin_role = Role::create([
            'name' => 'Super Admin',
            'slug' => 'superadmin',
            'description' => '', // optional
            'parent_id' => NULL, // optional, set to NULL by default
        ]);

        $superadmin = User::create([
            'name' => 'Superadmin',
            'email' => 'superadmin@localhost',
            'password' => bcrypt('superadmin'),
            'user_type'=>$superadmin_role->id
        ]);

        $userMeta = new \DTApi\Models\UserMeta();
        $userMeta->user_id = $superadmin->id;
        $userMeta->gender = 'male';
        $userMeta->save();

        \DTApi\Models\RoleUser::create([
            'user_id' =>  $customer_ngo->id,
            'role_id' =>  $customer_role->id,
            'granted' =>  1,
        ]);
        \DTApi\Models\RoleUser::create([
            'user_id' =>  $customer_paid->id,
            'role_id' =>  $customer_role->id,
            'granted' =>  1,
        ]);
        \DTApi\Models\RoleUser::create([
            'user_id' =>  $translator_vol->id,
            'role_id' =>  $translator_role->id,
            'granted' =>  1,
        ]);
        \DTApi\Models\RoleUser::create([
            'user_id' =>  $translator_pro->id,
            'role_id' =>  $translator_role->id,
            'granted' =>  1,
        ]);
        \DTApi\Models\RoleUser::create([
            'user_id' =>  $superadmin->id,
            'role_id' =>  $superadmin_role->id,
            'granted' =>  1,
        ]);
        \DTApi\Models\RoleUser::create([
            'user_id' =>  $admin_main->id,
            'role_id' =>  $superadmin_role->id,
            'granted' =>  1,
        ]);

    }
}