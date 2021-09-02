<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class CreateAllProceduresAndFunctions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::unprepared('DROP FUNCTION IF EXISTS get_lucky_result;
                CREATE FUNCTION `get_lucky_result`(`draw_id` INT, `draw_date` DATE, `payout` VARCHAR(20)) RETURNS int
                    READS SQL DATA
                    DETERMINISTIC
                BEGIN

                  declare var_result int(11);
                  IF payout=\'low\' THEN
                    SELECT id into var_result FROM play_series WHERE ID NOT IN (
                    select DISTINCT(play_details.play_series_id) from play_masters
                    inner join play_details ON play_details.play_master_id=play_masters.id
                    WHERE date(play_masters.created_at)=draw_date AND play_masters.draw_master_id=draw_id) order by rand() limit 1;
                    IF var_result IS NULL THEN
                        select play_series_id into var_result from
                        (select play_details.play_series_id,play_series.series_name, sum(play_details.input_value) as game_value
                        ,sum(play_details.input_value)*play_series.winning_price as prize_value
                        from play_masters
                        inner join play_details on play_details.play_master_id = play_masters.id
                        inner join play_series ON play_series.id = play_details.play_series_id
                        where play_masters.draw_master_id=draw_id and date(play_masters.created_at)=draw_date
                        group by play_details.play_series_id,play_series.series_name,play_series.winning_price order by prize_value asc,rand() limit 1) as table1;
                    End if;
                  Elseif payout=\'high\'  Then
                  select play_series_id into var_result from
                    (select play_details.play_series_id,play_series.series_name, sum(play_details.input_value) as game_value
                    ,sum(play_details.input_value)*play_series.winning_price as prize_value
                    from play_masters
                    inner join play_details on play_details.play_master_id = play_masters.id
                    inner join play_series ON play_series.id = play_details.play_series_id
                    where play_masters.draw_master_id=draw_id and date(play_masters.created_at)=draw_date
                    group by play_details.play_series_id,play_series.series_name,play_series.winning_price order by prize_value desc,rand() limit 1) as table1;
                  End if;

                IF var_result IS NULL THEN
                    select id into var_result from play_series order by rand() limit 1;
                end if;
                return var_result;
                END;

        ');

        DB::unprepared('DROP FUNCTION IF EXISTS get_lucky_result_bk;
        CREATE FUNCTION `get_lucky_result_bk`(`draw_id` INT, `draw_date` DATE, `payout` VARCHAR(20)) RETURNS int(11)
        READS SQL DATA
                DETERMINISTIC
                BEGIN
                  declare var_result int(11);
                  IF payout=\'low\' THEN
                    select play_series_id into var_result from
                    (select play_details.play_series_id,play_series.series_name, sum(play_details.input_value) as game_value
                    ,sum(play_details.input_value)*play_series.winning_price as prize_value
                    from play_masters
                    inner join play_details on play_details.play_master_id = play_masters.id
                    inner join play_series ON play_series.id = play_details.play_series_id
                    where play_masters.draw_master_id=draw_id and date(play_masters.created_at)=draw_date
                    group by play_details.play_series_id order by prize_value asc,rand() limit 1) as table1;
                  Elseif payout=\'high\'  Then
                  select play_series_id into var_result from
                    (select play_details.play_series_id,play_series.series_name, sum(play_details.input_value) as game_value
                    ,sum(play_details.input_value)*play_series.winning_price as prize_value
                    from play_masters
                    inner join play_details on play_details.play_master_id = play_masters.id
                    inner join play_series ON play_series.id = play_details.play_series_id
                    where play_masters.draw_master_id=draw_id and date(play_masters.created_at)=draw_date
                    group by play_details.play_series_id order by prize_value desc,rand() limit 1) as table1;
                  End if;
                IF var_result IS NULL THEN
                    select id into var_result from play_series order by rand() limit 1;
                end if;
                return var_result;
                END ;
            ');


        DB::unprepared('DROP FUNCTION IF EXISTS get_opening_balance;
        CREATE FUNCTION `get_opening_balance`(`opening_date` DATE, `terminalId` INT) RETURNS int(11)
        READS SQL DATA
        DETERMINISTIC
        BEGIN
            DECLARE total_sale INT;
            DECLARE total_winning INT;
            DECLARE recharged_balance INT;
            DECLARE total_point INT;
         select sum(point) INTO total_sale from (SELECT play_masters.created_at,play_masters.barcode_number,play_masters.draw_master_id,play_details.play_series_id,play_series.mrp,play_details.input_value
        ,(play_series.mrp*play_details.input_value) as point
        FROM `play_masters`
        inner join play_details on play_masters.id=play_details.play_master_id
        inner join play_series ON play_details.play_series_id=play_series.id
        where play_masters.terminal_id=terminalId and date(play_masters.created_at) < opening_date order by play_masters.created_at)as table1;
        SELECT SUM(get_prize_value_of_barcode(barcode_number))into total_winning
        FROM `play_masters`
        where play_masters.terminal_id=terminalId and date(play_masters.created_at)<opening_date;
        SELECT SUM(amount) INTO recharged_balance FROM `recharge_to_terminals`
        where terminal_id=terminalId AND date(created_at)< opening_date;
         IF recharged_balance IS NULL THEN
            SET recharged_balance=0;
         END IF;
          IF total_sale IS NULL THEN
            SET total_sale=0;
         END IF;
          IF total_winning IS NULL THEN
            SET total_winning=0;
         END IF;
        SET total_point = recharged_balance - total_sale + total_winning;
          IF total_point IS NOT NULL THEN
            RETURN total_point;
          ELSE
            RETURN 0;
          END IF;
        END ;

        ');

        DB::unprepared('DROP FUNCTION IF EXISTS get_prize_value_of_barcode;
            CREATE FUNCTION `get_prize_value_of_barcode`(`barcode` VARCHAR(30)) RETURNS double
            READS SQL DATA
            DETERMINISTIC
            BEGIN
              SET @prize_value=0;
              SET @total_prize_value=0;
              SET @target_row=\'\';
              select max(play_masters.draw_master_id),date(max(play_masters.created_at)) into @draw_id, @draw_date
              from play_details
              inner join (select * from play_masters where barcode_number=barcode)as play_masters ON play_masters.id = play_details.play_master_id
              inner join play_series ON play_series.id = play_details.play_series_id;
              select play_series_id into @target_row from result_masters where game_date=@draw_date and draw_master_id=@draw_id;
              select (play_details.input_value * play_series.winning_price) into @prize_value from play_details
              inner join play_series ON play_series.id = play_details.play_series_id
              inner join play_masters ON play_masters.id = play_details.play_master_id
              where barcode_number= barcode AND play_details.play_series_id=@target_row;
                IF @prize_value IS NULL THEN
                    SET @prize_value = 0;
              END IF;
              RETURN @prize_value;
            END ;;
        ');

        DB::unprepared('DROP FUNCTION IF EXISTS get_total_prize_value_by_date;
        CREATE FUNCTION `get_total_prize_value_by_date`(`play_date` DATE, `term_id` bigint(20)) RETURNS double
        READS SQL DATA
        DETERMINISTIC
        BEGIN
            DECLARE total_prize_value DOUBLE;
          select
          sum(get_prize_value_of_barcode(barcode_number)) into total_prize_value
          from play_masters
          where date(created_at)=play_date and terminal_id=term_id and is_claimed=1;
          IF total_prize_value IS NOT NULL THEN
            RETURN total_prize_value;
          ELSE
            RETURN 0;
          END IF;
        END ;;
        ');

        DB::unprepared('DROP FUNCTION IF EXISTS terminal_commission_by_sale_date;
        CREATE FUNCTION `terminal_commission_by_sale_date`(`sale_date` DATE, `terminal_id` bigint(20)) RETURNS double
        READS SQL DATA
        DETERMINISTIC
        BEGIN
            declare total DOUBLE;
          declare com DOUBLE;
          select max(commision) into com from play_series;
          SET total=terminal_total_sale_by_date(sale_date,terminal_id);
          /*end of getting total sale*/
          select total * max(commision)/100 into com from play_series;  /*getting commission*/
          return com;
        END ;;
        ');

        DB::unprepared('DROP FUNCTION IF EXISTS terminal_net_payable_by_sale_date;
        CREATE FUNCTION `terminal_net_payable_by_sale_date`(`sale_date` DATE, `terminal_id` bigint(20)) RETURNS double
        READS SQL DATA
        DETERMINISTIC
        BEGIN
            declare total DOUBLE;
          declare com DOUBLE;
          declare win_amt DOUBLE;
          declare net_payable DOUBLE;
          select terminal_total_sale_by_date(sale_date,terminal_id) into total;
          /*end of getting total sale*/
          select total * max(commision)/100 into com from play_series;  /*getting commission*/
          select get_total_prize_value_by_date(sale_date,terminal_id) into win_amt;    /*getting prize_value by date*/
          select (total-com)-win_amt into net_payable;          /*get net payable to stockist by terminal*/
          return net_payable;
        END ;;
        ');

        DB::unprepared('DROP FUNCTION IF EXISTS terminal_total_sale_by_date;
        CREATE FUNCTION `terminal_total_sale_by_date`(`saleDate` DATE, `termId` BIGINT(20)) RETURNS float
        READS SQL DATA
        DETERMINISTIC
        BEGIN
        declare x float;
        select sum(total_sale) into x from
        (select play_details.play_series_id,sum(play_details.input_value)* play_series.mrp as total_sale
        from play_details inner join
        (select * from play_masters where terminal_id=termId and date(created_at)=saleDate)play_masters
        ON play_masters.id = play_details.play_master_id
        inner join play_series ON play_series.id = play_details.play_series_id
        group by play_details.play_series_id) as table1;
        return x;
        END ;;
        ');

        DB::unprepared('DROP FUNCTION IF EXISTS update_current_point_of_terminal;
            CREATE FUNCTION `update_current_point_of_terminal`(`terminalId` INT(11)) RETURNS int(11)
            READS SQL DATA
            DETERMINISTIC
            BEGIN
                DECLARE total_sale INT;
                DECLARE total_winning INT;
                DECLARE recharged_balance INT;
                DECLARE total_point INT;
             select sum(point) INTO total_sale from (SELECT play_masters.created_at,play_masters.barcode_number,play_masters.draw_master_id,play_details.play_series_id,play_series.mrp,play_details.input_value
            ,(play_series.mrp*play_details.input_value) as point
            FROM `play_masters`
            inner join play_details on play_masters.id=play_details.play_master_id
            inner join play_series ON play_details.play_series_id=play_series.id
            where play_masters.terminal_id=terminalId and date(play_masters.created_at) <= curdate() order by play_masters.created_at)as table1;
            SELECT SUM(get_prize_value_of_barcode(barcode_number))into total_winning
            FROM `play_masters`
            where play_masters.terminal_id=terminalId and date(play_masters.created_at) <= curdate();
            SELECT SUM(amount) INTO recharged_balance FROM `recharge_to_terminals`
            where terminal_id=terminalId AND date(created_at) <= curdate();
             IF recharged_balance IS NULL THEN
                SET recharged_balance=0;
             END IF;
              IF total_sale IS NULL THEN
                SET total_sale=0;
             END IF;
              IF total_winning IS NULL THEN
                SET total_winning=0;
             END IF;
            SET total_point = recharged_balance - total_sale + total_winning;
              IF total_point IS NOT NULL THEN
                RETURN total_point;
              ELSE
                RETURN 0;
              END IF;
            END ;;
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS barcode_report_total_point_wise;
        CREATE PROCEDURE `barcode_report_total_point_wise`(IN `term_id` VARCHAR(100), IN `start_date` DATE, IN `end_date` DATE)
        BEGIN
        select  max(created_at) as ticket_time
        ,barcode_number,draw_master_id,end_time,sum(input_value)*max(mrp) as total,get_prize_value_of_barcode(barcode_number) as prize,is_claimed from (select play_details.play_master_id, play_details.play_series_id, play_details.input_value,
        play_series.mrp,draw_masters.end_time,play_masters.created_at, play_masters.barcode_number, play_masters.is_claimed,
        play_masters.activity_done_by, play_masters.terminal_id, play_masters.draw_master_id from play_details
        INNER join play_masters ON play_masters.id = play_details.play_master_id
        inner join play_series ON play_series.id = play_details.play_series_id
        inner join draw_masters ON draw_masters.id = play_masters.draw_master_id
        where play_masters.terminal_id=term_id AND date(play_masters.created_at) between start_date and end_date) as table1
        group by play_master_id;
        END ;
        ');


        DB::unprepared('DROP PROCEDURE IF EXISTS digit_barcode_report_from_terminal;
            CREATE PROCEDURE `digit_barcode_report_from_terminal`(IN `term_id` VARCHAR(100), IN `start_date` DATE)
            BEGIN
             select
            max(draw_time) as draw_time
            ,max(ticket_taken_time) as ticket_taken_time
            ,barcode_number
            ,max(draw_master_id) as draw_master_id
            ,sum(game_value) as quantity
            ,sum(game_value*mrp) as amount
            ,get_prize_value_of_barcode(barcode_number) as prize_value
            ,\'\' as particulars
            ,max(is_claimed) as is_claimed
            from (select max(play_masters.barcode_number) as barcode_number
            , max(play_masters.terminal_id) as terminal_id
            , max(play_details.play_series_id) as play_series_id
            ,max(play_series.mrp) as mrp
            , max(play_masters.draw_master_id) as draw_master_id
            ,max(play_masters.is_claimed) as is_claimed
            , max(play_details.input_value) as game_value
            , max(draw_masters.start_time) as start_time
            , max(draw_masters.end_time) as draw_time
            ,TIME_FORMAT(convert_tz(play_masters.created_at,@@session.time_zone,\'+05:30\'), \'%h:%i:%s\')
            as ticket_taken_time
            from play_details
            inner join
            (select * from play_masters where terminal_id=term_id and date(created_at)=start_date order by
            time(created_at) desc) play_masters ON play_masters.id = play_details.play_master_id
            inner join draw_masters ON draw_masters.id = play_masters.draw_master_id
            inner join play_series ON play_series.id = play_details.play_series_id
            group by play_details.play_master_id,play_details.play_series_id
            order by time(play_masters.created_at) desc) as table1
            group by barcode_number order by draw_master_id,ticket_taken_time desc;
            END ;;
        ');


        DB::unprepared('DROP PROCEDURE IF EXISTS fetch_terminal_digit_total_sale;
            CREATE PROCEDURE `fetch_terminal_digit_total_sale`(IN `term_id` VARCHAR(100), IN `start_date` DATE, IN `end_date` DATE)
            BEGIN
            SELECT
             DATE_FORMAT(ticket_taken_time, "%d/%m/%Y") as ticket_taken_time            ,terminal_total_sale_by_date(ticket_taken_time,term_id) as amount
                        ,terminal_commission_by_sale_date(ticket_taken_time,term_id) as commision
                        ,get_total_prize_value_by_date(ticket_taken_time,term_id) as prize_value
                        ,terminal_net_payable_by_sale_date(ticket_taken_time,term_id) as net_payable
            FROM (select play_masters.terminal_id as terminal_id,
            play_series.commision as commision, play_series.winning_price as winning_price, play_series.mrp as mrp,
            date(play_masters.created_at) as ticket_taken_time
            from play_details
            inner join play_masters ON play_masters.id = play_details.play_master_id
            inner join play_series ON play_series.id = play_details.play_series_id
            where date(play_masters.created_at) between start_date and end_date and terminal_id=term_id) as table1  group by ticket_taken_time;
            END ;
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS insert_game_result_details;
        CREATE PROCEDURE `insert_game_result_details`(IN `draw_id` INT,IN `payout` varchar(20))
            BEGIN
              DECLARE winning_series_id int(11);
              DECLARE dice_combination_id int(11);
              DECLARE _rollback BOOL DEFAULT 0;
              DECLARE CONTINUE HANDLER FOR SQLEXCEPTION SET _rollback = 1;
                START TRANSACTION;
                select play_series_id into winning_series_id from manual_result_digits
                where draw_master_id=draw_id and game_date=curdate();
                IF winning_series_id IS NULL THEN
                  set winning_series_id=get_lucky_result(draw_id,curdate(),payout);
                END IF;
                select id into dice_combination_id from dice_combination where play_series_id=winning_series_id
                order by rand() limit 1;
              /*insert into result master table*/
                insert into result_masters (
                game_date,
                play_series_id,
                draw_master_id,
                dice_combination_id,
                payout_status
              ) VALUES (
                curdate()
                ,winning_series_id
                ,draw_id
                ,dice_combination_id
                ,payout
              );
              /*end of insert into result master table*/
                IF _rollback THEN
                    ROLLBACK;
                ELSE
                    COMMIT;
                END IF;
            END ;;
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS terminal_date_wise_report_from_cpanel;
            CREATE PROCEDURE `terminal_date_wise_report_from_cpanel`(IN `start_date` DATE, IN `end_date` DATE, IN `stockistId` INTEGER(11))
            BEGIN

            IF stockistId > -1 THEN

            select coalesce (created_at,\'Total\') AS created_at,terminal,max(agent_name) as agent_name,sum(total_sale) as amount,sum(prize) as prize,
            sum(terminal_commission_on_sale) as terminal_commission_on_sale ,sum(total_sale)-sum(prize) as total
            ,sum(total_sale)-sum(prize)-sum(terminal_commission_on_sale) as net_to_pay
            from
            (select created_at,terminal,agent_name,barcode_number,sum(input_value*mrp) as total_sale,
            get_prize_value_of_barcode(barcode_number) as prize,
            terminal_id,max(terminal_commission) as terminal_commission,
            sum(input_value*mrp)*max(terminal_commission)/100 as terminal_commission_on_sale from
            (select play_masters.barcode_number, play_masters.is_claimed,
            play_masters.terminal_id,people.user_id as terminal,people.people_name as agent_name,stockist_to_terminals.stockist_id,
            date(play_masters.created_at) as created_at, play_details.play_series_id,
            play_details.input_value,play_series.mrp,play_masters.terminal_commission from play_masters
            inner join play_details on play_masters.id = play_details.play_master_id
            inner join play_series on play_details.play_series_id = play_series.id
            inner join people on play_masters.terminal_id = people.id
             INNER join stockist_to_terminals on play_masters.terminal_id=stockist_to_terminals.terminal_id
             INNER JOIN stockists ON stockist_to_terminals.stockist_id=stockists.id
            where date(play_masters.created_at) between start_date and end_date and stockist_to_terminals.stockist_id=stockistId)
            as table1 group by barcode_number,terminal_id,terminal,agent_name,created_at order by created_at) as table2
            group by terminal,created_at WITH ROLLUP;

            ELSE
            select coalesce (created_at,\'Total\') AS created_at,terminal,max(agent_name) as agent_name,sum(total_sale) as amount,sum(prize) as prize,
            sum(terminal_commission_on_sale) as terminal_commission_on_sale ,sum(total_sale)-sum(prize) as total
            ,sum(total_sale)-sum(prize)-sum(terminal_commission_on_sale) as net_to_pay
            from
            (select created_at,terminal,agent_name,barcode_number,sum(input_value*mrp) as total_sale,
            get_prize_value_of_barcode(barcode_number) as prize,
            terminal_id,max(terminal_commission) as terminal_commission,
            sum(input_value*mrp)*max(terminal_commission)/100 as terminal_commission_on_sale from
            (select play_masters.barcode_number, play_masters.is_claimed,
            play_masters.terminal_id,people.user_id as terminal,people.people_name as agent_name,stockist_to_terminals.stockist_id,
            date(play_masters.created_at) as created_at, play_details.play_series_id,
            play_details.input_value,play_series.mrp,play_masters.terminal_commission from play_masters
            inner join play_details on play_masters.id = play_details.play_master_id
            inner join play_series on play_details.play_series_id = play_series.id
            inner join people on play_masters.terminal_id = people.id
             INNER join stockist_to_terminals on play_masters.terminal_id=stockist_to_terminals.terminal_id
             INNER JOIN stockists ON stockist_to_terminals.stockist_id=stockists.id
            where date(play_masters.created_at) between start_date and end_date)
            as table1 group by barcode_number,terminal_id,terminal,agent_name,created_at order by created_at) as table2
            group by terminal,created_at WITH ROLLUP;

            END IF;

            END ;;
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS terminal_sale_report;
            CREATE PROCEDURE `terminal_sale_report`(IN `start_date` DATE, IN `end_date` DATE, IN `terminal_id` INT(11))
            BEGIN
            select date(created_at) as game_date,terminal,agent_name,sum(total_sale) as total_sale,sum(prize) as prize,sum(terminal_commission_on_sale) as terminal_commission_on_sale
            ,sum(total_sale)-sum(prize)-sum(terminal_commission_on_sale) as net_to_pay
            from
            (select terminal,agent_name,barcode_number,sum(input_value*mrp) as total_sale,get_prize_value_of_barcode(barcode_number) as prize,
            terminal_id,created_at,max(terminal_commission) as terminal_commission,sum(input_value*mrp)*(max(terminal_commission)/100) as terminal_commission_on_sale from
            (select play_masters.barcode_number, play_masters.is_claimed,
            play_masters.terminal_id,people.user_id as terminal,people.people_name as agent_name, play_masters.created_at, play_details.play_series_id,
            play_details.input_value,play_series.mrp,play_masters.terminal_commission from play_masters
            inner join play_details on play_masters.id = play_details.play_master_id
            inner join play_series on play_details.play_series_id = play_series.id
            inner join people on play_masters.terminal_id = people.id
            where date(play_masters.created_at) between start_date and end_date AND play_masters.terminal_id=terminal_id) as table1
            group by barcode_number,terminal_id,terminal,agent_name,created_at) as table2
            group by date(created_at),terminal,terminal_id,agent_name;
            END ;
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS terminal_summary_wise_report_for_admin;
               CREATE PROCEDURE `terminal_summary_wise_report_for_admin`(IN `start_date` DATE, IN `end_date` DATE, IN `stockistId` INT(11))
                BEGIN
                IF stockistId > -1 THEN
                select date(max(created_at)) as game_date,terminal,agent_name,max(stockist_name) as stockist_name
                ,sum(quantity)as quantity,sum(total_sale) as amount,
                sum(prize) as prize,sum(terminal_commission_on_sale) as terminal_commission_on_sale ,sum(total_sale)-sum(prize) as total
                ,sum(total_sale)-sum(prize)-sum(terminal_commission_on_sale) as net_to_pay
                from
                (select terminal,agent_name,stockist_id,stockist_name,barcode_number,
                sum(input_value) as quantity,sum(input_value*mrp) as total_sale,
                get_prize_value_of_barcode(barcode_number) as prize,
                terminal_id,created_at,max(terminal_commission) as terminal_commission,
                sum(input_value*mrp)*max(terminal_commission)/100 as terminal_commission_on_sale from
                (select play_masters.barcode_number, play_masters.is_claimed,
                play_masters.terminal_id,people.user_id as terminal,people.people_name as agent_name,
                stockist_to_terminals.stockist_id,stockists.stockist_name, play_masters.created_at, play_details.play_series_id,
                play_details.input_value,play_series.mrp,play_masters.terminal_commission from play_masters
                inner join play_details on play_masters.id = play_details.play_master_id
                inner join play_series on play_details.play_series_id = play_series.id
                inner join people on play_masters.terminal_id = people.id
                 INNER join stockist_to_terminals on play_masters.terminal_id=stockist_to_terminals.terminal_id
                 INNER JOIN stockists ON stockist_to_terminals.stockist_id=stockists.id
                where date(play_masters.created_at) between start_date and end_date and stockist_to_terminals.stockist_id=stockistId) as table1
                group by barcode_number,terminal_id,terminal,agent_name,stockist_id,stockist_name,created_at) as table2
                group by terminal,agent_name with ROLLUP;

                ELSE
                select date(max(created_at)) as game_date,terminal,agent_name,max(stockist_name) as stockist_name
                ,sum(quantity)as quantity,sum(total_sale) as amount,
                sum(prize) as prize,sum(terminal_commission_on_sale) as terminal_commission_on_sale ,sum(total_sale)-sum(prize) as total
                ,sum(total_sale)-sum(prize)-sum(terminal_commission_on_sale) as net_to_pay
                from
                (select terminal,agent_name,stockist_id,stockist_name,barcode_number,
                sum(input_value) as quantity,sum(input_value*mrp) as total_sale,
                get_prize_value_of_barcode(barcode_number) as prize,
                terminal_id,created_at,max(terminal_commission) as terminal_commission,
                sum(input_value*mrp)*max(terminal_commission)/100 as terminal_commission_on_sale from
                (select play_masters.barcode_number, play_masters.is_claimed,
                play_masters.terminal_id,people.user_id as terminal,people.people_name as agent_name,
                stockist_to_terminals.stockist_id,stockists.stockist_name, play_masters.created_at, play_details.play_series_id,
                play_details.input_value,play_series.mrp,play_masters.terminal_commission from play_masters
                inner join play_details on play_masters.id = play_details.play_master_id
                inner join play_series on play_details.play_series_id = play_series.id
                inner join people on play_masters.terminal_id = people.id
                 INNER join stockist_to_terminals on play_masters.terminal_id=stockist_to_terminals.terminal_id
                 INNER JOIN stockists ON stockist_to_terminals.stockist_id=stockists.id
                where date(play_masters.created_at) between start_date and end_date) as table1
                group by barcode_number,terminal_id,terminal,agent_name,stockist_id,stockist_name,created_at) as table2
                group by terminal,agent_name with ROLLUP;
                END IF;
                END ;
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS terminal_total_sale_report_from_cpanel;
            CREATE PROCEDURE `terminal_total_sale_report_from_cpanel`(IN `start_date` DATE, IN `end_date` DATE)
            BEGIN
            select date(created_at) as game_date,terminal,agent_name,sum(total_sale) as total_sale,sum(prize) as prize from
            (select terminal,agent_name,barcode_number,sum(input_value*mrp) as total_sale,get_prize_value_of_barcode(barcode_number) as prize,
            terminal_id,created_at from
            (select play_masters.barcode_number, play_masters.is_claimed,
            play_masters.terminal_id,people.user_id as terminal,people.people_name as agent_name, play_masters.created_at, play_details.play_series_id,
            play_details.input_value,play_series.mrp from play_masters
            inner join play_details on play_masters.id = play_details.play_master_id
            inner join play_series on play_details.play_series_id = play_series.id
            inner join people on play_masters.terminal_id = people.id
            where date(play_masters.created_at) between start_date and end_date) as table1
            group by barcode_number,terminal_id,terminal,agent_name,created_at) as table2
            group by terminal,terminal_id,agent_name,date(created_at) order by date(created_at) desc;
            END ;
        ');

        DB::unprepared('DROP PROCEDURE IF EXISTS transaction_report_by_terminal;
                CREATE PROCEDURE `transaction_report_by_terminal`(IN `term_id` VARCHAR(100), IN `start_date` DATE, IN `end_date` DATE)
                BEGIN
                            select * from (SELECT \'\' as activity_time,1 as `type`,\'opening point\' as \'transaction_type\',\'\' as barcode_number,get_opening_balance(start_date,term_id) as total
                UNION
                select created_at as activity_time,2 as `type`,\'Ticket sale\' as transaction_type,barcode_number, sum(total) as total FROM(SELECT play_masters.created_at,barcode_number,play_details.play_series_id,play_details.input_value,play_series.mrp,play_details.input_value*play_series.mrp as total
                FROM `play_masters`
                inner join play_details on play_masters.id=play_details.play_master_id
                inner join play_series on play_details.play_series_id=play_series.id
                where play_masters.terminal_id=term_id and date(play_masters.created_at) between start_date and end_date
                order by play_masters.barcode_number,play_masters.created_at) as table1 group by barcode_number,created_at
                UNION
                SELECT updated_at as activity_time,3 as `type`,\'Winning amount updated\' as transaction_type,barcode_number,get_prize_value_of_barcode(barcode_number)as total
                FROM `play_masters`
                where play_masters.terminal_id=term_id and date(play_masters.updated_at) between start_date and end_date
                UNION
                SELECT created_at as activity_time,4 as `type`,\'Points limit updated\' as transaction_type,\'\' as barcode_number,amount FROM `recharge_to_terminals`
                WHERE terminal_id=term_id and date(created_at) between start_date and end_date) as transaction_table
                order by date(activity_time),barcode_number,type ASC;
                END ;;

        ');





//        Schema::create('all_procedures_and_functions', function (Blueprint $table) {
//            $table->bigIncrements('id');
//            $table->timestamps();
//        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('all_procedures_and_functions');
    }
}
