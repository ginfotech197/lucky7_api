<?php

namespace App\Http\Controllers;

use App\Model\ClaimDetails;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use App\Model\StockistToTerminal;
use App\Model\PlayMaster;
use Exception;

class ClaimDetailsController extends Controller
{
    public function claimPointManually(request $request){
        $returnArray =array();
        $requestedData = (object)($request->json()->all());
        $barcode = $requestedData->barcodeNumber;
        $claimedPoint = $requestedData->point;
        $terminalId = $requestedData->terminalId;
        $barcodeInfo = PlayMaster::select('id','is_claimed')->where('barcode_number',$barcode)->first();
        $claimedStatus=$barcodeInfo->is_claimed;
        try{
            if($claimedStatus==0){
                $updatePoint = StockistToTerminal::where('terminal_id', $terminalId)
                    ->update(array(
                        'current_balance' => DB::raw( 'current_balance +'.$claimedPoint)
                    ) );
                if($updatePoint){
                    $playMasterObj = new PlayMaster();
                    $claimedStatus=$playMasterObj->where('barcode_number', $barcode)->update(['is_claimed'=>1]);
                    $playMasterId = $playMasterObj->select('id')->where('barcode_number',$barcode)->first();
                    $claimDetailsObj = new ClaimDetails();
                    $claimDetailsObj->game_id = 1;
                    $claimDetailsObj->play_master_id = $playMasterId->id;
                    $claimDetailsObj->terminal_id = $terminalId;
                    $claimDetailsObj->prize_value = $claimedPoint;
                    $claimDetailsObj->save();
                }
            }

            $returnArray=['claimed'=>$claimedStatus,'message'=>'Claimed successful'];
        }catch(Exception $e){
            $returnArray=['claimed'=>$claimedStatus,'message'=>'Something went wrong','error_message'=>$e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()];
        }

        return $returnArray;

    }
}
