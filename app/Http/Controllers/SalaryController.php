<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\SalaryRepository;

class SalaryController extends Controller
{
    protected $repository;

    public function __construct(SalaryRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Method to get salaries by company, user (translator)
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if($request->has('user_id') && $request->has('company_id'))
        {
            return $this->repository->where('company_id',  $request->get('company_id'))->where('user_id', $request->get('user_id'))->with('basicSalaries', 'inconvenienceSalaries', 'travelSalaries')->first();
        }
        elseif($request->has('user_id'))
        {
            return $this->repository->where('company_id', 0)->where('user_id', $request->get('user_id'))->with('basicSalaries', 'inconvenienceSalaries', 'travelSalaries')->first();
        }
        elseif($request->has('company_id'))
        {
            return $this->repository->where('company_id', $request->get('company_id'))->where('user_id', 0)->with('basicSalaries', 'inconvenienceSalaries', 'travelSalaries')->first();
        }
    }

    public function store(Request $request)
    {
        $data = $request->all();
        $this->repository->createOrUpdate($data);

    }
}
