<?php

namespace DTApi\Repository;

use Carbon\Carbon;
use DTApi\Models\TranslatorLevel;
use Monolog\Logger;
use DTApi\Models\Job;
use DTApi\Models\Export;
use DTApi\Models\Settings;
use DTApi\Models\ExportList;
use Illuminate\Http\Request;
use DTApi\Helpers\DateTimeHelper;
use DTApi\Exports\ExportInterface;
use Monolog\Handler\StreamHandler;
use Illuminate\Support\Facades\Log;
use Monolog\Handler\FirePHPHandler;

class ExportRepository extends BaseRepository
{
    protected $model;
    protected $exportInterface;

    public function __construct(Export $model, ExportInterface $exportInterface)
    {
        parent::__construct($model);
        $this->logger = new Logger('export_logger');

        $this->logger->pushHandler(new StreamHandler(storage_path('logs/export/laravel-' . date('Y-m-d') . '.log'), Logger::DEBUG));
        $this->logger->pushHandler(new FirePHPHandler());

        $this->exportInterface = $exportInterface;
    }

    public function prepareList($jobs)
    {
        $result_list = [];

        foreach ($jobs as $job) {
            if (env('APP_ENV') == 'local') $langs = ['Dari'];
            else $langs = ['Punjabi', 'Ukrainska'];
            if (!in_array($job->language->language, $langs)) {
                $translator = Job::getJobsAssignedTranslatorDetail($job);

                $session_time = $job->session_time;

                if (!is_null($job->user->invoices)) {
                    $customer = $job->user;
                    $invoices = $job->user->invoices;
                } elseif (isset($job->user->department) && !is_null($job->user->department->invoices)) {
                    $customer = $job->user->department;
                    $invoices = $job->user->department->invoices;
                } elseif (!is_null($job->user->company->invoices)) {
                    $customer = $job->user->company;
                    $invoices = $job->user->company->invoices;
                }

                if ($session_time != '') {
                    $time = explode(':', $job->session_time);
                    $session_time = $time[0] + $time[1] / 60;
                }

                $rounded_session_length = $this->calculateSessionTime($session_time, $job->duration, $job->customer_phone_type, $job->customer_physical_type, isset($job->distance) && !is_null($job->distance) ? $job->distance->distance : '', isset($job->distance) && !is_null($job->distance) ? $job->distance->time : '', $invoices->businessRules);

                if ($job->status == 'withdrawafter24' || $job->status == 'not_carried_out_customer')
                    $rounded_session_length = $job->duration / 60;

                $inconvenience_hours = DateTimeHelper::calculateInconvenienceHours($job->due, $rounded_session_length, $customer->inconvenienceSettings, $customer->holidays);
                $inconvenience_hours_type = $inconvenience_hours['type'];
                $inconvenience_hours_duration = round($inconvenience_hours['duration'] / 60, 2);

                $session_time_min = 0;
                $session_time_after = 0;
                if (isset($translator)) {
                    if ($translator->userMeta->translator_level == 'Layman' || $translator->userMeta->translator_level == 'Read Translation courses')
                        $translator_level = 'layman';
                    elseif ($translator->userMeta->translator_level == 'Certified')
                        $translator_level = 'certified';
                    else
                        $translator_level = 'specialised';

                    if ($job->physic == 'yes')
                        $booking_type = 'physical';
                    else
                        $booking_type = 'phone';

//                    $invoices = $job->user->invoices;
                    $physical_level = 'physical_' . $translator_level;
                    $phone_level = 'phone_' . $translator_level;
                    $travel_level = 'travel_time_' . $translator_level;
                    $inconvenient_level = 'inconvenient_' . $translator_level;

                    $translator_level_id = TranslatorLevel::where('code', $translator_level)->first()->id;

                    $business_rules = $invoices->businessRules()->first();

                    $session_time_min = $business_rules[$booking_type . '_min'];
                    $session_time_after = $session_time - $business_rules[$booking_type . '_min'];
                    $session_time_after_rounded = $rounded_session_length - $business_rules[$booking_type . '_min'];

                    if (isset($invoices->km_price)) {
                        $invoicing_info = ($job->customer_physical_type == 'yes') ? $invoices->$physical_level . '<br>' . $invoices->$travel_level . '<br>' . $invoices->$inconvenient_level . '<br>' . $invoices->km_price : $invoices->$phone_level . '<br>' . $invoices->$inconvenient_level;
                    } else {
                        $invoicing_info = 'N/A';
                    }
                    $salaries = $translator->salaries;
                    $salary = collect($salaries)->where('customer_id', $job->user->id)->first();
                    if (isset($salary)) {
                        $salaries_info = ($job->customer_physical_type == 'yes') ? $salary->physical_session . '<br>' . $salary->travel_time . '<br>' . $salary->additional . '<br>' . $salary->travel_km : $salary->phone_session . '<br>' . $salary->additional;
                    } else {
                        $default_salaries = Settings::where('group', 'salaries')->get();
                        $default_salaries = collect($default_salaries)->pluck('value', 'code')->all();
                        $salaries_info = ($job->customer_physical_type == 'yes') ? $default_salaries[$physical_level] . '<br>' . $default_salaries[$travel_level] . '<br>' . $default_salaries[$inconvenient_level] . '<br>' . $default_salaries['km_price'] : $default_salaries[$phone_level] . '<br>' . $default_salaries[$inconvenient_level];
                    }
                } else {
                    $invoicing_info = 'N/A';
                    $salaries_info = 'N/A';
                }

                $result_list[$job->id]['booking_id'] = $job->id;
                $result_list[$job->id]['c_name'] = $job->user->name;
                $result_list[$job->id]['c_id'] = $job->user->id;
                $result_list[$job->id]['c_ref'] = $job->reference;
                $result_list[$job->id]['cost_place'] = $job->user->department_id;
                $result_list[$job->id]['language'] = $job->language->language;
                $result_list[$job->id]['status'] = $job->status;
                $result_list[$job->id]['phone'] = $job->customer_phone_type;
                $result_list[$job->id]['physic'] = $job->customer_physical_type;
                $result_list[$job->id]['due'] = $job->due->format('Y-m-d H:i:s');
                $result_list[$job->id]['duration'] = $job->duration;
                $result_list[$job->id]['session_length'] = $job->session_time;
                $result_list[$job->id]['rounded_session_length'] = $rounded_session_length;
                $result_list[$job->id]['minimum_time'] = $session_time_min;
                $result_list[$job->id]['after_minimum_time'] = $session_time_after . ' (' . $session_time_after_rounded . ')';
                $result_list[$job->id]['due_within24'] = $this->checkDueWithin24($job->due, $job->b_created_at);
                $result_list[$job->id]['by_admin'] = $job->by_admin;
                $result_list[$job->id]['online'] = $job->by_admin == 'no' ? 'yes' : 'no';
                $result_list[$job->id]['fee'] = $job->user->userMeta->fee;
                $result_list[$job->id]['t_name'] = isset($translator) ? $translator->name : 'N/A';
                $result_list[$job->id]['t_id'] = isset($translator) ? $translator->id : 'N/A';
                $result_list[$job->id]['t_dob'] = isset($translator) ? $translator->dob_or_orgid : 'N/A';
                $result_list[$job->id]['compensation'] = $job->status == 'compensation' ? 'yes' : 'no';
                $result_list[$job->id]['withdrawn_late'] = $job->status == 'withdrawafter24' ? 'yes' : 'no';
                $result_list[$job->id]['c_not_call'] = $job->status == 'not_carried_out_customer' ? 'yes' : 'no';
                $result_list[$job->id]['invoicing_info'] = $invoicing_info;
                $result_list[$job->id]['salaries_info'] = $salaries_info;
                $result_list[$job->id]['travel_time'] = ($job->customer_physical_type == 'yes' && isset($job->distance->time)) ? $job->distance->time : 'N/A';
                $result_list[$job->id]['travel_km'] = ($job->customer_physical_type == 'yes' && isset($job->distance->distance)) ? $job->distance->distance : 'N/A';
                $result_list[$job->id]['inconvenience_time'] = isset($inconvenience_hours) ? $inconvenience_hours_duration : 'N/A';
                $result_list[$job->id]['inconvenience_type'] = isset($inconvenience_hours) ? $inconvenience_hours_type : 'N/A';
                $result_list[$job->id]['customer_id'] = $job->user->company->id;
                $result_list[$job->id]['created_date'] = $job->b_created_at->format('Y-m-d H:i:s');
            }
        }

        $result_list = collect($result_list)->sortBy('due')->all();

        return $result_list;

    }

