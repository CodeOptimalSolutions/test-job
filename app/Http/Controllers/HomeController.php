<?php

namespace DTApi\Http\Controllers;

use DTApi\Http\Requests;
use Illuminate\Http\Request;

/**
 * Class HomeController
 * @package DTApi\Http\Controllers
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('api');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('home');
    }

    /**
     * @return string
     */
    public function store() {

        return 'true';

    }

    /**
     * @return string
     */
    public function store2() {

        return 'true';

    }

    /**
     * @param Request $request
     * @return mixed
     */
    public function appData(Request $request)
    {
        return $request->__authenticatedApp;
    }

    /**
     * @param Request $request
     * @return array
     */
    public function userData(Request $request)
    {
        return [
            'app' => $request->__authenticatedApp,
            'user' => $request->__authenticatedUser,
        ];
    }

}
