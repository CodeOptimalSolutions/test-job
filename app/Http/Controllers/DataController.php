<?php

namespace DTApi\Http\Controllers;

use DTApi\Models\Town;
use DTApi\Models\Type;
use DTApi\Http\Requests;
use DTApi\Models\Language;
use Illuminate\Http\Request;
use DTApi\Models\TranslatorLevel;

/**
 * Class DataController
 * @package DTApi\Http\Controllers
 */
class DataController extends Controller
{
    /**
     * @param Request $request
     * @return mixed
     */
    public function getLangs(Request $request)
    {
        $langs = Language::all();
        return response([
            'user' => $request->__authenticatedUser,
            'langs' => $langs
        ]);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getTowns(Request $request)
    {
        $towns = Town::all();
        return response($towns);
    }

    /**
     * Method to get list of types. 
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getTypes()
    {
        $types = Type::all();
        return response($types);
    }

    /**
     * Method to get list of translator levels.
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Symfony\Component\HttpFoundation\Response
     */
    public function getLevels()
    {
        $levels = TranslatorLevel::all();
        return response($levels);
    }
}
