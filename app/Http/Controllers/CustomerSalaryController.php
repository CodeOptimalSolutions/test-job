<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\CustomerSalaryRepository;

class CustomerSalaryController extends Controller
{
    protected $repository;

    public function __construct(CustomerSalaryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function store(Request $request)
    {
        $this->repository->createOrUpdate($request->all());
    }
}
