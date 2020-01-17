<?php


namespace App\Http\Controllers\Web;


use App\Http\Controllers\Controller;

class WebController extends Controller
{
    public function index()
    {
        return response()->file(storage_path().'/web/index.html');
    }
}
