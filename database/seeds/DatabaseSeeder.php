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
        Person::create(['people_unique_id'=>'C-001-ad','people_name'=>'Sachin Tendulkar','person_category_id'=>1,'user_id'=>'coder','user_password'=>'12345']);
        Person::create(['people_unique_id'=>'T-0001-1920','people_name'=>'test terminal','person_category_id'=>3,'user_id'=>'terminal','user_password'=>'12345']);

        // game
        Game::create(['game_name'=>'2D']);

        // play series
        PlaySeries::create(['series_name'=>'AP Gold','game_initial' => 'AP' ,'mrp'=> 11, 'winning_price'=>100, 'commision'=>5, 'payout'=>150,'default_payout'=>150]);
        PlaySeries::create(['series_name'=>'GOA Jackpot','game_initial' => 'JP', 'mrp'=> 11, 'winning_price'=>100, 'commision'=>5, 'payout'=>150,'default_payout'=>150]);
        PlaySeries::create(['series_name'=>'AP Gold','game_initial' => '7S', 'mrp'=> 11, 'winning_price'=>100, 'commision'=>5, 'payout'=>150,'default_payout'=>150]);

        // stockist
        Stockist::create(['stockist_unique_id'=>'ST-0001','stockist_name' => 'test stockist' ,'login_id'=> 1001, 'login_password'=>12345, 'serial_number'=>1, 'current_balance'=>1000,'person_category_id'=>4]);


        // stockist_to_terminal
        StockistToTerminal::create(['stockist_id'=>1,'terminal_id' => 2 ,'current_balance'=> 100, 'inforce'=>1]);

    }
}