    /**
     * Method to prepare invoices list based on filtered bookings, business rules to round session time, and invoices settings for customers. If user has invoices settings then use his rules, if no then check department and use its rules, if no then use company invoicing rules.
     * @param $jobs
     * @return mixed
     */
    public function prepareInvoicesList($bookings)
    {
        $result_list = [];
        foreach ($bookings as $booking) {
            $translator = Job::getJobsAssignedTranslatorDetail($booking);

            if (!is_null($booking->user->invoices)) {
                $customer = $booking->user;
                $invoices = $booking->user->invoices;
            } elseif (isset($job->user->department) && !is_null($booking->user->department->invoices)) {
                $customer = $booking->user->department;
                $invoices = $booking->user->department->invoices;
            } elseif (!is_null($booking->user->company->invoices)) {
                $customer = $booking->user->company;
                $invoices = $booking->user->company->invoices;
            }

            $session_time = $booking->session_time;
            if ($session_time != '') {
                $time = explode(':', $booking->session_time);
                $session_time = $time[0] + $time[1] / 60;
            }

            $rounded_session_length = $this->calculateSessionTime($session_time, $booking->duration, $booking->customer_phone_type, $booking->customer_physical_type, isset($booking->distance) && !is_null($booking->distance) ? $booking->distance->distance : '', isset($booking->distance) && !is_null($booking->distance) ? $booking->distance->time : '', $invoices->businessRules);

            if ($booking->status == 'withdrawafter24' || $booking->status == 'not_carried_out_customer')
                $rounded_session_length = $booking->duration / 60;

            $inconvenience_hours = DateTimeHelper::calculateInconvenienceHours($booking->due, $rounded_session_length, $customer->inconvenienceSettings, $customer->holidays);
            $inconvenience_hours_type = $inconvenience_hours['type'];
            $inconvenience_hours_duration = round($inconvenience_hours['duration'] / 60, 2);

            if (isset($translator)) {
                if ($translator->userMeta->translator_level == 'Layman' || $translator->userMeta->translator_level == 'Read Translation courses')
                    $translator_level = 'layman';
                elseif ($translator->userMeta->translator_level == 'Certified')
                    $translator_level = 'certified';
                else
                    $translator_level = 'specialised';

                $physical_level = 'physical_' . $translator_level;
                $phone_level = 'phone_' . $translator_level;
                $travel_level = 'travel_time_' . $translator_level;
                $inconvenient_level = 'inconvenient_' . $translator_level;
                if (isset($invoices->km_price)) {
                    $invoicing_info = ($booking->customer_physical_type == 'yes') ? $invoices->$physical_level . '<br>' . $invoices->$travel_level . '<br>' . $invoices->$inconvenient_level . '<br>' . $invoices->km_price : $invoices->$phone_level . '<br>' . $invoices->$inconvenient_level;
                } else {
                    $invoicing_info = 'N/A';
                }
                $salaries = $translator->salaries;
                $salary = collect($salaries)->where('customer_id', $booking->user->id)->first();
                if (isset($salary)) {
                    $salaries_info = ($booking->customer_physical_type == 'yes') ? $salary->physical_session . '<br>' . $salary->travel_time . '<br>' . $salary->additional . '<br>' . $salary->travel_km : $salary->phone_session . '<br>' . $salary->additional;
                } else {
                    $default_salaries = Settings::where('group', 'salaries')->get();
                    $default_salaries = collect($default_salaries)->pluck('value', 'code')->all();
                    $salaries_info = ($booking->customer_physical_type == 'yes') ? $default_salaries[$physical_level] . '<br>' . $default_salaries[$travel_level] . '<br>' . $default_salaries[$inconvenient_level] . '<br>' . $default_salaries['km_price'] : $default_salaries[$phone_level] . '<br>' . $default_salaries[$inconvenient_level];
                }
            } else {
                $invoicing_info = 'N/A';
                $salaries_info = 'N/A';
            }

            $result_list[$booking->id]['booking_id'] = $booking->id;
            $result_list[$booking->id]['c_name'] = $booking->user->name;
            $result_list[$booking->id]['c_id'] = $booking->user->id;
            $result_list[$booking->id]['c_ref'] = $booking->reference;
            $result_list[$booking->id]['customer_id'] = $booking->user->company_id;
            $result_list[$booking->id]['cost_place'] = $booking->user->department_id;
            $result_list[$booking->id]['language'] = $booking->language->language;
            $result_list[$booking->id]['status'] = $booking->status;
            $result_list[$booking->id]['phone'] = $booking->customer_phone_type;
            $result_list[$booking->id]['physic'] = $booking->customer_physical_type;
            $result_list[$booking->id]['due'] = $booking->due->format('Y-m-d H:i:s');
            $result_list[$booking->id]['session_length'] = $booking->session_time;
            $result_list[$booking->id]['rounded_session_length'] = $rounded_session_length;
            $result_list[$booking->id]['due_within24'] = $this->checkDueWithin24($booking->due, $booking->b_created_at);
            $result_list[$booking->id]['by_admin'] = $booking->by_admin;
            $result_list[$booking->id]['online'] = $booking->by_admin == 'no' ? 'yes' : 'no';
            $result_list[$booking->id]['fee'] = $booking->user->userMeta->fee;
            $result_list[$booking->id]['t_name'] = isset($translator) ? $translator->name : 'N/A';
            $result_list[$booking->id]['t_id'] = isset($translator) ? $translator->id : 'N/A';
            $result_list[$booking->id]['t_dob'] = isset($translator) ? $translator->dob_or_orgid : 'N/A';
            $result_list[$booking->id]['compensation'] = $booking->status == 'compensation' ? 'yes' : 'no';
            $result_list[$booking->id]['withdrawn_late'] = $booking->status == 'withdrawafter24' ? 'yes' : 'no';
            $result_list[$booking->id]['c_not_call'] = $booking->status == 'not_carried_out_customer' ? 'yes' : 'no';
            $result_list[$booking->id]['invoicing_info'] = $invoicing_info;
            $result_list[$booking->id]['salaries_info'] = $salaries_info;
            $result_list[$booking->id]['travel_time'] = ($booking->customer_physical_type == 'yes' && isset($booking->distance->time)) ? $this->calculateTravelTime($booking->distance->time, $booking->user->userMeta->time_to_pay) : 'N/A';
            $result_list[$booking->id]['travel_km'] = ($booking->customer_physical_type == 'yes' && $booking->user->userMeta->charge_km == 'yes' && isset($booking->distance->distance)) ? ($booking->user->userMeta->maximum_km != '' && $booking->user->userMeta->maximum_km <= $booking->distance->distance) ? $booking->user->userMeta->maximum_km : $booking->distance->distance : 'N/A';
            $result_list[$booking->id]['inconvenience_time'] = $booking->user->userMeta->charge_ob == 'yes' ? isset($inconvenience_hours) ? $inconvenience_hours_duration : 'N/A' : 'N/A';
            $result_list[$booking->id]['inconvenience_type'] = $booking->user->userMeta->charge_ob == 'yes' ? isset($inconvenience_hours) ? $inconvenience_hours_type : 'N/A' : 'N/A';
            $result_list[$booking->id]['created_date'] = $booking->b_created_at->format('Y-m-d H:i:s');
        }

        $result_list = collect($result_list)->sortBy('due')->all();

        return $result_list;
    }

