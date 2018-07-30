<?php

Route::post('authorize', 'Auth\AuthController@authorizeApp')->middleware('web');

Route::any('/', function () {

//            $payload = (array) \Firebase\JWT\JWT::decode('eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJEVEFwaSIsInN1YiI6IjExMTIyMjMzMyIsImlhdCI6MTQ2MTU3NDA3MCwiZXhwIjoxNDY0MTY2MDcwfQ.emH3wzXUyflrH4K4E3Bid_wXF93THE8t7-4IipR60TM', env('API_SECRET'), ['HS256']);
//dd($payload);
//                $validator = \Illuminate\Support\Facades\Validator::make($payload, [
//                    'iss' => 'required|in:DTApi',
//                    'sub' => 'required',
//                ]);
//
//        dd($validator->passes());

    return 'Hej!<br>
Vi genomgår just nu systemuppdatering.
<br><br>
Du kan ringa oss på 076-273 16 28 eller maila oss på info (at) DigitalTolk.se om du har frågor om din bokning eller vill boka tolk.
<br><br>
Vi förväntar oss att alla system är igång inom 24 timmar.
<br><br>
Tack för er förståelse kring detta.
<br><br>
Vänliga hälsningar
DigitalTolk';
})->middleware('web'); //test route

//Route::group(['prefix' => 'api/v1', 'middleware' => 'auth.api.app'], function () {
Route::group(['prefix' => 'api/v1'], function () {

    Route::post('auth/app', 'Auth\AuthController@authenticateApp');
    Route::post('sms', 'SMSController@receive');

    Route::get('/', 'HomeController@index');

    Route::group(['middleware' => 'auth.api.app'],
        function () {

            Route::post('auth/send-reset-link-email', 'Auth\PasswordController@sendResetLinkEmail');
            Route::post('auth/send-reset-password', 'Auth\PasswordController@reset');
            Route::post('sessions', 'Auth\AuthController@sessionMigration');
            Route::get('langs', 'DataController@getLangs');
            Route::get('towns', 'DataController@getTowns');
            Route::get('types', 'DataController@getTypes');
            Route::get('translator-levels', 'DataController@getLevels');
            Route::get('translators', 'UserController@getTranslators');
            Route::get('application-data', 'HomeController@appData');

            Route::post('too-many-login', 'Auth\AuthController@tooManyLogin');
            Route::post('auth/user', 'Auth\AuthController@authenticateUser');
        });

    Route::group(['as' => 'users', 'middleware' => 'auth.api.user'],
        function () {

            Route::group(['middleware' => 'role:superadmin'],
                function () {
                    Route::resource('admin-users', 'AdminUsersController');
                    Route::post('login-as-user', 'Auth\AuthController@loginAsUser');
                    Route::post('export', 'ExportController@generateList');
                    Route::resource('statistics', 'StatisticsController');
                        Route::resource('salary', 'SalaryController');
                        Route::resource('customer-salary', 'CustomerSalaryController');
                        Route::resource('invoice', 'InvoiceController');
                        Route::get('export-file/{filename}', 'ExportController@exportFile');
                    Route::resource('companies', 'CompanyController');
                    Route::resource('departments', 'DepartmentController');
                });

            Route::resource('settings', 'SettingController');
            Route::resource('holidays', 'HolidaysController');
            Route::resource('types', 'TypesController');
            Route::get('admin/logs/{date}', 'LogsController@showLog');
            Route::get('admin/logs', 'LogsController@adminLogs');
            Route::get('admin/alerts/{count?}', 'LogsController@alerts');
            Route::get('admin/failed-logins/{count?}', 'LogsController@userLoginFailed');
            Route::get('admin/booking-expire/{count?}', 'LogsController@bookingExpireNoAccepted');
            Route::get('admin/no-salary', 'LogsController@noSalaries');
            Route::get('admin/ignore-{type}/{id}', 'LogsController@ignoreAlert');
            Route::get('admin/ignore-expiring/{id}', 'LogsController@ignoreExpiring');
            Route::get('admin/ignore-expired/{id}', 'LogsController@ignoreExpired');
            Route::get('admin/ignore-failed-login/{id}', 'LogsController@ignoreThrottle');
            Route::get('admin/ignore-feedback/{id}', 'LogsController@ignoreFeedback');
            
            Route::resource('jobs', 'BookingController');
            Route::resource('users', 'UserController');
            Route::resource('distance', 'DistanceController');
            Route::resource('feedback', 'FeedBackController');
            Route::resource('pages', 'PagesController');

            Route::get('historic', 'BookingController@getHistory');
            Route::get('login-logs', 'LogsController@index');
            Route::get('potential-jobs', 'BookingController@getPotentialJobs');
            Route::get('potential-translators/{id}', 'UserController@getPotentialTranslators');
                

            Route::get('export', 'ExportController@generateList');
            Route::get('export-invoices', 'ExportController@generateInvoiceList');
            Route::get('export/{id}', 'ExportController@getList');

            Route::put('export/{id}', 'ExportController@updateList');
            Route::delete('export/{id}', 'ExportController@deleteList');

            Route::post('export', 'ExportController@saveList');
            Route::post('login-as-user', 'Auth\AuthController@loginAsUser');

            Route::post('distanceFeed', 'BookingController@distanceFeed');
            Route::post('reopen', 'BookingController@reopen');
            Route::post('immediate-email', 'BookingController@immediateJobEmail');
            Route::post('accept-job', 'BookingController@acceptJob');
            Route::post('accept-job-id', 'BookingController@acceptJobWithId');
            Route::post('cancel-job', 'BookingController@cancelJob');
            Route::post('end-job', 'BookingController@endJob');
            Route::post('customer-not-call', 'BookingController@customerNotCall');
            Route::post('resend-notifications', 'BookingController@resendNotifications');
            Route::post('resend-sms-notifications', 'BookingController@resendSMSNotifications');

        });

});