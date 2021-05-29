<?php

namespace App\Http\Controllers;

use App\Model\NextGameDraw;
use Illuminate\Http\Request;

class NextGameDrawController extends Controller
{
    function getIncreasedValue(){
        $nextDrawId = NextGameDraw::select('next_draw_id')->first();
        return $nextDrawId;
    }
}
