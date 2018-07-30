<?php

namespace DTApi\Repository;

use DTApi\Models\CustomerSalary;

/**
 * Class CustomerSalaryRepository
 * @package DTApi\Repository
 */
class CustomerSalaryRepository extends BaseRepository
{

    protected $model;

    /**
     * @param CustomerSalary $model
     */
    function __construct(CustomerSalary $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate($data)
    {
        $salary = $this->model->firstOrNew(['user_id' => $data['user_id']]);
        $salary->user_id = $data['user_id'];
        $salary->physical_layman = $data['physical_layman'];
        $salary->phone_layman = $data['phone_layman'];
        $salary->physical_certified = $data['physical_certified'];
        $salary->phone_certified = $data['phone_certified'];
        $salary->phone_specialised = $data['phone_specialised'];
        $salary->physical_specialised = $data['physical_specialised'];
        $salary->travel_time_layman = $data['travel_time_layman'];
        $salary->travel_time_certified = $data['travel_time_certified'];
        $salary->travel_time_specialised = $data['travel_time_specialised'];
        $salary->km_price = $data['km_price'];
        $salary->inconvenient_layman = $data['inconvenient_layman'];
        $salary->inconvenient_certified = $data['inconvenient_certified'];
        $salary->inconvenient_specialised = $data['inconvenient_specialised'];
        $salary->transaction_layman = $data['transaction_layman'];
        $salary->transaction_certified = $data['transaction_certified'];
        $salary->transaction_specialised = $data['transaction_specialised'];
        $salary->transaction_phone_layman = $data['transaction_phone_layman'];
        $salary->transaction_phone_certified = $data['transaction_phone_certified'];
        $salary->transaction_phone_specialised = $data['transaction_phone_specialised'];
        $salary->save();
    }

}