    /**
     * Method to calculate session duration according to business logic (round duration).
     *
     * @param $session_time
     * @param $duration
     * @param $phone_type
     * @param $physical_type
     * @param $travel_duration
     * @param $travel_time
     * @return float|int
     */
    private function calculateSessionTime($session_time, $duration, $phone_type, $physical_type, $travel_duration, $travel_time, $businessRules = null)
    {
        if ($session_time == '') return $session_time;

        if (is_null($businessRules)) {
            $physical_min_time = 1;
            $physical_after_time = 1;
            $phone_min_time = 0.5;
            $phone_after_time = 0.5;
        } else {
            $physical_min_time = $businessRules->physical_min;
            $physical_after_time = $businessRules->physical_after;
            $phone_min_time = $businessRules->phone_min;
            $phone_after_time = $businessRules->phone_after;
        }

        $hour = 60;
        $half_hour = 0.5;

        if ($phone_type == 'yes' && $phone_type == 'yes' && ($travel_duration != '' || $travel_time != '')) {
            $type = 'physical';
        } elseif ($physical_type == 'yes') {
            $type = 'physical';
        } else {
            $type = 'phone';
        }

        if ($type == 'physical') {
            $session_time = $session_time - $physical_min_time;
            $cc = $session_time / $physical_after_time;

            if ($session_time + $physical_min_time <= $physical_min_time)
                return $physical_min_time;
            if ($duration / $hour > $session_time + $physical_min_time) {
                return $duration / $hour;
            }
            if ($session_time < 0.08)
                return $physical_min_time;
            else if ($cc < 1)
                return $physical_min_time + $physical_after_time;
            else if (0.08 > $session_time - floor($cc) * $physical_after_time)
                return $physical_min_time + floor($cc) * $physical_after_time;
            else
                return $physical_min_time + ceil($cc) * $physical_after_time;
        } else {

            $session_time = $session_time - $phone_min_time;
            $cc = $session_time / $phone_after_time;

            if ($session_time + $phone_min_time <= $phone_min_time)
                return $phone_min_time;
            if ($duration / $hour > $session_time + $phone_min_time)
                return $duration / $hour;
            if ($session_time < 0.08)
                return $phone_min_time;
            else if ($cc < 1)
                return $phone_min_time + $phone_after_time;
            else if (0.08 > $session_time - floor($cc) * $phone_after_time)
                return $phone_min_time + floor($cc) * $phone_after_time;
            else
                return $phone_min_time + ceil($cc) * $phone_after_time;
        }

    }

