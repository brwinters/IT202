<?php
//TODO use get_finished_competitions to fetch comps that need calc
//update calced_winner to 1 once done
//query fetches comp data, second query will be needed to fetch player stats
require(__DIR__ . "/../includes/common.inc.php");
$result = DBH::get_pending_competitions();
if(Common::get($result, "status", 400) == 200){
    $comps = Common::get($result, "data", []);
    $comp_ids = [];//valid participant count
    $comp_ids_invalid = [];//not enough participants
    foreach($comps as $comp){
        //TODO filter out competitions without enough people
        $participants = (int)Common::get($comps, "participants", 0);
        $min_participants = (int)Common::get($comps, "min_participants", 3);
        $comp_id = Common::get($comps, "id", -1);
        if($participants < $min_participants){
            //save these for later so we can process them separately
            //no need to waste resources calculating scores and all for these.
            array_push($comp_ids_invalid, $comp_id);
        }
        else {
            array_push($comp_ids, $comp_id);
        }
    }
    //TODO take a look at this function, decent bit of magic happens inside
    //goal: for each competition get the top 10 (only counts if there were wins during the competition active period
    $result = DBH::get_competitions_scoreboard($comp_ids);

    if(Common::get($result, "status", 400) == 200){
        $scoreboard = [];
        $data = Common::get($result, "data", []);
        //prep our data, although there are better ways to do this
        foreach($data as $d) {
            $cid = Common::get($d, "competition_id", -1);
            $uid = Common::get($d, "user_id", -1);
            $wins = Common::get($d, "wins", 0);//technically will not exist since only wins are pulled
            if (!array_key_exists($cid)) {
                //if key doesn't exist add it
                array_push($scoreboard, $cid);
            }
            //now we can push user-wins to the key
            array_push($scoreboard[$cid], [
                $uid => $wins
            ]);
        }
        //here our $scoreboard should be populated into unique competitions
        foreach($scoreboard as $comp_id=>$users){
            //shouldn't need to do this since it should come from the DB in proper order
            //but just being sure
            //TODO put key/value pairs in desc order based on value
            $users = arsort($users);//should have no more than 10 for each comp
            $comp = Common::get($comps, $comp_id, []);//competition data for point award calc
            $fp = (float)round(Common::get($comp, "first_place", 1),1);
            $winners = [];
            //TODO likely there will be rounding errors and we may generate
            //more points than necessary, but the amount should be small enough that we don't care
            //you can do extra validation/math if it really matters
            if($fp == 1.0){//be very careful with float comparison
                //this is the easy one, just 1 winner
                $fpp = (int)Common::get($comp, "points", 1);
                $fpp *= $fp;
                $fpp = round($fpp, 0);//round to nearest whole number, see note above
                $fpw = Common::get($users, 0, -1);
                array_push($winners, [
                    $fpw => [$fpp, "1st"]
                ]);
            }
            else{
                $sp = (float)round(Common::get($comp, "second_place", 0), 1);
                //get our 2nd place winner
                $spp = (int)Common::get($comp, "points", 1);
                $spp *= $sp;
                $spp = round($spp, 0);//round to nearest whole number, see note above
                $spw = Common::get($users, 1, -1);
                array_push($winners, [
                    $spw => [$spp,"2nd"]
                ]);
                if(round($fp+$sp) == 1.0){//again be careful
                    //ok we can stop
                }
                else{
                    $tp = (float)round(Common::get($comp, "third_place", 0),1);
                    //get our 3rd place winner
                    $tpp = (int)Common::get($comp, "points", 1);
                    $tpp *= $tp;
                    $tpp = round($tpp, 0);//round to nearest whole number, see note above
                    $tpw = Common::get($users, 2, -1);
                    array_push($winners, [
                        $tpw => [$tpp, "3rd"]
                    ]);
                }
            }
            //TODO award our winners
            $hadError = false;
            foreach($winners as $winner_id=>$reward){
                //filter out invalid entries from above calculation
                if($winner_id > -1) {
                    //this will generate a lot of DB calls depending on how many comps complete
                    $result = DBH::changePoints($winner_id, $reward[0], -1, "comp_winner", $reward[1] . " place");
                    if (Common::get($result, "status", 400) != 200) {
                        error_log("Error awarding user[$winner_id] $reward[0] points for $reward[1] place");
                        $hadError = true;
                    }
                }
            }
            if(!$hadError){
                //Mark competition as calculated (pass an array of 1 since this is dynamic)
                $result = DBH::set_calc_completed_competition([$comp_id]);
                if(Common::get($result, "status", 400) != 200){
                    error_log("Error marking competition as calc completed, this could re-award players that didn't fail");
                }
                else{
                    error_log("Marked Competition $comp_id as completed (all users awarded)");
                }
            }
        }
        //complete the invalid ids
        $result = DBH::set_calc_completed_competition($comp_ids_invalid);
        if(Common::get($result, "status", 400) != 200){
            error_log("Error marking competition as calc completed for invalid comps");
        }
        else{
            error_log("Marked Invalid Competitions (" . ','.join($comp_ids_invalid) . ") as completed");
        }
    }
}
?>