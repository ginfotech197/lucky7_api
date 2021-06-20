<?php

namespace App\Http\Controllers;

use App\Model\RechargeToStockist;
use App\Model\Stockist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

class RechargeToStockistController extends Controller
{
    public function saveStockistRechargeData(request $request){
        $requestedData = (object)($request->json()->all());
        $rechargeToStockistObj = new RechargeToStockist();
        $stockist_id = $requestedData->stockist_id;
        $amount = $requestedData->amount;
        $recharge_master_id = $requestedData->recharge_master_id;

        try
        {
            $rechargeToStockistObj->amount = $amount;
            $rechargeToStockistObj->stockist_id = $stockist_id;
            $rechargeToStockistObj->recharge_master = $recharge_master_id;
            $rechargeToStockistObj->save();

            Stockist::where('id',$stockist_id)
            ->update(array(
                'current_balance' => DB::raw( 'current_balance +'.$amount)
            ) );

//            Stockist::where('id',1)
//                ->update(array(
//                    'current_balance' => DB::raw( 'current_balance -'.$amount)
//                ) );

            $stockistData = Stockist::where('id',$stockist_id)->first();
            $currentBalance = $stockistData->current_balance;
            DB::commit();
        }

        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Successfully recorded', 'current_balance' => $currentBalance),200);
    }

    public function rechargeToStockiestDetailing(request $request){
        $requestedData = (object)($request->json()->all());
        $master_id = $requestedData->master_id;

        $data = DB::select("select people.people_name, stockists.stockist_name, stockists.stockist_unique_id, recharge_to_stockists.created_at
            , abs(if(recharge_to_stockists.amount<0,recharge_to_stockists.amount,0)) as credit
            , if(recharge_to_stockists.amount>=0,recharge_to_stockists.amount,0) as debit from recharge_to_stockists
            inner join people ON people.id = recharge_to_stockists.recharge_master
            inner join stockists on stockists.id = recharge_to_stockists.stockist_id
            where recharge_to_stockists.recharge_master= '$master_id'
            order by recharge_to_stockists.created_at",[$master_id]);
        return response()->json(array('success' => 1, 'data' => $data),200);
    }
}
