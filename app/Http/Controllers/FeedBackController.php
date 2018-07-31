<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;
use DTApi\Repository\FeedBackRepository;

class FeedBackController extends Controller
{
    /*Function to save the feedback*/
    public function store(Request $request, FeedBackRepository $feedBackRepository)
    {
        $response = $feedBackRepository->makeFeedBack($request->all(),$request->__authenticatedUser);

        return response($response);

    }
}
