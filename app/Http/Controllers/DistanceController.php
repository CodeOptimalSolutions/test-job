<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\DistanceRepository;

/**
 * Class DistanceController
 * @package DTApi\Http\Controllers
 */
class DistanceController extends Controller
{
    /**
     * @param Request $request
     * @param DistanceRepository $feedBackRepository
     * @return mixed
     */
    public function store(Request $request, DistanceRepository $distanceRepository)
    {
        
        $response = $distanceRepository->store($request->all(), $request->__authenticatedUser);

        return response($response);

    }
}
