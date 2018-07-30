<?php

namespace DTApi\Repository;

use DTApi\Models\BusinessRules;
use DTApi\Models\InconvenienceInvoice;
use DTApi\Models\InconvenienceSettings;
use DTApi\Models\Invoice;
use DTApi\Models\FeeInvoice;
use DTApi\Models\BasicInvoice;
use DTApi\Models\EmergencyInvoice;
use DTApi\Models\TravelInvoice;

/**
 * Class InvoiceRepository
 * @package DTApi\Repository
 */
class InvoiceRepository extends BaseRepository
{

    protected $model;

    /**
     * @param Invoice $model
     */
    function __construct(Invoice $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate($data)
    {
        if($data['user_id'] != '0' && $data['user_id'] != '')
            $type ='user_id';
        elseif($data['department_id'] != '0' && $data['department_id'] != '')
            $type = 'department_id';
        else
            $type = 'company_id';

        if($data['invoice_id'] != 0 && $data['invoice_id'] != '') {
            $invoice = $this->model->find($data['invoice_id']);
            $invoice->update(array_only($data, [$type]));
        }
        else {
            $invoice = $this->model->create(array_only($data, [$type]));
            $data['invoice_id'] = $invoice->id;
        }

        $this->createOrUpdateBusinessRules($data);
        $this->createOrUpdateBasic($data);
        $this->createOrUpdateFee($data);
        $this->createOrUpdateEmergency($data);
        $this->createOrUpdateInconvenienceSettings($data, $type);
        $this->createOrUpdateInconvenience($data);
        $this->createOrUpdateTravel($data);

//        $salary = $this->model->firstOrNew(['user_id' => $data['user_id']]);
//        $salary->user_id = $data['user_id'];
//        $salary->physical_layman = $data['physical_layman'];
//        $salary->phone_layman = $data['phone_layman'];
//        $salary->physical_certified = $data['physical_certified'];
//        $salary->phone_certified = $data['phone_certified'];
//        $salary->physical_specialised = $data['physical_specialised'];
//        $salary->phone_specialised = $data['phone_specialised'];
//        $salary->travel_time_layman = $data['travel_time_layman'];
//        $salary->travel_time_certified = $data['travel_time_certified'];
//        $salary->travel_time_specialised = $data['travel_time_specialised'];
//        $salary->km_price = $data['km_price'];
//        $salary->inconvenient_layman = $data['inconvenient_layman'];
//        $salary->inconvenient_certified = $data['inconvenient_certified'];
//        $salary->inconvenient_specialised = $data['inconvenient_specialised'];
//        $salary->transaction_layman = $data['transaction_layman'];
//        $salary->transaction_certified = $data['transaction_certified'];
//        $salary->transaction_specialised = $data['transaction_specialised'];
//        $salary->save();
    }

    protected function createOrUpdateBusinessRules($data)
    {
        $invoice = BusinessRules::firstOrCreate(['invoice_id' => $data['invoice_id']]);
        $invoice->physical_min = $data['business_rules']['physical_min'];
        $invoice->physical_after = $data['business_rules']['physical_after'];
        $invoice->phone_min = $data['business_rules']['phone_min'];
        $invoice->phone_after = $data['business_rules']['phone_after'];
        $invoice->save();
    }

    protected function createOrUpdateBasic($data)
    {
        foreach ($data['basic_invoices']['translator_level'] as $basic_invoice)
        {
            $invoice = BasicInvoice::firstOrCreate(['invoice_id' => $data['invoice_id'], 'type_id' => $basic_invoice['type_id']]);
            $invoice->physical_min = $basic_invoice['physical_min'];
            $invoice->phone_min = $basic_invoice['phone_min'];
            $invoice->physical_after = $basic_invoice['physical_after'];
            $invoice->phone_after = $basic_invoice['phone_after'];
            $invoice->save();
        }
    }

    protected function createOrUpdateFee($data)
    {
        $invoice = FeeInvoice::firstOrCreate(['invoice_id' => $data['invoice_id']]);
        $invoice->booking_over_phone = $data['fee_invoices']['booking_over_phone'];
        $invoice->booking_online = $data['fee_invoices']['booking_online'];
        $invoice->charge_fee = isset($data['fee_invoices']['charge_fee']) && $data['fee_invoices']['charge_fee'] == 'on' ? 'yes' : 'no';
        $invoice->save();
    }

    protected function createOrUpdateEmergency($data)
    {
        $invoice = EmergencyInvoice::firstOrCreate(['invoice_id' => $data['invoice_id']]);
        $invoice->charge_emergency = isset($data['emergency_invoices']['charge_emergency']) && $data['emergency_invoices']['charge_emergency'] == 'on' ? 'yes' : 'no';
        $invoice->physical_session = $data['emergency_invoices']['physical_session'];
        $invoice->phone_session = $data['emergency_invoices']['phone_session'];
        $invoice->save();
    }

    protected function createOrUpdateInconvenienceSettings($data, $type)
    {
        if($type == 'company_id')
            $settings = InconvenienceSettings::firstOrCreate(['company_id' => $data['company_id'], 'department_id' => 0, 'user_id' => 0]);
        elseif($type == 'department_id')
            $settings = InconvenienceSettings::firstOrCreate(['company_id' => 0, 'department_id' => $data['department_id'], 'user_id' => 0]);
        else
            $settings = InconvenienceSettings::firstOrCreate(['company_id' => 0, 'department_id' => 0, 'user_id' => $data['user_id']]);

        $settings->weekends_day_before_after = isset($data['inconvenience_settings']['weekends_day_before_after']) && $data['inconvenience_settings']['weekends_day_before_after'] == 'on' ? 'yes' : 'no';
        $settings->holiday_day_before_after = isset($data['inconvenience_settings']['holiday_day_before_after']) && $data['inconvenience_settings']['holiday_day_before_after'] == 'on' ? 'yes' : 'no';
        $settings->inconvenience_standard_rate = isset($data['inconvenience_settings']['inconvenience_standard_rate']) && $data['inconvenience_settings']['inconvenience_standard_rate'] == 'on' ? 'yes' : 'no';
        $settings->standard_rate = $data['inconvenience_settings']['standard_rate'];
        $settings->weekdays_start = $data['inconvenience_settings']['weekdays_start'];
        $settings->weekdays_end = $data['inconvenience_settings']['weekdays_end'];
        $settings->weekend_start = $data['inconvenience_settings']['weekend_start'];
        $settings->weekend_end = $data['inconvenience_settings']['weekend_end'];
        $settings->holiday_start = $data['inconvenience_settings']['holiday_start'];
        $settings->holiday_end = $data['inconvenience_settings']['holiday_end'];
        $settings->save();
    }

    protected function createOrUpdateInconvenience($data)
    {
        foreach ($data['inconvenience_invoices']['translator_level'] as $basic_invoice)
        {
            $invoice = InconvenienceInvoice::firstOrCreate(['invoice_id' => $data['invoice_id'], 'type_id' => $basic_invoice['type_id']]);
            $invoice->physical_weekday_min = $basic_invoice['physical_weekday_min'];
            $invoice->physical_weekday_after = $basic_invoice['physical_weekday_after'];
            $invoice->phone_weekday_min = $basic_invoice['phone_weekday_min'];
            $invoice->phone_weekday_after = $basic_invoice['phone_weekday_after'];
            $invoice->physical_weekend_min = $basic_invoice['physical_weekend_min'];
            $invoice->physical_weekend_after = $basic_invoice['physical_weekend_after'];
            $invoice->phone_weekend_min = $basic_invoice['phone_weekend_min'];
            $invoice->phone_weekend_after = $basic_invoice['phone_weekend_after'];
            $invoice->physical_holiday_min = $basic_invoice['physical_holiday_min'];
            $invoice->physical_holiday_after = $basic_invoice['physical_holiday_after'];
            $invoice->phone_holiday_min = $basic_invoice['phone_holiday_min'];
            $invoice->phone_holiday_after = $basic_invoice['phone_holiday_after'];
            $invoice->save();
        }
    }

    protected function createOrUpdateTravel($data)
    {
        $invoice = TravelInvoice::firstOrCreate(['invoice_id' => $data['invoice_id']]);
        $invoice->km_reimbursement = isset($data['travel_invoices']['km_reimbursement']) && $data['travel_invoices']['km_reimbursement'] == 'on' ? 'yes' : 'no';
        $invoice->travel_time = isset($data['travel_invoices']['travel_time']) && $data['travel_invoices']['travel_time'] == 'on' ? 'yes' : 'no';
        $invoice->minimum_time_to_eligible = isset($data['travel_invoices']['minimum_time_to_eligible']) && $data['travel_invoices']['minimum_time_to_eligible'] == 'on' ? 'yes' : 'no';
        $invoice->maximum_km = $data['travel_invoices']['maximum_km'];
        $invoice->per_km = $data['travel_invoices']['per_km'];
        $invoice->per_hour = $data['travel_invoices']['per_hour'];
        $invoice->maximum_time = $data['travel_invoices']['maximum_time'];
        $invoice->save();
    }
    
}