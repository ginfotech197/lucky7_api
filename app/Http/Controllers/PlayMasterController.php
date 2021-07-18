<?php

namespace App\Http\Controllers;

use App\Model\DrawMaster;
use App\Model\PlayMaster;
use App\Model\BarcodeMax;
use App\Model\PlayDetails;
use App\Model\StockistToTerminal;
use App\Model\ClaimDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\CentralFunctionController;
use Illuminate\Support\Carbon;
use Exception;

class PlayMasterController extends Controller
{


    public function saveGameInputDetails(request $request){
        $allRequestedData=(object)($request->json()->all());
        $objCentralFunctionCtrl = new CentralFunctionController();
        $financial_year = $objCentralFunctionCtrl->get_financial_year();
        DB::beginTransaction();

            try
            {
//                DB::insert("insert into barcode_maxes (subject_name, current_value, prefix, financial_year)
//                values('digit bill',1,'LS',?)
//                on duplicate key UPDATE id=last_insert_id(id), current_value=current_value+1", [$financial_year]);
//                $lastInsertId = DB::getPdo()->lastInsertId();
//                $lastGeneratedBarcode = BarcodeMax::where('id', $lastInsertId)->first();
//
//                $bcd = str_pad($lastGeneratedBarcode->current_value,10,"0",STR_PAD_LEFT).''.$financial_year;
                $currentDate = Carbon::now()->format('d/m/Y');
                $currentTime = Carbon::now()->format('H:i:s');
                $barcodeGenerate = DB::select(DB::raw("select LPAD(FLOOR(RAND() * 99999999.999), 8, '0') as code"));
                $barcode = 'L7'.$barcodeGenerate[0]->code;
                $dataCheck = PlayMaster::select()->where('barcode_number', '=', $barcode)->where('created_at', '=', $currentDate)->first();
                if($dataCheck){
                    $barcodeGenerate = DB::select(DB::raw("select LPAD(FLOOR(RAND() * 99999999.999), 8, '0') as code"));
                    $barcode = 'L7'.$barcodeGenerate[0]->code;
                }


//                $currentDate = Carbon::now()->format('d/m/Y');
//                $currentTime = Carbon::now()->format('H:i:s');
                $terminalId = $allRequestedData->userId;
//                $prefix = $lastGeneratedBarcode->prefix;
//                $barcode = $prefix.''.$bcd;
                $terminalCommission = 0;
                $stockistCommission = 0;
                $getCommission = StockistToTerminal::select('stockist_to_terminals.commission as terminal_commission','stockists.commission as stockist_commission')
                    ->join('stockists','stockists.id','stockist_to_terminals.stockist_id')
                    ->where('terminal_id', $terminalId)->first();
                if(!empty($getCommission)){
                    $terminalCommission=$getCommission->terminal_commission;
                    $stockistCommission=$getCommission->stockist_commission;
                }
                $playMaster = new PlayMaster();
                $playMaster->barcode_number = $barcode;
                $playMaster->terminal_id = $terminalId;
                $playMaster->draw_master_id = $allRequestedData->drawId;
                $playMaster->terminal_commission = $terminalCommission;
                $playMaster->stockist_commission = $stockistCommission;
                $playMaster->save();
                $lastInsertedPlayMasterId = $playMaster->id;
                $inputDetails = $allRequestedData->playDetails;
                $playDetails = new PlayDetails();
                foreach($inputDetails as $key => $row){
                    $inputDetails[$key]['play_master_id'] = $lastInsertedPlayMasterId;
                }

                $playDetails->insert($inputDetails);

                StockistToTerminal::where('terminal_id', $terminalId)
                ->update(array(
                    'current_balance' => DB::raw( 'current_balance -'.$allRequestedData->purchasedTicket)
                ) );

//                $testData = StockistToTerminal::find('terminal_id', $terminalId);
//                $testData->current_balance = $testData->current_balance - $allRequestedData->purchasedTicket;
//                $testData->save();

                $currentBalance = StockistToTerminal::select('current_balance')->where('terminal_id', $terminalId)->first();
                DB::commit();
            }

            catch (Exception $e)
            {
                DB::rollBack();
                return response()->json(array('success' => 0, 'msg' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
            }

            return response()->json(['success'=> 1,'barcode'=>$barcode, 'purchase_date' => $currentDate, 'purchase_time' => $currentTime,'current_balance'=> $currentBalance->current_balance], 200);
    }

    public function getTotalAmountSingleDraw(request $request){
        $requestedData = (object)($request->json()->all());
        $terminal_id = $requestedData->terminalId;
        $activeDraw = DrawMaster::select()->where('active',1)->first();

//        $data =DB::select("select if(play_details.input_value,convert(sum(play_details.input_value),UNSIGNED),0) as total_input, play_masters.terminal_id, play_masters.draw_master_id from play_details
//            inner join play_masters ON play_masters.id = play_details.play_master_id
//            inner join people on people.id = play_masters.terminal_id
//            where play_masters.terminal_id =?  and play_masters.draw_master_id = ?
//            group by play_masters.terminal_id,play_masters.draw_master_id,play_details.input_value", array($terminal_id, $activeDraw->id));

        $data = DB::select("select sum(input_value) as total_input from (select play_details.input_value, play_masters.terminal_id, play_masters.draw_master_id from play_details
            inner join play_masters ON play_masters.id = play_details.play_master_id
            inner join people on people.id = play_masters.terminal_id
            where play_masters.terminal_id =?  and play_masters.draw_master_id = ?
            group by play_masters.terminal_id,play_masters.draw_master_id,play_details.input_value) as table1", array($terminal_id, $activeDraw->id));

        if($data){
            $result = $data[0];
        }else{
            $data['total_input'] = 0;
            $data['terminal_id'] = $terminal_id;
            $data['draw_master_id'] = $activeDraw->id;
            $result = $data;
        }

        return response()->json(['success'=> 1,'data'=>$result], 200);
    }


    public function claimBarcodeManually(request $request){
        $requestedData = (object)($request->json()->all());
        $terminalId = $requestedData->terminalId;
        $gameId = $requestedData->gameId;
        $prizeValue = $requestedData->prizeValue;
        $playMasterId = $requestedData->playMasterId;
        DB::beginTransaction();
        try {
            $claimDetailsObj = new ClaimDetails();
            $claimDetailsObj->game_id = $gameId;
            $claimDetailsObj->play_master_id = $playMasterId;
            $claimDetailsObj->terminal_id = $terminalId;
            $claimDetailsObj->prize_value = $prizeValue;
            $claimDetailsObj->save();

            StockistToTerminal::where('terminal_id', $terminalId)
                ->update(array(
                    'current_balance' => DB::raw( 'current_balance +'.$prizeValue)
                ) );

            PlayMaster::where('id',$playMasterId)->update(['is_claimed' =>1]);
            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('msg' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(['success'=> 1,'msg'=>'claimed','is_claimed'=>1], 200);
    }
}
