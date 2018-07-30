<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\CompanyRepository;

/**
 * Class CompanyController
 * @package DTApi\Http\Controllers
 */
class CompanyController extends Controller
{

    /**
     * @var
     */
    protected $companyRepository;
    protected $perPage = 15;

    public function __construct(CompanyRepository $companyRepository)
    {
        $this->companyRepository = $companyRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('all'))
            $companies = $this->companyRepository->all();
        else
            $companies = $this->companyRepository->paginate($this->perPage);

        return response($companies);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $this->companyRepository->createOrUpdate(null, $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $company = $this->companyRepository->with(['inconvenienceSettings', 'holidays', 'invoices.businessRules', 'salaries.travelSalaries'])->find($id);

        return response($company);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $this->companyRepository->createOrUpdate($id, $data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->companyRepository->delete($id);
    }
}
