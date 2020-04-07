<?php


namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;


class ApiTest extends Controller
{
    public function test(Request $request)
    {
        $number = $request->get('number');
        switch ($number){
            case 1:

                break;
            case 2:

                break;
            default:

        }

    }

}
