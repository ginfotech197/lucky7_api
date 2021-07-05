<?php

namespace App\Http\Controllers;

use App\Model\ResultDetails;
use Illuminate\Http\Request;
use App\Http\Controllers\ResultMasterController;
use App\Model\ResultMaster;
use App\Model\DrawMaster;
use Illuminate\Support\Facades\DB;

class ResultDetailsController extends Controller
{
    function getPreviousDrawResult(){
        $result = ResultMaster::
        select('result_masters.play_series_id','play_series.series_name', 'play_series.game_initial'
            ,'draw_masters.end_time', 'draw_masters.serial_number','dice_combination.dice1','dice_combination.dice2'
            ,DB::raw("DATE_FORMAT(result_masters.game_date, '%d-%m-%Y') as draw_date"))
            ->join('draw_masters', 'result_masters.draw_master_id', '=', 'draw_masters.id')
            ->join('dice_combination', 'result_masters.dice_combination_id', '=', 'dice_combination.id')
            ->join('play_series', 'result_masters.play_series_id', '=', 'play_series.id')
            ->whereRaw("game_date=curdate()")
            ->orderByRaw("result_masters.id desc")
            ->limit(1)
            ->first();
        echo json_encode($result,JSON_NUMERIC_CHECK);
    }

    function getLimitedResultByDate(){
        $result = ResultMaster::
        select('result_masters.play_series_id','play_series.series_name', 'play_series.game_initial'
            ,'draw_masters.end_time', 'draw_masters.serial_number','dice_combination.dice1','dice_combination.dice2'
            ,DB::raw("DATE_FORMAT(result_masters.game_date, '%d-%m-%Y') as draw_date"))
            ->join('dice_combination', 'result_masters.dice_combination_id', '=', 'dice_combination.id')
            ->join('draw_masters', 'result_masters.draw_master_id', '=', 'draw_masters.id')
            ->join('play_series', 'result_masters.play_series_id', '=', 'play_series.id')
            ->whereRaw('result_masters.game_date=curdate()')
//            ->orderBy('result_masters.draw_master_id','desc')
            ->orderBy('result_masters.id','desc')
            ->limit(14)
            ->get();
        echo json_encode($result,JSON_NUMERIC_CHECK);
    }


    function getResultsByDate(request $request){
        $requestedData = (object)($request->json()->all());
        $gameDate = $requestedData->gameDate;
        $result = DrawMaster::
        select('result_masters.play_series_id','play_series.series_name', 'play_series.game_initial'
            ,'draw_masters.end_time', 'draw_masters.serial_number','dice_combination.dice1','dice_combination.dice2'
            ,DB::raw("DATE_FORMAT(result_masters.game_date, '%d-%m-%Y') as draw_date"))
            ->leftJoin(
                DB::raw("(select * from result_masters where date(created_at)='$gameDate') `result_masters`"),
                'result_masters.draw_master_id', '=', 'draw_masters.id')
            ->leftJoin('play_series', 'result_masters.play_series_id', '=', 'play_series.id')
            ->leftJoin('dice_combination', 'result_masters.dice_combination_id', '=', 'dice_combination.id')
            ->orderByRaw("draw_masters.id")
            ->get();
        echo json_encode($result,JSON_NUMERIC_CHECK);
    }
}
