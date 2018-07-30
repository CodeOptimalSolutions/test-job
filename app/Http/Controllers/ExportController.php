<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use DTApi\Models\Export;
use DTApi\Models\ExportList;
use Illuminate\Http\Request;
use DTApi\Repository\BookingRepository;
use DTApi\Repository\ExportRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

/**
 * Class ExportController
 * @package DTApi\Http\Controllers
 */
class ExportController extends Controller
{

    protected $repository;
    protected $exportRepository;

    public function __construct(BookingRepository $repository, ExportRepository $exportRepository)
    {
        $this->repository = $repository;
        $this->exportRepository = $exportRepository;
    }

    public function generateList(Request $request)
    {
        if ($request->has('prepare')) {
            $lists = ExportList::orderBy('created_at', 'desc')->get();
            return response($lists);
        }

        $jobs = $this->repository->getAll($request, 'all');

        $result_list = $this->exportRepository->prepareList($jobs);

        return response($result_list);
    }

    /**
     * Method to generate invoice list based on filtered bookings
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function generateInvoiceList(Request $request)
    {
        if ($request->has('prepare')) {
            $lists = ExportList::orderBy('created_at', 'desc')->get();
            return response($lists);
        }

        $jobs = $this->repository->getAll($request, 'all');

        $result_list = $this->exportRepository->prepareInvoicesList($jobs);

        return response($result_list);
    }

    public function saveList(Request $request)
    {
        $jobs = $this->repository->getAll($request, 'all');

//        if(!$request->has('invoice'))
        $jobs = $this->exportRepository->prepareList($jobs);
//        else
//            $jobs = $this->exportRepository->prepareInvoicesList($jobs);
        $data = array_only($request->all(), ['name', 'comment', 'type', 'payment_date']);
        $data['query'] = json_encode(array_only($request->all(), ['lang', 'status', 'to', 'from', 'customer_email', 'translator_email']), true);
        $result_list = $this->exportRepository->saveList($data, $jobs);

        return response($result_list);
    }

    public function updateList($id, Request $request)
    {
        $jobs = $request->get('id');
        $data = array_only($request->all(), ['name', 'comment', 'payment_date']);
        $result_list = $this->exportRepository->updateList($id, $data, $jobs);

        return response(['id' => $id]);
    }

    public function deleteList($id, Request $request)
    {
        $response = $this->exportRepository->deleteList($id);

        return response(['success' => $response]);
    }

    public function getList($id, Request $request)
    {
        if ($request->has('download'))
            $list = $this->exportRepository->prepareFile($id, $request->get('type'));
        else
            $list = ExportList::with('exports')->find($id);

        return response($list);
    }

    public function exportFile($filename)
    {
        Log::info('hi hi ', [$filename]);
        return response()->json(Redis::get($filename));

    }

}
