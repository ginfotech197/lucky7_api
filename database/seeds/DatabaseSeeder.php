<?php

use Illuminate\Database\Seeder;
use App\Model\PersonCategory;
use App\Model\Person;
use App\Model\Game;
use App\Model\PlaySeries;
use App\Model\Stockist;
use App\Model\StockistToTerminal;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
    //    personCategory
        PersonCategory::create(['person_category_name'=>'Admin']);
        PersonCategory::create(['person_category_name'=>'Developer']);
        PersonCategory::create(['person_category_name'=>'Terminal']);
        PersonCategory::create(['person_category_name'=>'Stockist']);


        //people
        Person::create(['id'=>1,'people_unique_id'=>'C-001-ad','people_name'=>'Sachin Tendulkar','person_category_id'=>1,'user_id'=>'coder','user_password'=>'12345','default_password'=>'12345']);
        Person::create(['id'=>2,'people_unique_id'=>'C-002-ad','people_name'=>'Sourav Ganguly','person_category_id'=>1,'user_id'=>'adlucky','user_password'=>'998877','default_password'=>'12345']);

        // game
        Game::create(['game_name'=>'lucky7']);

        // play series
        PlaySeries::create(['series_name'=>'7 DOWN','game_initial' => '7D' ,'mrp'=> 1, 'winning_price'=>2, 'commision'=>0, 'payout'=>500,'default_payout'=>150]);
        PlaySeries::create(['series_name'=>'LUCKY 7','game_initial' => 'L7', 'mrp'=> 1, 'winning_price'=>5, 'commision'=>0, 'payout'=>500,'default_payout'=>150]);
        PlaySeries::create(['series_name'=>'7 UP','game_initial' => '7U', 'mrp'=> 1, 'winning_price'=>2, 'commision'=>0, 'payout'=>500,'default_payout'=>150]);

        // stockist
        Stockist::create(['stockist_unique_id'=>'ST-0001','stockist_name' => 'Main stockist' ,'user_id'=> 'ST0001', 'user_password'=>'ST0001', 'serial_number'=>1, 'current_balance'=>200000,'person_category_id'=>4]);


        // stockist_to_terminal
//        StockistToTerminal::create(['stockist_id'=>1,'terminal_id' => 2 ,'current_balance'=> 100, 'inforce'=>1]);

    }
}
