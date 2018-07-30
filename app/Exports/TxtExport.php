<?php

namespace DTApi\Exports;

use Carbon\Carbon;
use DTApi\Models\Department;
use DTApi\Models\Job;
use DTApi\Models\TranslatorLevel;
use DTApi\Models\User;
use DTApi\Models\Export;
use DTApi\Models\Settings;
use DTApi\Models\ExportList;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Storage;

class TxtExport implements ExportInterface
{

    public function __construct()
    {
    }

    public function getData($id, $type)
    {
        $list = ExportList::find($id);
        $data = Export::where('export_list_id', $id)->where('t_id', '!=', 0)->orderBy('t_id')->get();
        if ($type == 'salaries')
            $data = collect($data)->groupBy('t_id')->all();
        else
            $data = collect($data)->groupBy('cost_place')->all();

        return ['list' => $list, 'data' => $data];
    }

    public function getInvoicesData($id)
    {

    }

    public function exportSalaries($data)
    {
        $result = '';
        $content = '';

        $header = '[SALARY]AnstId;Name;Box;Zip;City;UtbetDatum;Skattetabell;TaxCount;PeriodText
[SALARYLINES]AnstId;RadNr;Pt;UtbetDatum;LoneArtId;Text;Konto;Antal;aPris;Anm;enhet
[ANSTALLD]Id;PersonNr;Name;Box;Zip;City;Tel;Tel2;BankKonto;SkattTabell;PrintToEmail;Email;UtbetIntervall
';

        foreach ($data['data'] as $t_id => $translators_info) {
            $translator = User::with(['userMeta', 'salaries'])->find($t_id);
            if (strlen($t_id) == 1)
                $id_full = '0000' . $t_id;
            elseif (strlen($t_id) == 2)
                $id_full = '000' . $t_id;
            elseif (strlen($t_id) == 3)
                $id_full = '00' . $t_id;
            elseif (strlen($t_id) == 4)
                $id_full = '0' . $t_id;
            elseif (strlen($t_id) == 5)
                $id_full = $t_id;

            $date = Carbon::createFromFormat('Y-m-d H:i:s', $data['list']['payment_date'])->format('Y-m-d');

            $content .= '
[ANSTALLD]
';
            $address = isset($translator) ? $translator->userMeta->address . ' ' . $translator->userMeta->address_2 : '';
            $post_code = isset($translator) ? $translator->userMeta->post_code : '';
            $city = isset($translator) ? $translator->userMeta->town : '';
            $dob_or_orgid = isset($translator) ? $translator->dob_or_orgid : '';
            $t_name = isset($translator) ? $translator->name : '';
            $phone = isset($translator) ? $translator->phone : '';
            $mobile = isset($translator) ? $translator->mobile : '';
            $email = isset($translator) ? $translator->email : '';
            $content .= "$id_full;$dob_or_orgid;$t_name;$address;$post_code;$city;$phone;$mobile;;30,00;True;$email;225";

            $query = json_decode($data['list']['query'], true);
            $date_from = $query['from'];
            $date_to = $query['to'];

            $content .= "
[SALARY]
$id_full;$t_name;$address;$post_code;$city;$date;30,00;True;$date_from - $date_to";

            $content .= '
[SALARYLINES]
';
            $row = 0;
            foreach ($translators_info as $k => $job) {
                $row = $row + 1;
                $due_date = Carbon::createFromFormat('Y-m-d H:i:s', $job->due);
                $date = Carbon::createFromDate($due_date->year, $due_date->month, 25)->addMonth()->format('Y-m-d');
                $due_date = $due_date->format('Y-m-d');
                $due_time = Carbon::createFromFormat('Y-m-d H:i:s', $job->due)->format('H:i');
                $description = $job->physic == 'yes' ? 'Platstolkning - #' . $job->booking_id : 'Telefontolkning #' . $job->booking_id;

                $customer = User::with(['userMeta', 'customerSalary', 'company.salaries.basicSalaries', 'company.salaries.inconvenienceSalaries', 'company.salaries.travelSalaries', 'company.invoices.businessRules', 'salaries.basicSalaries', 'salaries.inconvenienceSalaries'])->find($job->c_id);

                $invoices = $customer->company->invoices;
                $salaries = $translator->salaries;
                $salary = collect($salaries)->where('company_id', $customer->company->id)->first();

                if ($translator->userMeta->translator_level == 'Layman')
                    $translator_level = 'layman';
                elseif ($translator->userMeta->translator_level == 'Certified')
                    $translator_level = 'certified';
                elseif ($translator->userMeta->translator_level == 'Read Translation courses')
                    $translator_level = 'read';
                else
                    $translator_level = 'specialised';

                $physical_level = 'physical_' . $translator_level;
                $phone_level = 'phone_' . $translator_level;
                $travel_level = 'travel_time_' . $translator_level;
                $inconvenient_level = 'inconvenient_' . $translator_level;

                $translator_level_id = TranslatorLevel::where('code', $translator_level)->first()->id;

                if ($job->physic == 'yes') {
                    $booking_type = 'physical';
                } else {
                    $booking_type = 'phone';
                }

                if (is_null($salary)) {
                    $salary = collect($customer->salaries)->where('company_id', 0)->first();
                }

                $company_salary = collect($customer->company->salaries)->where('user_id', 0)->first();
                if (is_null($salary)) {
                    $salary = $company_salary;
                } elseif (is_null($customer->customerSalary)) {
                    $physical_level = 'physical_session';
                    $phone_level = 'phone_session';
                    $travel_level = 'travel_time';
                    $inconvenient_level = 'additional';
                }

                if ($job->physic == 'yes') {
//                    $booking_salary = $salary[$physical_level];
                } else {
//                    $booking_salary = $salary[$phone_level];
                }

                $basic_salaries = $salary->basicSalaries;
                $price = $basic_salaries->where('type_id', $translator_level_id)->first();

                $price_for_min = $price[$booking_type . '_min'];
                $business_rules = $invoices->businessRules()->first();
                $session_time_min = $business_rules[$booking_type . '_min'];

                if ($session_time_min < 1)
                    $price_for_min = $price_for_min / $session_time_min;

                $session = $job->rounded_session_length != '' ? $job->rounded_session_length : '';
                $session_time_after = $job->rounded_session_length - $session_time_min;

                if ($session_time_after <= 0)
                    $price_for_after = $price[$booking_type . '_after'];
                else
                    $price_for_after = $price[$booking_type . '_after'] / $business_rules[$booking_type . '_after'];

                if ($session > $session_time_min)
                    $session = $session_time_min;

                $session_time_formatted = ($session != '') ? number_format($session, 2, ',', '') : $session;
                $session_time_after_formatted = ($session_time_after != '') ? number_format($session_time_after, 2, ',', '') : '';
                $description_min = $job->physic == 'yes' ? 'Platstolkning första ' . $session_time_min . ' tim - #' . $job->booking_id : 'Telefontolkning första ' . $session_time_min . ' tim - ' . $job->booking_id;
                $description_after = $job->physic == 'yes' ? 'Platstolkning efter första ' . $session_time_min . ' tim - #' . $job->booking_id : 'Telefontolkning efter första ' . $session_time_min . ' tim - #' . $job->booking_id;

                $content .= "$id_full;$row;$job->customer_id;$date;010;$description_min;7010;$session_time_formatted;$price_for_min;$due_date     kl $due_time;tim
";
                $row++;
                $content .= "$id_full;$row;$job->customer_id;$date;010;$description_after;7010;$session_time_after_formatted;$price_for_after;$due_date     kl $due_time;tim
";

                if ($job->inconvenience_time != '' && $job->inconvenience_time > 0) {
                    $row++;
                    $description = 'OB-tillägg - #' . $job->booking_id;
                    $it = number_format($job->inconvenience_time, 2, ',', '');
                    $salary_it = $salary->inconvenienceSalaries;
                    $price_it = $salary_it->where('type_id', $translator_level_id)->first();
                    $price_for_min = $price_it[$booking_type . '_' . $job->inconvenience_type . '_min'];
                    $price_for_after = $price_it[$booking_type . '_' . $job->inconvenience_type . '_after'];
                    $session_time_after = $job->inconvenience_time - $business_rules[$booking_type . '_min'];
                    $description_min = 'OB-tillägg första ' . $session_time_min . ' tim - ' . $job->booking_id;
                    $description_after = 'OB-tillägg efter första ' . $session_time_min . ' tim - ' . $job->booking_id;

                    $inconvenience_settings = $customer->company->inconvenienceSettings;

                    if($inconvenience_settings->inconvenience_standard_rate == 'yes')
                    {
                        $content .= "$id_full;$row;$job->customer_id;$date;170;$description;7012;$it;$inconvenience_settings->standard_rate;;tim
";
                    }
                    else if ($price_for_min != $price_for_after && $session_time_after != '' && $job->inconvenience_time == $job->rounded_session_length) {
                        $content .= "$id_full;$row;$job->customer_id;$date;170;$description_min;7012;$session_time_min;$price_for_min;;tim
";
                        $row++;
                        $content .= "$id_full;$row;$job->customer_id;$date;170;$description_after;7012;$session_time_after;$price_for_after;;tim
";
                    }
                    else {
                        $content .= "$id_full;$row;$job->customer_id;$date;170;$description;7012;$it;$price_for_after;;tim
";
                    }

                }

                $job_full = Job::with('distance')->find($job->booking_id);

                if ($job->physic && $job->travel_time != '' && $job->travel_time != 'N/A') {
                    $travel_time = $job->travel_time / 60;
                    $travel_distance = $job->travel_km;
                    if ($company_salary->travelSalaries->travel_time == 'yes') {
                        $price_travel_time = $salary->travelSalaries->per_hour;
                        $price_travel_time = number_format($price_travel_time, 2, ',', '');
                        $row++;
                        if($travel_time > $company_salary->travelSalaries->maximum_time)
                            $travel_time = $company_salary->travelSalaries->maximum_time;
                        if ($company_salary->travelSalaries->minimum_time_to_eligible == 'yes' && $travel_time >= 0.5) {
                            $travel_time = number_format($travel_time, 2, ',', '');
                            $content .= "$id_full;$row;$job->customer_id;$date;351;Restid - #$job->booking_id ;7011;$travel_time;$price_travel_time;;tim
";
                        } else {
                            $travel_time = number_format($travel_time, 2, ',', '');
                            $content .= "$id_full;$row;$job->customer_id;$date;351;Restid - #$job->booking_id ;7011;$travel_time;$price_travel_time;;tim
";
                        }
                    }
                    if ($company_salary->travelSalaries->km_reimbursement == 'yes' && $travel_distance != 'N/A') {
                        if ($travel_distance >= $salary->travelSalaries->maximum_km)
                            $travel_distance = $salary->travelSalaries->maximum_km;
                        $price_km = $salary->travelSalaries->pre_km;
                        $row++;
                        $content .= "$id_full;$row;$job->customer_id;$date;350;Skattefri km-ersättning - #$job->booking_id ;7331;$travel_distance;1,85;;km
";
                    }

                }

            }
            $row++;
            $content .= "$id_full;$row;;$date;;;;;;;;;;;;
";
            $row++;
            $content .= "$id_full;$row;;$date;;Tack för att du gör en fantastisk insats i allt ditt arbete. Vid frågor, tveka inte kontakta oss på jaskarn@digitaltolk.se;;;;;;;;;
";

        }

        $result = $header . $content;

        $timestamp = Carbon::now()->timestamp;

        Redis::set('generatedSalary_' . $timestamp . '.txt', $result);
        return ['filename' => 'generatedSalary_' . $timestamp . '.txt'];
    }

