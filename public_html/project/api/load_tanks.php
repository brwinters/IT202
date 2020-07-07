<?php
require(__DIR__ . "/../includes/common.inc.php");

if(Common::is_logged_in(false)) {
    $tanks = array();
    $playerTanks = Common::get($_SESSION["user"], "tanks", []);
    if (count($playerTanks) > 0) {
        //get first/only tank
        $t = $playerTanks[0];
        array_push($tanks, $t);
        //https://www.w3schools.com/php/func_math_mt_rand.asp
        $speed = Common::get($t, "speed", 50);
        $range = Common::get($t, "range", 50);
        $turnSpeed = Common::get($t, "turnSpeed", 25);
        $fireRate = Common::get($t, "fireRate", 10);
        $health = Common::get($t, "health", 3);

        $enemyTank = array(
            "speed"=>mt_rand($speed*.5, $speed*1.5),
            "range"=>mt_rand($range*.5, $range*1.5),
            "turnSpeed"=>mt_rand($turnSpeed*.5, $turnSpeed*1.5),
            "fireRate"=>mt_rand($fireRate*.5, $fireRate*1.5),
            "health"=>mt_rand($health*.5, $health*1.5),
            "tankColor"=>'',
            "barrelColor" =>'',
            "barrelTipColor" => '',
            "treadColor" => '',
            "hitColor" => '#A2082B',
            "gunType" => mt_rand(1,3)
        );
    }
}
?>

