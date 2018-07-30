<?php

namespace DTApi\Repository;

use DTApi\Models\Department;
use DTApi\Models\HolidaysToUser;
use Illuminate\Http\Request;

/**
 * Class DepartmentRepository
 * @package DTApi\Repository
 */
class DepartmentRepository extends BaseRepository
{

    /**
     * @var
     */
    protected $model;
    /**
     * @var int
     */
    protected $perPage = 15;

    /**
     * @param Department $model
     */
    function __construct(Department $model)
    {
        parent::__construct($model);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator|\Illuminate\Database\Eloquent\Builder
     */
    public function getDepartments(Request $request)
    {
        $departments = $this->query();

        if ($request->has('company_id'))
            $departments->where('company_id', $request->get('company_id'));

        if($request->has('all'))
            return $departments = $departments->with(['company', 'users'])->get();

        $departments = $departments->with(['company', 'users'])->paginate($this->perPage);

        return $departments;
    }

    /**
     * @param $id
     * @param $data
     * @return \Illuminate\Database\Eloquent\Model
     */
    public function createOrUpdate($id, $data)
    {

        if (isset($data['fee']) && $data['fee'] == 'on')
            $data['fee'] = 'yes';
        else
            $data['fee'] = 'no';

        if (isset($data['charge_ob']) && $data['charge_ob'] == 'on')
            $data['charge_ob'] = 'yes';
        else
            $data['charge_ob'] = 'no';

        if (isset($data['charge_km']) && $data['charge_km'] == 'on')
            $data['charge_km'] = 'yes';
        else
            $data['charge_km'] = 'no';

        if (isset($data['email_invoice']) && $data['email_invoice'] == 'on')
            $data['email_invoice'] = 'yes';
        else
            $data['email_invoice'] = 'no';

        if (is_null($id))
            $department = $this->create($data);
        else
            $department = $this->update($id, $data);

        if(isset($data['holidays']))
        {
            HolidaysToUser::where('department_id', $department->id)->delete();
            foreach ($data['holidays'] as $key => $holiday)
            {
                if($holiday == 'on')
                {
                    $holiday_to_user = HolidaysToUser::firstOrCreate(['department_id' => $department->id, 'holiday_code' => $key]);
                    $holiday_to_user->save();
                }
            }
        }

        return $department;
    }

}