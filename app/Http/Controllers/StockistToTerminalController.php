<?php

namespace App\Http\Controllers;

use App\Model\StockistToTerminal;
use App\Model\MaxTable;
use App\Model\Person;
use App\Model\Stockist;
use Illuminate\Http\Request;
use App\Http\Controllers\CentralFunctionController;
use Illuminate\Support\Facades\DB;
use Exception;
use Webpatser\Uuid\Uuid;

class StockistToTerminalController extends Controller
{
   public function getTerminalBalance($id){
        $StockistToTerminal=Person::find($id)->StockistToTerminal;
        return json_encode($StockistToTerminal);
   }

    public function getAllTerminals(){
      $allTerminals = StockistToTerminal::
                    select('stockist_to_terminals.terminal_id','stockist_to_terminals.stockist_id','stockists.stockist_name',
                  'stockists.user_id as stockist_user_id','people.people_name','people.user_id','people.user_password','people.default_password',
                  'stockist_to_terminals.current_balance as terminal_current_balance',
                  'stockists.current_balance as stockist_current_balance',
                  'stockist_to_terminals.commission as terminal_commission','stockists.commission as stockist_commission')
                    ->join('stockists', 'stockist_to_terminals.stockist_id', '=', 'stockists.id')
                    ->join('people', 'stockist_to_terminals.terminal_id', '=', 'people.id')
                    ->where('stockist_to_terminals.inforce','=',1)
                    ->where('stockists.inforce','=',1)
                    ->where('stockist_to_terminals.inforce','=',1)
                    ->get();
        echo json_encode($allTerminals);
    }

    public function selectNextTerminalId(request $request){
//        $requestedData = (object)($request->json()->all());
//        $serialnumber = $requestedData->serialNo;
//        $stockistId = $requestedData->stockistId;
//        $stockist = (DB::select("select count(*) as is_exist from max_terminals where stockist_id=?",[$stockistId]));
//
//        if($stockist[0]->is_exist > 0){
//            $terminalCurrentValue = DB::select("select (current_value+1) as current_value from max_terminals where stockist_id=?",[$stockistId]);
//            $currentValue = $terminalCurrentValue[0]->current_value;
//        }else{
//            $currentValue = 1;
//        }
//        $terminalUserId = 'T'.$serialnumber.'-'.str_pad($currentValue,4,"0",STR_PAD_LEFT);
        $nextTerminalId = MaxTable::where('person_category_id',3)->first();
        if(!empty($nextTerminalId)){
            $terminalUserId=$nextTerminalId->current_value+1;
        }else{
            $terminalUserId=6106001;
        }
        echo json_encode($terminalUserId,JSON_NUMERIC_CHECK);
    }


    public function saveNewTerminal(request $request){
        $requestedData = (object)($request->json()->all());
        $objCentralFunctionCtrl = new CentralFunctionController();
        $serialnumber = $requestedData->stockist_sl_no;
        $stockist_id = $requestedData->stockist_id;
        $financial_year = $objCentralFunctionCtrl->get_financial_year();
        DB::beginTransaction();
        try
        {
            DB::insert("insert into max_tables (subject_name,person_category_id, current_value, financial_year,prefix)
            values('terminal',3,6106001,?,'T')
            on duplicate key UPDATE id=last_insert_id(id), current_value=current_value+1", [$financial_year]);
            $lastInsertId = DB::getPdo()->lastInsertId();
            $max_table_data = MaxTable::where('id',$lastInsertId)->first();
            $terminalUniqueId = $max_table_data->current_value;
//            $terminalUniqueId = 'T'.$serialnumber.'-'.str_pad($currentValue,4,"0",STR_PAD_LEFT);

            $terminalObj = new Person();
            $terminalObj->people_unique_id = $terminalUniqueId;
            $terminalObj->people_name = $requestedData->terminal['people_name'];
            $terminalObj->user_id = $terminalUniqueId;
            $terminalObj->user_password = $requestedData->terminal['user_password'];
            $terminalObj->default_password = $requestedData->terminal['user_password'];
            $terminalObj->person_category_id = 3;
            $terminalObj->save();
            $lastInsertedTerminalId = DB::getPdo()->lastInsertId();

            DB::insert("insert into max_terminals (stockist_id,current_value,financial_year) VALUES (?,1,?)
            on duplicate key UPDATE id=last_insert_id(id), current_value=current_value+1", [$stockist_id,$financial_year]);

            $StockistToTerminalObj = new StockistToTerminal();
            $StockistToTerminalObj->stockist_id = $stockist_id;
            $StockistToTerminalObj->terminal_id = $lastInsertedTerminalId;
            $StockistToTerminalObj->commission = $requestedData->terminal['commission'];
            $StockistToTerminalObj->save();
            DB::commit();
        }

        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine(),),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Successfully recorded','stockist_id' => $stockist_id, 'terminal_id' => $lastInsertedTerminalId,'user_id' => $requestedData->terminal['user_id'],),200);
    }


    public function updateTerminalDetails(request $request){
        $requestedData = (object)($request->json()->all());

        $id = $requestedData->terminal['terminal_id'];
        $stockist_id = $requestedData->terminal['stockist']['id'];
        $terminal_name = $requestedData->terminal['people_name'];
        $user_id = $requestedData->terminal['user_id'];
        $user_password = $requestedData->terminal['user_password'];
        $commission = $requestedData->terminal['commission'];
        try
        {
            $terminalObj = new Person();
            Person::where('id','=',$id)->update(['people_name'=> $terminal_name,'user_password'=> $user_password,'default_password'=>$user_password]);
            StockistToTerminal::where('terminal_id','=',$id)->update(['stockist_id'=> $stockist_id,'commission'=>$commission]);
            DB::commit();
        }

        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Successfully recorded', 'stockist_id' => $id,'user_id' => $user_id),200);
    }
    public function resetPasswordByTerminal(request $request){
        $requestedData = (object)($request->json()->all());
        $id = $requestedData->terminalId;
        $user_password = $requestedData->userPassword;
        try
        {
            $terminalObj = new Person();
            Person::where('id','=',$id)->update(['user_password'=> $user_password]);
            DB::commit();
        }
        catch (Exception $e)
        {
            DB::rollBack();
            return response()->json(array('success' => 0, 'message' => $e->getMessage().'<br>File:-'.$e->getFile().'<br>Line:-'.$e->getLine()),401);
        }
        return response()->json(array('success' => 1, 'message' => 'Password reset successfully'),200);
    }
}
