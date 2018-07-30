<?php

namespace DTApi\Repository;

use DTApi\Models\Holiday;

/**
 * Class HolidaysRepository
 * @package DTApi\Repository
 */
class HolidaysRepository extends BaseRepository
{

    protected $model;

    /**
     * @param Holiday $model
     */
    function __construct(Holiday $model)
    {
        parent::__construct($model);
    }

    public function createOrUpdate($data)
    {
        $salary = $this->model->firstOrNew(['id' => $data['id']]);
        $salary->name = $data['name'];
        $salary->code = $data['code'];
        $salary->date_from = $data['date_from'];
        $salary->date_to = $data['date_to'];
        $salary->save();
    }

}