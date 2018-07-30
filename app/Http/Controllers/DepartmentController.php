<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\DepartmentRepository;

/**
 * Class DepartmentController
 * @package DTApi\Http\Controllers
 */
class DepartmentController extends Controller
{

    /**
     * @var
     */
    protected $departmentRepository;
    protected $perPage = 15;

    public function __construct(DepartmentRepository $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->has('all') && !$request['company_id'])
            $departments = $this->departmentRepository->with(['company', 'users'])->get();
        else
            $departments = $this->departmentRepository->getDepartments($request);

        return response($departments);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $this->departmentRepository->createOrUpdate(null, $data);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $department = $this->departmentRepository->with(['inconvenienceSettings', 'holidays'])->find($id);

        return response($department);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $data = $request->all();
        $this->departmentRepository->createOrUpdate($id, $data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $this->departmentRepository->delete($id);
    }
}
