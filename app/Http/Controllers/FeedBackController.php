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
        $post_data = $request->all();
        $user = $request->__authenticatedUser;
        $response = $feedBackRepository->makeFeedBack($post_data, $user);

        return response($response);

    }
}
