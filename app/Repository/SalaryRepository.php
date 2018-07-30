<?php

namespace DTApi\Repository;

use DTApi\Models\BasicSalaries;
use DTApi\Models\InconvenienceSalary;
use DTApi\Models\Salary;
use DTApi\Models\TravelSalaries;

/**
 * Class SalaryRepository
 * @package DTApi\Repository
 */
class SalaryRepository extends BaseRepository
{

    protected $model;

    /**
     * @param Salary $model
     */
    function __construct(Salary $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate($data)
    {
        if ($data['user_id'] == '') $data['user_id'] = 0;
        if ($data['company_id'] == '') $data['company_id'] = 0;

        $salary = $this->model->firstOrNew(['user_id' => $data['user_id'], 'company_id' => $data['company_id']]);
        $salary->user_id = $data['user_id'];
        $salary->company_id = $data['company_id'];
        $salary->standard_rate = $data['inconvenience_settings']['standard_rate'];
        $salary->save();

        $data['salary_id'] = $salary->id;
        $this->createOrUpdateBasic($data);
        $this->createOrUpdateInconvenience($data);
        $this->createOrUpdateTravel($data);
    }

    protected function createOrUpdateBasic($data)
    {
        foreach ($data['basic_salaries']['translator_level'] as $basic_salary) {
            $salary = BasicSalaries::firstOrCreate(['salary_id' => $data['salary_id'], 'type_id' => $basic_salary['type_id']]);
            $salary->physical_min = $basic_salary['physical_min'];
            $salary->phone_min = $basic_salary['phone_min'];
            $salary->physical_after = $basic_salary['physical_after'];
            $salary->phone_after = $basic_salary['phone_after'];
            $salary->save();
        }
    }

    protected function createOrUpdateInconvenience($data)
    {
        if (isset($data['inconvenience_salaries']))
            foreach ($data['inconvenience_salaries']['translator_level'] as $basic_salary) {
                $salary = InconvenienceSalary::firstOrCreate(['salary_id' => $data['salary_id'], 'type_id' => $basic_salary['type_id']]);
                $salary->physical_weekday_min = $basic_salary['physical_weekday_min'];
                $salary->physical_weekday_after = $basic_salary['physical_weekday_after'];
                $salary->phone_weekday_min = $basic_salary['phone_weekday_min'];
                $salary->phone_weekday_after = $basic_salary['phone_weekday_after'];
                $salary->physical_weekend_min = $basic_salary['physical_weekend_min'];
                $salary->physical_weekend_after = $basic_salary['physical_weekend_after'];
                $salary->phone_weekend_min = $basic_salary['phone_weekend_min'];
                $salary->phone_weekend_after = $basic_salary['phone_weekend_after'];
                $salary->physical_holiday_min = $basic_salary['physical_holiday_min'];
                $salary->physical_holiday_after = $basic_salary['physical_holiday_after'];
                $salary->phone_holiday_min = $basic_salary['phone_holiday_min'];
                $salary->phone_holiday_after = $basic_salary['phone_holiday_after'];
                $salary->save();
            }
    }

    protected function createOrUpdateTravel($data)
    {
        $salary = TravelSalaries::firstOrCreate(['salary_id' => $data['salary_id']]);
        $salary->km_reimbursement = isset($data['travel_salaries']['km_reimbursement']) && $data['travel_salaries']['km_reimbursement'] == 'on' ? 'yes' : 'no';
        $salary->travel_time = isset($data['travel_salaries']['travel_time']) && $data['travel_salaries']['travel_time'] == 'on' ? 'yes' : 'no';
        $salary->minimum_time_to_eligible = isset($data['travel_salaries']['minimum_time_to_eligible']) && $data['travel_salaries']['minimum_time_to_eligible'] == 'on' ? 'yes' : 'no';
        $salary->maximum_km = $data['travel_salaries']['maximum_km'];
        $salary->per_km = $data['travel_salaries']['per_km'];
        $salary->per_hour = $data['travel_salaries']['per_hour'];
        $salary->maximum_time = $data['travel_salaries']['maximum_time'];
        $salary->save();
    }

}