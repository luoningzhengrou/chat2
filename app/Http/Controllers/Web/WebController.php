<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;

class WebController extends Controller
{
    public function index()
    {
        return response()->file(public_path().'/index.html');
    }
}
