<?php

namespace App\Http\Controllers;

use App\Model\RechargeToTerminal;
use App\Model\StockistToTerminal;
use App\Model\Stockist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class RechargeToTerminalController extends Controller
{
    public function saveTerminalRechargeData(request $request){
        $requestedData = (object)($request->json()->all());
        $rechargeToTerminalObj = new RechargeToTerminal();
        $stockist_id = $requestedData->stockist_id;
        $amount = $requestedData->amount;
        $terminal_id = $requestedData->terminal_id;
        $recharge_master_id = $requestedData->recharge_master_id;
        $recharge_master_cat_id = $requestedData->recharge_master_cat_id;

        try
        {
            $rechargeToTerminalObj->amount = $amount;
            $rechargeToTerminalObj->recharge_master_id = $recharge_master_id;
            $rechargeToTerminalObj->terminal_id = $terminal_id;
            $rechargeToTerminalObj->recharge_master_cat_id = $recharge_master_cat_id;
            $rechargeToTerminalObj->save();

            StockistToTerminal::where('terminal_id',$terminal_id)
            ->update(array(
                'current_balance' => DB::raw( 'current_balance +'.$amount)
            ) );

            $terminalData = StockistToTerminal::where('terminal_id',$terminal_id)->first();
            $currentBalance = $terminalData->current_balance;

            Stockist::where('id',$stockist_id)
            ->update(array(
                'current_balance' => DB::raw( 'current_balance -'.$amount)
            ) );
            DB::commit();
        }

        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Successfully recorded', 'current_balance' => $currentBalance),200);
    }

    public function getTerminalTotalSaleReport(request $request){
        $requestedData = (object)($request->json()->all());
        $startDate = $requestedData->start_date;
        $endDate = $requestedData->end_date;
        $reportData = DB::select('call customer_sale_report_from_admin(?,?)',array($startDate,$endDate));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }

    public function getAllBarcodeReportByDate(request $request){
        $requestedData = (object)($request->json()->all());
        $startDate = $requestedData->start_date;
        $reportData = DB::select("select *
        ,if(is_claimed=1,'Yes','No') as claimed
        from (select max(user_id) as user_id,
                    max(draw_time) as draw_time
                    ,max(ticket_taken_time) as ticket_taken_time
                    ,barcode
                    ,max(play_master_id) as play_master_id
                    ,max(terminal_id) as terminal_id
                    ,max(draw_master_id) as draw_master_id
                    ,sum(game_value) as quantity
                    ,sum(game_value* mrp) as amount
                    ,get_prize_value_of_barcode(barcode) as prize_value
                    ,group_concat(row_num,'-[',game_value,']' order by row_num) as particulars
                    ,max(is_claimed) as is_claimed
                    from (select
                    play_masters.barcode_number as barcode
                    ,play_masters.id as play_master_id
                    , max(play_masters.terminal_id) as terminal_id
                    ,max(people.user_id) as user_id
                    , play_details.play_series_id
                    ,max(play_series.mrp) as mrp
                    , max(play_masters.draw_master_id) as draw_master_id
                    ,max(play_masters.is_claimed) as is_claimed
                    , play_details.input_box as row_num
                    , max(play_details.input_value) as game_value
                    , max(draw_masters.start_time) as start_time
                    , TIME_FORMAT(max(draw_masters.end_time),'%h:%i:%s %p') as draw_time
                    ,TIME_FORMAT(play_masters.created_at, '%h:%i:%s %p') as ticket_taken_time
                    from play_details
                    inner join play_masters ON play_masters.id = play_details.play_master_id
                    inner join draw_masters ON draw_masters.id = play_masters.draw_master_id
                    inner join play_series ON play_series.id = play_details.play_series_id
                    inner join people on people.id = play_masters.terminal_id
                    where date(play_masters.created_at)=?
                    group by play_details.play_master_id,play_details.play_series_id ,play_details.input_box) as table1
                    group by barcode order by draw_master_id desc,ticket_taken_time desc) as table2",[$startDate]);
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }


    public function getBarcodeInputDetails(request $request){
        $requestedData = (object)($request->json()->all());
        $barcode = $requestedData->barcode;
        $reportData = DB::select("select group_concat(series_name,'= ',input_value,', ' order by series_name) as particulars from
        (select play_masters.barcode_number, play_series.series_name, play_series.game_initial ,play_details.input_value from play_masters
        inner join play_details on play_details.play_master_id = play_masters.id
        inner join play_series ON play_series.id = play_details.play_series_id
        where play_masters.barcode_number='$barcode') as table1 group by barcode_number",[$barcode]);
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }


    public function drawWiseReport(request $request){
        $requestedData = (object)($request->json()->all());
        $gameDate = $requestedData->start_date;
        $reportData = DB::select('call draw_wise_report(?)',array($gameDate));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }

    public function terminalReportDetails(request $request){
        $requestedData = (object)($request->json()->all());
        $start_date = $requestedData->start_date;
        $end_date = $requestedData->end_date;
        $terminal_id = $requestedData->terminal_id;
        $reportData = DB::select('call fetch_terminal_digit_total_sale(?,?,?)',array($terminal_id,$start_date,$end_date));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }


    public function barcodeReportFromTerminal(request $request){
        $requestedData = (object)($request->json()->all());
        $terminalId = $requestedData->terminalId;
        $startDate = $requestedData->startDate;

        $reportData = DB::select('call digit_barcode_report_from_terminal(?,?)',array($terminalId,$startDate));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }

    public function getTotalBoxInput(request $request){
        $requestedData = (object)($request->json()->all());
        $drawId = $requestedData->draw_id;
        $reportData = DB::select('call  game_wise_total_input(?)',array($drawId));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }
    public function getPointWiseBarcodeReport(request $request){
        $requestedData = (object)($request->json()->all());
        $terminalId = $requestedData->terminalId;
        $startDate = $requestedData->startDate;
        $endDate = $requestedData->endDate;

        $reportData = DB::select('call barcode_report_total_point_wise(?,?,?)',array($terminalId,$startDate,$endDate));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }

    public function getTransactionReportFromTerminal(request $request){
        $requestedData = (object)($request->json()->all());
        $terminalId = $requestedData->terminalId;
        $startDate = $requestedData->startDate;
        $endDate = $requestedData->endDate;

        $reportData = DB::select('call transaction_report_by_terminal(?,?,?)',array($terminalId,$startDate,$endDate));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }

    public function terminalTicketSaleReportFromCpanel(request $request){
        $requestedData = (object)($request->json()->all());
        $startDate = $requestedData->start_date;
        $endDate = $requestedData->end_date;
        $stockistId = $requestedData->stockistId;
        $reportData = DB::select('call terminal_summary_wise_report_for_admin(?,?,?)',array($startDate,$endDate,$stockistId));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }

    public function getSaleReportByTerminalId(request $request){
        $requestedData = (object)($request->json()->all());
        $startDate = $requestedData->start_date;
        $endDate = $requestedData->end_date;
        $terminalId = $requestedData->terminalId;

        $reportData = DB::select('call terminal_sale_report(?,?,?)',array($startDate,$endDate,$terminalId));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }
    public function terminalDateWiseReportFromCPanel(request $request){
        $requestedData = (object)($request->json()->all());
        $startDate = $requestedData->start_date;
        $endDate = $requestedData->end_date;
        $stockistId = $requestedData->stockistId;
        $reportData = DB::select('call terminal_date_wise_report_from_cpanel(?,?,?)',array($startDate,$endDate,$stockistId));
        echo json_encode($reportData,JSON_NUMERIC_CHECK);
    }
    public function updateTerminalCurrentPoint(request $request){
        $requestedData = (object)($request->json()->all());
        $terminalId = $requestedData->terminalId;
        $terminalCurrentBalance = 0;
        $terminalCurrentBalance = collect(DB::select('select update_current_point_of_terminal(?) as current_point',array($terminalId)))
            ->first()->current_point;
        $terminalInfo = StockistToTerminal::select('current_balance')->where('terminal_id',$terminalId)->first();
        if(!empty($terminalInfo)){
            if($terminalInfo->current_balance != $terminalCurrentBalance){
                StockistToTerminal::where('terminal_id',$terminalId)->update(['current_balance'=> $terminalCurrentBalance]);
            }else{
                return response()->json(['point'=> 0], 200);
            }
        }
        return response()->json(['point'=> $terminalCurrentBalance], 200);
    }
}
