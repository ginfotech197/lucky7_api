<?php

namespace App\Http\Controllers;

use App\Model\ManualResultDigit;
use App\Model\PlaySeries;
use App\Model\RechargeToTerminal;
use App\Model\Stockist;
use App\Model\StockistToTerminal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Exception;

class ManualResultDigitController extends Controller
{
    public function getDrawTimeForManualResult(){
        $drawTime = DB::select(DB::raw("select * from draw_masters where id not in
        (select draw_master_id from result_masters where date(created_at)=(curdate())) AND id not in
        (select draw_master_id from manual_result_digits where date(created_at)=curdate()) order by serial_number"));
        echo json_encode($drawTime,JSON_NUMERIC_CHECK);
    }

    public function saveManualResult(request $request){
        $requestedData = (object)($request->json()->all());
        $drawMasterId = $requestedData->master['draw_master_id'];
        $resultSeriesId = $requestedData->master['result_series_id'];
        $gameDate = $currentDate = Carbon::now()->format('Y-m-d');
//        echo $drawMasterId.'=='.$resultSeriesId.'//'.$gameDate ;exit;
        try
        {
            $manualResultDigit = new ManualResultDigit();
            $manualResultDigit->play_series_id = $resultSeriesId;
            $manualResultDigit->draw_master_id = $drawMasterId;
            $manualResultDigit->game_date = $gameDate;
            $manualResultDigit->save();
            DB::commit();
        }

        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Successfully recorded'),200);
    }

    public function getLastInsertedManualResult(){
        $manualResults = ManualResultDigit::select('manual_result_digits.play_series_id','manual_result_digits.draw_master_id','series_name','end_time','result')
                    ->join('play_series', 'manual_result_digits.play_series_id', '=', 'play_series.id')
                    ->join('draw_masters', 'manual_result_digits.draw_master_id', '=', 'draw_masters.id')
                    ->where('draw_masters.active',1)
                    ->whereRaw("manual_result_digits.game_date = curdate()")
                    ->get();
        echo json_encode($manualResults,JSON_NUMERIC_CHECK);
    }

    public function updateCurrentManual(request $request){
        $requestedData = (object)($request->json()->all());
        $drawMasterId = $requestedData->master['draw_master_id'];
        $gameDate = $currentDate = Carbon::now()->format('Y-m-d');

        try
        {

            if($requestedData->master['series_one'] != -1){
                ManualResultDigit::where('draw_master_id',$drawMasterId)
                                ->where('play_series_id',1)
                                ->whereRaw("manual_result_digits.game_date = curdate()")
                                ->update(["result" => $requestedData->master['series_one']]);
            }
            if($requestedData->master['series_two'] != -1){
                ManualResultDigit::where('draw_master_id',$drawMasterId)
                                ->where('play_series_id',2)
                                ->whereRaw("manual_result_digits.game_date = curdate()")
                                ->update(["result" => $requestedData->master['series_two']]);
            }
            if($requestedData->master['series_three'] != -1){
                ManualResultDigit::where('draw_master_id',$drawMasterId)
                                ->where('play_series_id',3)
                                ->whereRaw("manual_result_digits.game_date = curdate()")
                                ->update(["result" => $requestedData->master['series_three']]);
            }
            DB::commit();
        }

        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Successfully updated'),200);
    }

    public function getTotalSalePerDraw(request $request){
        $requestedData = (object)($request->json()->all());
        $drawMasterId = $requestedData->master['draw_master_id'];
        $gameDate = $currentDate = Carbon::now()->format('Y-m-d');
        $drawTime = DB::select(DB::raw("select play_series_id,series_name,sum(total_sale) as total,sum(winning) as total_winning from
        (select play_masters.barcode_number,play_details.play_series_id,play_series.series_name,play_series.mrp,play_details.input_value,
        (play_series.mrp*play_details.input_value)as total_sale,(play_series.winning_price*play_details.input_value)as winning
         from play_masters
        inner join play_details on play_masters.id=play_details.play_master_id
        inner join play_series on play_details.play_series_id=play_series.id
        where date(play_masters.created_at)='$gameDate' and play_masters.draw_master_id=$drawMasterId order by play_masters.barcode_number)
         as table1 group by play_series_id,series_name"));
        echo json_encode($drawTime,JSON_NUMERIC_CHECK);
    }
}
