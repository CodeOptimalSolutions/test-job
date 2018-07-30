<?php

namespace DTApi\Repository;

use DTApi\Models\LoginLog;

/**
 * Class LogsRepository
 * @package DTApi\Repository
 */
class LogsRepository extends BaseRepository
{

    protected $model;

    /**
     * @param Pages $model
     */
    function __construct(LoginLog $model)
    {
        parent::__construct($model);
    }

    /**
     * @param array $data
     * @return bool
     * @throws \Exceptions\ValidationException
     */
    public function validateOnCreate(array $data = [])
    {
        $validator = $this->validator($data, [
            'title' => 'required',
            'content' => 'required',
            'slug' => 'required|max:100|unique:pages',
        ]);

        return $this->_validate($validator);
    }

    /**
     * @param integer $id
     * @param array $data
     * @return bool
     * @throws \Exceptions\ValidationException
     */
    public function validateOnUpdate($id, array $data = [])
    {
        $validator = $this->validator($data, [
            'slug' => 'unique:pages'
        ]);

        return $this->_validate($validator);
    }


    public function create(array $data = []) {
        
        $page = parent::create(array_only($data, ['title', 'meta_title', 'meta_keywords', 'meta_description', 'content', 'slug']));

        return $page;

    }

    public function update($id, array $data = []) {

        $page = parent::update($id, array_only($data, ['title', 'meta_title', 'meta_keywords', 'meta_description', 'content', 'slug']));

        return $page;

    }

}