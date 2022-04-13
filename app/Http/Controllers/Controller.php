<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    
    public function wantJson()
    {
        if(!request()->acceptsJson() || !request()->isJson()){
            throw new WantJSONException();
        }
    }

    public function currentUserId()
    {
        return $this->user()->id;
    }

    public function user()
    {
        return Auth::guard()->user() ?? auth('sanctum')->user();
    }
    
}
