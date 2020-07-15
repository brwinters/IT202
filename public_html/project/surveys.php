<?php
include_once(__DIR__."/partials/header.partial.php");

if(Common::is_logged_in()){
    //this will auto redirect if user isn't logged in
}
//TODO: Note, internally calling them questionnaires (and for admin), user facing they're called surveys.
$available = DBH::get_available_surveys();
?>
<div>
    <div class="list-group">
        <?php foreach($available as $s):?>
            <div>
                <h6><?php echo Common::get($s, "name");?></h6>
                <p><?php echo Common::get($s, "description", "");?></p>
                <?php if(Common::get($s, "use_max", false)):?>
                    <div>Max Attempts: <?php Common::get($s, "max_attempts", 0);?></div>
                <?php else:?>
                    <div>Daily Attempts: <?php Common::get($s, "attempts_per_day", 0);?></div>
                <?php endif; ?>
                <a href="survey.php?s=<?php Common::get($s, "id", -1);?>" class="btn btn-secondary">Participate</a>
            </div>
        <?php endforeach;?>
    </div>
</div>