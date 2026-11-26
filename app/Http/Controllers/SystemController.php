<?php
namespace App\Http\Controllers;
use App\Actions\CheckSystemIntegrity;
class SystemController extends Controller {
    public function help() { return view('system.help'); }
    public function check(CheckSystemIntegrity $checker) {
        return response()->view('system.check',['report'=>$checker->check()])->header('Cache-Control','private, no-store');
    }
}
