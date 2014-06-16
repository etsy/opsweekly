<?php
include_once("phplib/base.php");

if (isset($_GET['week'])) {
    if(is_numeric($_GET['week'])) {
        $_POST['date'] = date("r", $_GET['week']);
    } else {
        die("Wat?");
    }
}

$time_requested = getOrSetRequestedDate();
$my_username = getUsername();

$start_end = getWeekRange($time_requested);
$start_ts = $start_end[0];
$end_ts = $start_end[1];

$oncall_period = getOnCallWeekRange($time_requested);
$oncall_start = $oncall_period[0];
$oncall_end = $oncall_period[1];

$permalink = "http://{$_SERVER['SERVER_NAME']}/meeting.php?week={$start_ts}";

$page_title = getTeamName() . " Weekly Updates - Meeting View";
include_once('phplib/header.php');
include_once('phplib/nav.php');
?>

<script>
function setDateToLastWeek() {
    $.post("meeting.php", { date: '<?php echo date("r", strtotime("1 week ago")) ?>' }, function() { window.location.href = 'meeting.php'} );
}
</script>

<div class="container">
<h1><?=getTeamName()?> Meeting For Week Ending <?php echo date("l jS F Y", $end_ts ) ?></h1>
<div class="row">
    <div class="span12">
    <?php 
        if ($end_ts > time() ) {
        ?>
        <div class="alert alert-block">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4>Warning!</h4>
            You're hosting a meeting for a week that hasn't ended yet. You probably want to host the meeting for last weeks data instead. <a href="javascript:setDateToLastWeek()">Click here to do that</a>. 
        </div>
        <?php
        } else {
        ?>
        <div class="alert alert-block alert-info">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            <h4>Welcome to the Weekly <?=getTeamName()?> Meeting</h4>
            Below is a compilation of all the things that happened for the selected week. Notes can be written below and they will be saved with that week's data for future reference.  <br />
        </div>

        <?php
        }
        ?>
        <div class="pull-right"><div class="btn-group"><a class="btn" role="button" data-toggle="modal" href="#permalink-modal"><i class="icon-bookmark"></i> Permalink</a></div></div>
        <?php 
            if($results = checkForPreviousMeetingNotes( generateMeetingNotesID($start_ts, $end_ts)  )) {
                $previous_timestamp = $results['timestamp'];
                $previous_user = $results['user'];
                $previous_report = $results['notes'];
                echo "<h2>Meeting Notes <small>taken by {$previous_user} at the " . getTeamName() . " Meeting held on " . date("l jS F Y", $previous_timestamp);
            } else {
                echo "<h2>Meeting Notes <small>you are taking at the " . getTeamName() . " Meeting today (" . date("l jS F Y") . ")";
                $previous_report = null;
            }
        ?>
        </small></h2>
        <form action="add_meeting_notes.php" method="POST" id="weekly-notes">
        <textarea class="textarea span12" name="weeklynotes" placeholder="Enter Meeting Notes, e.g. Hiring, Launches, Corp IT information" style="height: 200px">
            <?php echo $previous_report  ?>
        </textarea>
        <script>
            $('.textarea').wysihtml5({"image": false, "color": false});
        </script>
        <input type="hidden" name="range_start" value="<?php echo $start_ts ?>">
        <input type="hidden" name="range_end" value="<?php echo $end_ts ?>">
        <button class="btn btn-primary" type="submit">Save Meeting Notes</button>
        </form>

        <?php 
        if (getTeamConfig('oncall')) {
        ?>
        <h2>On Call Report
        <?php
        if($results = getOnCallReportForWeek($oncall_start, $oncall_end)) {
            echo "<small> for week " . date("l jS F Y", $oncall_start) . " - " . date("l jS F Y", $oncall_end);
            echo " compiled by " . guessPersonOnCall($oncall_start, $oncall_end) . "</small></h2>";
        ?>
        <table class="table table-striped table-bordered table-hover" id="oncall-table" style='font-size: 90%'>
        <thead>
        <tr>
        <th>Date/Time</th><th>Host</th><th>Service</th><th>Output</th><th>State</th>
        </tr>
        </thead>
        <tbody>

        <?php
            foreach ($results as $n) {
                echo formatOnCallRowForPrint($n);
            }
        echo "</tbody></table>";
        } else {
            echo "</h2>";
            echo insertNotify("critical", "Uh-oh! There has been no weekly report filed for this week yet!");
        }
        ?>

        <div id="oncall-stats">Loading report...</div>
        <script>
            $.post("/generate_report.php", { type: 'week', date: '<?php echo $time_requested ?>' }).done(function(data) {
                $('#oncall-stats').html(data);
            });
        </script>
        <? } ?>

        <h2>Weekly Updates</h2>
        <?php
            if(!$updates = getGenericWeeklyReportsForWeek($start_ts, $end_ts)) {
                echo insertNotify("error", "No Weekly Updates have been entered yet this week");
            } else {
                foreach ($updates as $update) {
                    echo formatWeeklyReportForPrint($update);
                }
            }
        ?>

        <h2>Fin</h2>
        <p>You have reached the end of the meeting report! </p>
        <button class="btn btn-primary" onClick="$('#weekly-notes').submit()">Save Meeting Notes</button>
        <a class="btn btn-success" href="/index.php"><i class="icon-white icon-home"></i> Home</a>
        <br />
        <br />
    </div>
</div>
</form>

</div>

<!-- Modal for permalink -->
<div id="permalink-modal" class="modal hide fade" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
  <div class="modal-header">
    <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
    <h3 id="myModalLabel">Permalink to this meeting</h3>
  </div>
  <div class="modal-body">
    <p>To link other people to this meeting, use the following URL:</p>
    <pre><a href="<?php echo $permalink; ?>"></a><?php echo $permalink ?></pre>
  </div>
  <div class="modal-footer">
    <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Close</button>
  </div>
</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
