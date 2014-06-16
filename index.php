<?php

include_once("phplib/base.php");


$time_requested = getOrSetRequestedDate();

$start_end = getWeekRange($time_requested);
$start_ts = $start_end[0];
$end_ts = $start_end[1];

$oncall_period = getOnCallWeekRange($time_requested); 
$oncall_start = $oncall_period[0];
$oncall_end = $oncall_period[1];

$list_of_users = getListOfPeopleWithReports();

$team_name = getTeamName();


$page_title = "{$team_name} Weekly Updates";
include_once('phplib/header.php');
include_once('phplib/nav.php')

?>

<div class="container">
<h1>Weekly updates week ending <?php echo date("l jS F Y", $end_ts ) ?></h1>
<div class="row">
    <div class="span9">
    <?php
        // Welcome splash screen
        if (empty($list_of_users) || !in_array(getUsername(), $list_of_users) ) {
            echo "<div class='hero-unit'><h1>Welcome to $team_name Weekly!</h1>";
            echo "<p>" . $team_name . " Weekly allows the $team_name team to record and store our weekly updates, as well as categorise and report on ";
            echo "our on call experiences. </p><p>On the $team_name team? Get started by adding your first weekly update!</p>";
            echo "<p><a href='/add.php' class='btn btn-success btn-large'><i class='icon-white icon-plus'></i> Add Yours Now</a></p>";
            echo "</div>";
        }

        if(isset($_GET['meeting_done'])) {
            echo insertNotify("success", "Thanks for running the weekly meeting! Your notes have been saved. ");
        }

        if($results = checkForPreviousMeetingNotes( generateMeetingNotesID($start_ts, $end_ts)  )) {
            echo formatMeetingNotesForPrint($results);
        }

        if (getTeamConfig('oncall')) {
            // Print the on call report if there is one yet for this period
            if($results = getOnCallReportForWeek($oncall_start, $oncall_end)) {
                echo "<h3>On call report <small> for week " . date("l jS F Y", $oncall_start) . " - " . date("l jS F Y", $oncall_end);
                echo " compiled by " . guessPersonOnCall($oncall_start, $oncall_end) . "</small></h3>";
                echo "<h5>" . count($results). " notifications received this week "; 
    ?>
                <small><a href="#" onClick="$('#oncall-table').fadeToggle('fast')">hide/show</a></small></h5>
    <?php
                echo printOnCallTableHeader();
                foreach ($results as $n) {
                    echo formatOnCallRowForPrint($n);
                }
                echo printOnCallTableFooter();
            }
        }

        // Print the generic weekly updates (if any)
        if(!$updates = getGenericWeeklyReportsForWeek($start_ts, $end_ts)) {
            echo insertNotify("error", "No Weekly Updates have been entered yet this week"); 
        } else {
            foreach ($updates as $update) {
                echo formatWeeklyReportForPrint($update);
            }
        }
    ?>

    </div>

    <div class="span3">
    <div><a href="/add.php" class="btn btn-success btn-block"><i class="icon-white icon-plus"></i> Add Yours</a></div>
    <br />
    <h4>Choose week</h4>
    <form id="setdate" action="index.php" method="post">
    <div id="datepicker" data-date="<?php echo date('m/d/y', $end_ts) ?>"><input id="picked-date" type="hidden" name="date" value=""></div>
    <script>
        $('#datepicker').datepicker({ format: "mm/dd/yy", todayHighlight: true, weekStart: 1 })
            .on('changeDate', function(e) {
                $('input[name=date]').val(e.date.toString());
                $('#setdate').submit();
            });
    </script>
    </form>
    <h4>Choose person</h3>
    <form id="setperson" action="user_updates.php" method="post">
    <select name="username" onchange="this.form.submit()"><option></option>
        <?php 
            foreach($list_of_users as $user) {
                echo "<option>{$user}</option>";
            }
        ?>
    </select>
    </form>

    </div>


</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
