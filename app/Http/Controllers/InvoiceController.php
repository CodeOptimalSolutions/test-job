<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\InvoiceRepository;

/**
 * Class InvoiceController
 * @package DTApi\Http\Controllers
 */
class InvoiceController extends Controller
{
    /**
     * @var InvoiceRepository
     */
    protected $repository;

    /**
     * InvoiceController constructor.
     * @param InvoiceRepository $repository
     */
    public function __construct(InvoiceRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Method to get invoice by company, department, user
     * @param Request $request
     * @return mixed
     */
    public function index(Request $request)
    {
        if($request->has('user_id'))
        {
            return $this->repository->where('company_id', 0)->where('department_id', 0)->where('user_id', $request->get('user_id'))->with('basicInvoices', 'inconvenienceInvoices', 'feeInvoices', 'emergencyInvoices', 'travelInvoices', 'businessRules')->first();
        }
        elseif($request->has('department_id'))
        {
            return $this->repository->where('company_id', 0)->where('department_id', $request->get('department_id'))->where('user_id', '0')->with('basicInvoices', 'inconvenienceInvoices', 'feeInvoices', 'emergencyInvoices', 'travelInvoices', 'businessRules')->first();
        }
        elseif($request->has('company_id'))
        {
            return $this->repository->where('company_id', $request->get('company_id'))->where('department_id', '0')->where('user_id', '0')->with('basicInvoices', 'inconvenienceInvoices', 'feeInvoices', 'emergencyInvoices', 'travelInvoices', 'businessRules')->first();
        }
    }

    /**
     * Method to create new invoice
     * @param Request $request
     */
    public function store(Request $request)
    {
        $this->repository->createOrUpdate($request->all());
    }
}