    public function exportInvoices($data)
    {
        $result = '';
        $content = '';

        $header = '[KUND]Id;Name;Box;Zip;City;Telephone;YourRef;Orgnr;Email;FaktToEmail;BetVillkor
[ORD]OrderNr;Typ;OrderTyp;KundId;Name;Box;Zip;City;YourRef;OurRef;kst
[ORDERLN]OrderNr;Typ;RadNr;Beskr;Apris;LevAntal;Enhet

';

        foreach ($data['data'] as $department_id => $row) {
            $department = Department::with(['company.invoices', 'invoices'])->find($department_id);
            $company = $department->company;
            $email_invoice = $company->email_invoice == 'yes' ? 'True' : 'False';
            $content .= "[KUND]
$department_id;$company->name;$company->address; $company->post_code;$company->city;$company->phone;$company->reference_person;$company->organization_number;$company->email;$email_invoice;30";

            $orderNumber = Redis::get('orderNumber') === null ? 200010 : Redis::get('orderNumber') + 10;

            $content .= "
[ORD]
$orderNumber;2;2;$company->id;$department->name;$department->address; $department->post_code;$department->city;$department->reference_person;Virpal Singh;12345";

            if ($department->invoices) {
                $invoices = $department->invoices;
            } else {
                $invoices = $company->invoices;
            }

            $basic_invoices = $invoices->basicInvoices;

            $content .= '
[ORDERLN]
';
            $rowNumber = 0;
            foreach ($row as $booking) {
                $translator = User::with(['userMeta', 'salaries'])->find($booking->t_id);
                $rowNumber = $rowNumber + 1;
                $due_date = Carbon::createFromFormat('Y-m-d H:i:s', $booking->due);
                $date = Carbon::createFromDate($due_date->year, $due_date->month, 25)->addMonth()->format('Y-m-d');
                $due_date = $due_date->format('Y-m-d');
                $due_time = Carbon::createFromFormat('Y-m-d H:i:s', $booking->due)->format('H:i');

                if ($translator->userMeta->translator_level == 'Layman' || $translator->userMeta->translator_level == 'Read Translation courses')
                    $translator_level = 'layman';
                elseif ($translator->userMeta->translator_level == 'Certified')
                    $translator_level = 'certified';
                else
                    $translator_level = 'specialised';

                $translator_level_id = TranslatorLevel::where('code', $translator_level)->first()->id;

                if ($booking->physic == 'yes') {
                    $booking_type = 'physical';
                } else {
                    $booking_type = 'phone';
                }

                $price = $basic_invoices->where('type_id', $translator_level_id)->first();

                $price_for_min = $price[$booking_type . '_min'];
                $price_for_after = $price[$booking_type . '_after'];

                $business_rules = $invoices->businessRules()->first();
                $session_time_min = $business_rules[$booking_type . '_min'];

                if ($session_time_min < 1 && $session_time_min != 0)
                    $price_for_min = $price_for_min / $session_time_min;

                $session = $booking->rounded_session_length != '' ? $booking->rounded_session_length : '';
                $session_time_after = $booking->rounded_session_length - $session_time_min;

                if ($session_time_after <= 0)
                    $price_for_after = $price[$booking_type . '_after'];
                else if($business_rules[$booking_type . '_after'] != 0)
                    $price_for_after = $price[$booking_type . '_after'] / $business_rules[$booking_type . '_after'];

                if ($session > $session_time_min)
                    $session = $session_time_min;

                $session_time_formatted = ($session != '') ? number_format($session, 2, ',', '') : '';
                $session_time_after_formatted = ($session_time_after != '') ? number_format($session_time_after, 2, ',', '') : '';

                $description_min = $booking->physic == 'yes' ? 'Platstolkning första ' . $session_time_min . ' tim - ' . $booking->booking_id . ' - ' . $booking->c_ref . ' - ' .$due_date . '     kl ' . $due_time : 'Telefontolkning första ' . $session_time_min . ' tim - ' . $booking->booking_id . ' - ' . $booking->c_ref . ' - ' .$due_date . '     kl ' . $due_time;
                $description_after = $booking->physic == 'yes' ? 'Platstolkning efter första ' . $session_time_min . ' tim - ' . $booking->booking_id . ' - ' . $booking->c_ref . ' - ' .$due_date . '     kl ' . $due_time : 'Telefontolkning efter första ' . $session_time_min . ' tim - ' . $booking->booking_id . ' - ' . $booking->c_ref . ' - ' .$due_date . '     kl ' . $due_time;

                $content .= "$orderNumber;2;$rowNumber;$description_min;$price_for_min;$session_time_formatted;tim
";
                $rowNumber++;
                $content .= "$orderNumber;2;$rowNumber;$description_after;$price_for_after;$session_time_after_formatted;tim
";
                $rowNumber++;

                if ($booking->inconvenience_time != '' && $booking->inconvenience_time > 0) {

                    $session_time_after = $booking->inconvenience_time - $business_rules[$booking_type . '_min'];
                    $description = 'OB-tillägg - ' . $booking->booking_id . ' - ' . $booking->c_ref;
                    $description_min = 'OB-tillägg första ' . $session_time_min . ' tim - ' . $booking->booking_id . ' - ' . $booking->c_ref;
                    $description_after = 'OB-tillägg efter första ' . $session_time_min . ' tim - ' . $booking->booking_id . ' - ' . $booking->c_ref;
                    $it = number_format($booking->inconvenience_time, 2, ',', '');
                    $session_time_min = number_format($session_time_min, 2, ',', '');
                    $session_time_after = number_format($session_time_after, 2, ',', '');
                    $inconvenience_invoice = $invoices->inconvenienceInvoices;
                    $inconvenience_invoice = $inconvenience_invoice->where('type_id', $translator_level_id)->first();
                    $price_inconvenience_min = $inconvenience_invoice[$booking_type . '_' . $booking->inconvenience_type . '_min'];
                    $price_inconvenience_after = $inconvenience_invoice[$booking_type . '_' . $booking->inconvenience_type . '_after'];

                    $inconvenience_settings = $company->inconvenienceSettings;

                    if($inconvenience_settings->inconvenience_standard_rate == 'yes')
                    {
                        $content .= "$orderNumber;2;$rowNumber;$description;$inconvenience_settings->standard_rate;$it;tim
";
                        $rowNumber++;
                    }
                    else if ($price_for_min != $price_for_after && $session_time_after != '' && $booking->inconvenience_time == $booking->rounded_session_length) {
                        $content .= "$orderNumber;2;$rowNumber;$description_min;$price_inconvenience_min;$session_time_min;tim
";
                        $rowNumber++;
                        $content .= "$orderNumber;2;$rowNumber;$description_after;$price_inconvenience_after;$session_time_after;tim
";
                        $rowNumber++;
                    }
                    else {
                        $content .= "$orderNumber;2;$rowNumber;$description;$price_inconvenience_after;$it;tim
";
                        $rowNumber++;
                    }

                }

                if ($invoices->feeInvoices->charge_fee == 'yes') {
                    if ($booking->by_admin == 'yes')
                        $price_fee = $invoices->feeInvoices->booking_over_phone;
                    else
                        $price_fee = $invoices->feeInvoices->booking_online;
                    $description_fee = 'Förmedlingsavgift - ' . $booking->booking_id . ' - ' . $booking->c_ref;
                    $content .= "$orderNumber;2;$rowNumber;$description_fee;$price_fee;1;st
";
                    $rowNumber++;
                }

                $job_full = Job::with('distance')->find($booking->booking_id);


                if ($booking->physic && $booking->travel_time != '' && $booking->travel_time != 'N/A') {

                    $travel_time = $booking->travel_time / 60;
                    $travel_distance = $booking->travel_km;
                    if ($invoices->travelInvoices->travel_time == 'yes') {
                        $price_travel_time = $invoices->travelInvoices->per_hour;
                        $price_travel_time = number_format($price_travel_time, 2, ',', '');
                        $row++;
                        if($travel_time > $invoices->travelInvoices->maximum_time)
                            $travel_time = $invoices->travelInvoices->maximum_time;
                        if ($invoices->travelInvoices->minimum_time_to_eligible == 'yes' && $travel_time >= 0.5) {
                            $travel_time = number_format($travel_time, 2, ',', '');
                            $content .= "$orderNumber;2;$rowNumber;Restid - $booking->booking_id . ' - ' . $booking->c_ref;$price_travel_time;$travel_time;tim
";
                        } else {
                            $travel_time = number_format($travel_time, 2, ',', '');
                            $content .= "$orderNumber;2;$rowNumber;Restid - $booking->booking_id . ' - ' . $booking->c_ref;$price_travel_time;$travel_time;tim
";
                        }
                        $rowNumber++;
                    }
                    if ($invoices->travelInvoices->km_reimbursement == 'yes') {
                        if ($travel_distance >= $invoices->travelInvoices->maximum_km)
                            $travel_distance = $invoices->travelInvoices->maximum_km;
                        $price_km = $invoices->travelInvoices->pre_km;
                        $content .= "$orderNumber;2;$rowNumber;KM-ersättning - $booking->booking_id  . ' - ' . $booking->c_ref;$price_km;$travel_distance;km
";
                        $rowNumber++;
                    }
                }

            }

            Redis::set('orderNumber', $orderNumber);
        }

        $result = $header . $content;

        $timestamp = Carbon::now()->timestamp;

        Redis::set('generatedInvoice_' . $timestamp . '.txt', $result);
        return ['filename' => 'generatedInvoice_' . $timestamp . '.txt'];

    }

}