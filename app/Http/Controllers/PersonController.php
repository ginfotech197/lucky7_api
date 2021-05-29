<?php

namespace App\Http\Controllers;

use App\Model\Person;
use App\Model\StockistToTerminal;
use App\Model\Stockist;
use Illuminate\Http\Request;
use Webpatser\Uuid\Uuid;
use \Carbon\Carbon;

class PersonController extends Controller
{

    public function validateCredential(request $request){
        $user_data=(object)($request->json()->all());
        $user_id = $user_data->userId;
        $password = $user_data->password;
        $clientIP = request()->ip();
        $stockist =NULL;
        $StockistToTerminal = NULL;
        $arrResponse =array();

        //updating data
        $person=Person::where(['user_id'=> $user_id,'user_password'=>$password])->first();

        if($person==NULL){
            $stockist = Stockist::select("id","stockist_unique_id","stockist_name as people_name","user_id","user_id","serial_number"
            ,"current_balance","person_category_id","is_loggedin","inforce","uuid","created_at","updated_at")
                ->where(['user_id'=> $user_id,'user_password'=>$password])->first();
        }

        if($person==NULL && $stockist==NULL){
            $arrResponse = ['success'=>false,'isLoggedIn'=>false,'person_name'=>'','uuid'=>'','msg'=>'Wrong credentials'];
        }

        //$person->ip_address = $clientIP;

        if ((!empty($person))){
            $person->uuid=(string)Uuid::generate();
            $person->is_loggedin = 1;
            $result=$person->save();
            $StockistToTerminal=Person::find($person->id)->StockistToTerminal->first();
            $arrResponse = ['success'=>$result,'isLoggedIn'=>$result,'person'=>$person,'StockistToTerminal'=>$StockistToTerminal,'msg'=>'Login Successful'];
        }

        if (!empty($stockist)){
            $stockist->uuid=(string)Uuid::generate();
            $stockist->is_loggedin = 1;
            $result=$stockist->save();
            $arrResponse = ['success'=>$result,'isLoggedIn'=>$result,'person'=>$stockist,'StockistToTerminal'=>'','msg'=>'Login Successful'];
        }

        return response()->json($arrResponse,200);
    }

    public function logOutUser(request $request){
        $user_data=(object)($request->json()->all());

        $uid = $user_data->uid;
        $userCategoryId = $user_data->userCategoryId;
        //updating data
        if($userCategoryId == 1 || $userCategoryId == 3){
            $person=Person::where(['id'=> $uid])->update(['is_loggedin'=>0]);
        }else if($userCategoryId == 4){
            $person=Stockist::where(['id'=> $uid])->update(['is_loggedin'=>0]);
        }

        return $person;
    }

    public function getCurrentTimestamp(){

        $current_date_time = \Carbon\Carbon::now()->toDateTimeString();
        $time = Carbon::now()->format('H:i:s');
        $timeInMilliSeconds = round(microtime(true)*1000);
        return json_encode(array('dateTime' => $current_date_time,'time' => $time,'timeInMilliSeconds' => $timeInMilliSeconds));
    }
    public function getLoggedInTerminalBalance(request $request){
        $requestedData = (object)($request->json()->all());
        $terminalId = $requestedData->terminal_id;
        $StockistToTerminal = StockistToTerminal:: select('terminal_id','stockist_id','current_balance')->where('terminal_id',$terminalId)->first();
        return $StockistToTerminal;
    }

    public function checkUnauthorizedAction($bearerToken=null){
        $person=Person::where(['uuid'=> $bearerToken])->first();

        if($person==NULL){
            return 0;
        }
        if($person->is_loggedin == 1){
            return 1;
        }
    }


    public function resetAdminPassword(request $request){
        $user_data=(object)($request->json()->all());

        $userId = $user_data->userId;
        $userPsw = $user_data->psw;

        $updateInfo = Person::where('id',$userId)->update(['user_password'=>$userPsw]);

        if($updateInfo==1){
            return response()->json(array('success' => 1, 'message' => 'Successfully recorded'),200);
        }else{
            return response()->json(array('success' => 0, 'message' => 'Something went wrong'),401);
        }

    }


    public function checkAuthenticatedUser(request $request){
        $user_data=(object)($request->json()->all());
        $personId = $user_data->personId;
        $uuid = $user_data->uuid;

        $userInfo = Person::where(['id'=>$personId,'uuid'=>$uuid,'is_loggedin'=>1])->first();
        if(!empty($userInfo)){
            return response()->json(array('success' => 1, 'message' => "user exists"),200);
        }else{
            return response()->json(array('success' => 0, 'message' => "user doesn't exist"),401);
        }

    }
}
