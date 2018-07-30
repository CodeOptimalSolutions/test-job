<?php

namespace DTApi\Repository;

use DTApi\Models\Job;
use DTApi\Models\Distance;

/**
 * Class DistanceRepository
 * @package DTApi\Repository
 */
class DistanceRepository extends BaseRepository
{

    /**
     * @var
     */
    protected $model;

    /**
     * @param Distance $model
     */
    function __construct(Distance $model)
    {
        parent::__construct($model);
    }

    /**
     * @param $post_data
     * @param $user
     * @return mixed
     */
    public function store($post_data, $user)
    {
        $loginUser = $user;
        $data = array(
            'user_id'    => $post_data['user_id'],
            'by_user_id' => $loginUser->id,
            'job_id'     => $post_data['job_id'],
            'distance'   => $post_data['distance'],
            'time'       => $post_data['time'],
        );
        Distance::create($data);
        $response['status'] = 'success';
        return $response;

    }

}