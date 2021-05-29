<?php

namespace App\Http\Controllers;

use App\Model\GameMessage;
use App\Model\RechargeToStockist;
use App\Model\Stockist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GameMessageController extends Controller
{
    public function getSiteNotification(){
        $message = GameMessage::orderBy('id', 'DESC')->take(1)->first();
        echo json_encode($message);
    }


    public function addNewMessage(request $request){
        $requestedData = (object)($request->json()->all());
        $message = $requestedData->msg;

        try
        {
            $gameMessageObj = new GameMessage();
            $currentMessage = $gameMessageObj::first();

            if(!empty($currentMessage)){
                $lastId = $currentMessage->id;
                $gameMessageObj::where('id',$lastId)->update(['message'=>$message]);
            }else{
                $gameMessageObj->message = $message;
                $gameMessageObj->save();
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
}
