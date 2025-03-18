<?php

namespace App\Http\Controllers;

use App\Services\ManifestCheckerService;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

class ManifestCheckerController extends Controller
{
    use HttpResponse;
    public function getManifestData(Request $request){
        return (new ManifestCheckerService())->check($request);
    }
}
