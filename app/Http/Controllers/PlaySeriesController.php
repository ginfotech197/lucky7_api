<?php

namespace App\Http\Controllers;

use App\Model\DrawMaster;
use App\Model\PlaySeries;
use App\Model\Stockist;
use App\Model\StockistToTerminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class PlaySeriesController extends Controller
{

    public function getPlaySeries(){
        $allPlaySeries = PlaySeries::all();
        echo json_encode($allPlaySeries,JSON_NUMERIC_CHECK);

//        $drawTime = DrawMaster::select()->where('active',1)->first();

//        $allPlaySeries = DB::select(DB::raw("select play_series.id, play_series.series_name, play_series.game_initial, play_series.mrp
//            ,(sum(if(play_details.play_series_id=1, play_details.input_value*2
//            ,if(play_details.play_series_id=2, play_details.input_value*5,
//            if(play_details.play_series_id=3, play_details.input_value*2,0)))) )as value from play_masters
//            inner join play_details on play_details.play_master_id = play_masters.id
//            inner join play_series on play_series.id = play_details.play_series_id
//            where time(play_masters.created_at)>= '19:15:00' and time(play_masters.created_at) <= '19:30:00'
//            group by play_series.id, play_series.series_name, play_series.game_initial, play_series.mrp"));

//        $allPlaySeries = DB::select("select play_series.id, play_series.series_name, play_series.game_initial, play_series.mrp
//            ,(sum(if(play_details.play_series_id=1, play_details.input_value*2
//            ,if(play_details.play_series_id=2, play_details.input_value*5,
//            if(play_details.play_series_id=3, play_details.input_value*2,0)))) )as value from play_masters
//            inner join play_details on play_details.play_master_id = play_masters.id
//            inner join play_series on play_series.id = play_details.play_series_id
//            where time(play_masters.created_at)>= '19:15:00' and time(play_masters.created_at) <= '19:30:00'
//            group by play_series.id, play_series.series_name, play_series.game_initial, play_series.mrp",[$drawTime->start_time, $drawTime->end_time]);

//        return response()->json(array('success' => 1, 'playSeries' => $allPlaySeries),200);
    }

    public function getPlaySeriesWithLoad(){
        $drawTime = DrawMaster::select()->where('active',1)->first();
        $allPlaySeries = DB::select("select play_series.id, play_series.series_name, play_series.game_initial, play_series.mrp
            ,(sum(if(play_details.play_series_id=1, play_details.input_value*2
            ,if(play_details.play_series_id=2, play_details.input_value*5,
            if(play_details.play_series_id=3, play_details.input_value*2,0)))) )as value from play_masters
            inner join play_details on play_details.play_master_id = play_masters.id
            inner join play_series on play_series.id = play_details.play_series_id
            where time(play_masters.created_at)>= '19:15:00' and time(play_masters.created_at) <= '19:30:00'
            group by play_series.id, play_series.series_name, play_series.game_initial, play_series.mrp",[$drawTime->start_time, $drawTime->end_time]);

        return response()->json(array('success' => 1, 'playSeries' => $allPlaySeries),200);
    }

    public function setGamePayout(request $request){
        try
        {
            $requestedData = (object)($request->json()->all());
            $payoutValue= $requestedData->payoutValue;
            $resultPayout = DB::table('result_payout')->first();
            if(empty($resultPayout)){
                DB::table('result_payout')->insert(['payout_status'=>$payoutValue]);
            }else{
                DB::table('result_payout')->where('id',$resultPayout->id)->update(['payout_status'=>$payoutValue]);
            }
            DB::commit();
        }

        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Successfully recorded'),200);
    }

    public function getGamePayout(){

        $resultPayout = DB::table('result_payout')->first();
        echo json_encode($resultPayout,JSON_NUMERIC_CHECK);
    }

}
