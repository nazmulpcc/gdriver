<?php

namespace App\Http\Controllers;

use \Google_Client;
use App\Helpers\GDriver;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Jobs\HandleUpload;

class HomeController extends Controller
{
    private $client = false;

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, GDriver $driver)
    {
        $auth = false;
        if($driver->needsToken()){
            $auth = $driver->getAuthUrl();
        }
        return view('home', compact('auth'));
    }

    public function upload(Request $request, GDriver $driver)
    {
        $this->validate($request, [
            'url' => 'required|active_url'
        ]);
        if($driver->needsToken()){
            return redirect()->route('home');
        }
        HandleUpload::dispatch($driver, $request->input('url'));
    }

    public function googleCallback(Request $request, GDriver $driver)
    {
        if($request->has('code')){
            $driver->setAuthCode($request->input('code'));
        }
        return redirect(route('home'));
    }
}