    public function calculateTravelTime($travel_time, $customer_travel_time)
    {
        $travel_time = $travel_time / 60;

        if ($travel_time >= $customer_travel_time)
            return $customer_travel_time;
        else
            return number_format($travel_time, 2, ',', '');
    }

    private function checkDueWithin24($due, $created_at)
    {
        $due = Carbon::createFromFormat('Y-m-d H:i:s', $due);
        $created_at = Carbon::createFromFormat('Y-m-d H:i:s', $created_at);
        if ($due->diffInHours($created_at) < 24)
            return 'yes';

        return 'no';
    }

    public function getList($id)
    {

    }

    public function saveList($list_data, $jobs)
    {
        $list = ExportList::create($list_data);
        foreach ($jobs as $job) {
            $job['export_list_id'] = $list->id;
            $job['booking_id'] = $job['booking_id'];
            $export_list = self::create($job);
        }

        return $list;
    }

    public function updateList($id, $list_data, $jobs)
    {
        $list = ExportList::where('id', $id)->update(['name' => $list_data['name'], 'comment' => $list_data['comment'], 'payment_date' => $list_data['payment_date']]);
        $exports = Export::where('export_list_id', $id)->get();
        foreach ($exports as $export) {
            if (!in_array($export->id, $jobs))
                self::delete($export->id);
        }
        return $list;
    }

