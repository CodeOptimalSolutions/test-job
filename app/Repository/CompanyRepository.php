<?php

namespace DTApi\Repository;

use DTApi\Models\Company;
use DTApi\Models\HolidaysToUser;

/**
 * Class CompanyRepository
 * @package DTApi\Repository
 */
class CompanyRepository extends BaseRepository
{

    protected $model;

    /**
     * @param Company $model
     */
    function __construct(Company $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate($id, $data)
    {

        if(isset($data['fee']) && $data['fee'] == 'on')
            $data['fee'] = 'yes';
        else
            $data['fee'] = 'no';

        if(isset($data['charge_ob']) && $data['charge_ob'] == 'on')
            $data['charge_ob'] = 'yes';
        else
            $data['charge_ob'] = 'no';

        if(isset($data['charge_km']) && $data['charge_km'] == 'on')
            $data['charge_km'] = 'yes';
        else
            $data['charge_km'] = 'no';

        if(isset($data['email_invoice']) && $data['email_invoice'] == 'on')
            $data['email_invoice'] = 'yes';
        else
            $data['email_invoice'] = 'no';
        
        if (is_null($id))
            $company = $this->create($data);
        else
            $company = $this->update($id, $data);

        if(isset($data['holidays']))
        {
            HolidaysToUser::where('company_id', $company->id)->delete();
            foreach ($data['holidays'] as $key => $holiday)
            {
                if($holiday == 'on')
                {
                    $holiday_to_user = HolidaysToUser::firstOrCreate(['company_id' => $company->id, 'holiday_code' => $key]);
                    $holiday_to_user->save();
                }
            }
        }

        return $company;
    }

}