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
        $post_data = $request->all();
        $user = $request->__authenticatedUser;
        $response = $distanceRepository->store($post_data, $user);

        return response($response);

    }
}