    public function deleteList($id)
    {
        ExportList::where('id', $id)->delete();
        Export::where('export_list_id', $id)->delete();

        return 'deleted';
    }

    public static function createOrUpdate($id = null, $request)
    {
        $model = is_null($id) ? new User : User::findOrFail($id);
        $model->user_type = $request['role'];
        $model->name = $request['name'];
        $model->email = $request['email'];
        $model->dob_or_orgid = $request['dob_or_orgid'];
        $model->phone = $request['phone'];
        $model->mobile = $request['mobile'];

        if (!$id || $id && $request['password']) $model->password = bcrypt($request['password']);
        $model->detachAllRoles();
        $model->save();
        $model->attachRole($request['role']);

        $data = array();

        $user_meta = UserMeta::firstOrCreate(['user_id' => $model->id]);

        if ($request['role'] == env('ADMIN_ROLE_ID')) {
            $data['consumer_type'] = $request['consumer_type'];
            $user_meta->consumer_type = $request['consumer_type'];
        }
        $data['username'] = $request['username'];
        $data['post_code'] = $request['post_code'];
        $data['city'] = $request['city'];
        $data['town'] = $request['town'];
        $data['country'] = $request['country'];

        $user_meta->username = $request['username'];
        $user_meta->post_code = $request['post_code'];
        $user_meta->city = $request['city'];
        $user_meta->town = $request['town'];
        $user_meta->country = $request['country'];

        $user_meta->save();


        $blacklistUpdated = [];
        $userBlacklist = UsersBlacklist::where('user_id', $id)->get();
        $userTranslId = collect($userBlacklist)->pluck('translator_id')->all();

        $diff = null;
        if ($request['translator_ex']) {
            $diff = array_intersect($userTranslId, $request['translator_ex']);
        }
        if ($diff || $request['translator_ex']) {
            foreach ($request['translator_ex'] as $translatorId) {
                $blacklist = new UsersBlacklist();
                if ($model->id) {
                    $already_exist = UsersBlacklist::translatorExist($model->id, $translatorId);
                    if ($already_exist == 0) {
                        $blacklist->user_id = $model->id;
                        $blacklist->translator_id = $translatorId;
                        $blacklist->save();
                    }
                    $blacklistUpdated [] = $translatorId;
                }

            }
            if ($blacklistUpdated) {
                UsersBlacklist::deleteFromBlacklist($model->id, $blacklistUpdated);
            }
        } else {
            UsersBlacklist::where('user_id', $model->id)->delete();
        }


        if ($request['status'] == '1') {
            if ($model->status != '1') {
//                AdminHelper::enable($model->id);
            }
        } else {
            if ($model->status != '0') {
//                AdminHelper::disable($model->id);
            }
        }
        return $model ? $model : false;
    }

    public function prepareFile($id, $type = 'invoices')
    {
        $data = $this->exportInterface->getData($id, $type);

        if ($type == 'salaries')
            $file = $this->exportInterface->exportSalaries($data);
        else
            $file = $this->exportInterface->exportInvoices($data);

        return $file;
    }

}