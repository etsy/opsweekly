<?php
include_once("phplib/base.php");

$time_requested = getOrSetRequestedDate();

$my_username = getUsername();

$start_end = getWeekRange($time_requested);
$start_ts = $start_end[0];
$end_ts = $start_end[1];

$oncall_period = getOnCallWeekRange($time_requested);
$oncall_start = $oncall_period[0];
$oncall_end = $oncall_period[1];


$page_title = getTeamName() . " Weekly Updates - Add new update";
include_once('phplib/header.php');
include_once('phplib/nav.php')

?>

<div class="container">
<h1>Update for week ending <?php echo date("l jS F Y", $end_ts ) ?></h2>
<div class="row">
    <div class="span12">
        <?php 
        if (getTeamConfig('oncall')) {
        ?>
        <h2>On call report</h2>
        <?php if(isset($_GET['oncall_succ'])) {
            echo insertNotify("success", "Your on call update has been saved successfully");
        }
        ?>
        <div id="on-call-question">
        <?php 
        // See if a report was already submitted for this week. Doesn't matter, just a heads up. 
        if ($oncall_user = guessPersonOnCall($oncall_start, $oncall_end)) {
            if ($oncall_user == $my_username) {
                echo "<p>You have already submitted a report this week, but you can update or add to it by clicking the button below</p>";
            } else {
                echo "<p>An on call report has already been submitted by someone else this week, but you can update or add to it by clicking the button below</p>";
            }
        } else {
            echo "<p>Were you on call this week? Click button to load notification report</p>";
        }
        ?>
        <button type="button" class="btn btn-danger" data-loading-text="Generating On Call Summary..." 
            onclick="$(this).button('loading'); $('.notifications').load('generate_oncall_survey.php?date=<?php echo urlencode($time_requested) ?>', function() { $('#on-call-question').fadeOut('fast') });">I was on call</button>
        </div>
        <div class="notifications" id="notifications"></div>
        <p></p><br />
        <?php }  ?>

        <h2>Regular update</h2>
        <div class="row">
            <div class="span7">
                <?php if(isset($_GET['weekly_succ'])) {
                    echo insertNotify("success", "Your weekly update has been saved successfully");
                } elseif(isset($_GET['weekly_succ_email'])) {
                    echo insertNotify("success", "Your weekly update has been saved and emailed successfully");
                }

                if($results = checkForPreviousWeeklyUpdate( generateWeeklyReportID($my_username, $start_ts, $end_ts)  )) {
                    # Previous report was found
                    $previous_report = $results['report'];
                } else {
                    $previous_report = null;
                }

                ?>
                <form action="add_generic_weekly.php" method="POST">
                <textarea class="textarea span7" name="weeklyupdate" placeholder="Enter Update" style="height: 500px"><?php echo $previous_report  ?></textarea>
                <script>
                    $('.textarea').wysihtml5({"image": false, "color": false});
                    var wysihtml5Editor = $(".textarea").data("wysihtml5").editor;
                    wysihtml5Editor.observe("focus", function() {
                        if ($('a[data-dismiss="alert"]').html() !== undefined) {
                            $('a[data-dismiss="alert"]').click();
                        }
                    });
                </script>
                <input type="hidden" name="range_start" value="<?php echo $start_ts ?>">
                <input type="hidden" name="range_end" value="<?php echo $end_ts ?>">
                <button class="btn btn-primary" type="submit">Save Weekly Update</button>
                <button class="btn btn-success" name="do_email" type="submit" value="true"><i class="icon-white icon-envelope"></i> Save and email this report</button>
                </form>
            </div>
            <div class="span5">
            <?php
                // This is where hints aka weekly providers are loaded and displayed
                printWeeklyHints($my_username, $start_ts, $end_ts);
            ?>
            </div>
        </div>
  
    </div>
</div>
</form>

</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
