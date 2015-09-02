<?php

include_once("phplib/base.php");

$my_username = getUsername();

$page_title = getTeamName() . " Weekly Updates - Your profile";
include_once('phplib/header.php');
include_once('phplib/nav.php');

?>

<div class="container">

<?php

if (!$profile = checkForUserProfile($my_username)) {
    echo insertNotify("info", "Welcome to your profile page! Please <a href='{$ROOT_URL}/edit_profile.php'>edit your profile</a>");
    $profile = null;
} else {
    $sleeptracking_settings = json_decode($profile['sleeptracking_settings'], 1);
}

// Welcome the user either with their full name, or their username.
if ($profile['full_name'] != "") {
    list($name, $rest) = explode(" ", $profile['full_name'], 2);
} else {
    $name = $my_username;
}

echo "<h1>Hello {$name} <small>viewing your profile</small></h1>";

// Count the number of weekly updates for the summary
if (!$updates = getGenericWeeklyReportsForUser($my_username)) {
    $num_weekly = "no";
} else {
    $num_weekly = count($updates);
}

// Get the oncall ranges for this user for their stats, and for the summary.
if(!$oncall_ranges = getAvailableOnCallRangesForUser($my_username)) {
    $oncall = false;
} else {
    $oncall = true;
    $num_oncall = count($oncall_ranges);
    $sleep_status_agg = array();
    $tag_agg_norm = array();
    $mtts_total = 0;
    $top_wakeup_cause = array();
    $tag_graph_data[] = array("Week", "Action Taken", "No Action Taken");
    // Let's get the last few on-call rotations for a sleep retrospective.
    $oncall_sleep_retrospective_details = array();

    // Create summary metrics about this person's on call
    foreach ($oncall_ranges as $week) {
        $results = getOnCallReportForWeek($week['range_start'], $week['range_end']);
        $personal_notifications = $personal_notifications + count($results);
        $week_tag_summary = array("action" => 0, "noaction" => 0);

        // Deep notification loop
        foreach ($results as $n) {
            $tag_agg_norm[$nagios_tag_category_map[$n['tag']]]++;
            $week_tag_summary[$nagios_tag_category_map[$n['tag']]]++;

            // Sleep status tracking
            $sleep_status_agg[$n['sleep_state']]++;
            if ($n['mtts'] > -1 && $n['sleep_state'] > 0) {
                $mtts_total = $mtts_total + $n['mtts'];
                $top_wakeup_cause[$n['service']]++;
                $sleep = true;
            }

            // Bit of a hack that takes the hour and minute of the notification and adds it to todays
            // date so we can use it in the timemap. 
            $timemap_hourmin = date('H:i', $n['timestamp']); 
            $timemap_const = DateTime::createFromFormat("Ymd H:i", date("Ymd")." {$timemap_hourmin}");
            $timemap_data[$timemap_const->format('U')]++;
        }
        $tag_graph_data[] = array(date("j M y", $week['range_start']), $week_tag_summary["action"], $week_tag_summary["noaction"]);

    }
    if ($sleep) {
        $my_wake_ups_pct = ($sleep_status_agg[1] / ($sleep_status_agg[1] + $sleep_status_agg[0]))*100;
        $mtts = round( ($mtts_total / $sleep_status_agg[1]) / 60, 2);
    }
    $personal_per_week = round($personal_notifications / $num_oncall, 2);
    $my_noaction_pct = ($tag_agg_norm['noaction'] / ($tag_agg_norm['noaction'] + $tag_agg_norm['action']))*100;

    // Get metrics for all on calls for the last year for comparison
    $weeks = getAvailableOnCallRangesForLastYear();
    $total_weeks = count($weeks);
    $all_sleep_status_agg = array();
    $all_tag_agg_norm = array();
    $all_mtts_total = 0;

    foreach ($weeks as $week) {
        $results = getOnCallReportForWeek($week['range_start'], $week['range_end']);
        $total_notifications = $total_notifications + count($results);

        // Deep notification loop
        foreach ($results as $n) {
            // Sleep status tracking
            $all_sleep_status_agg[$n['sleep_state']]++;
            $all_tag_agg_norm[$nagios_tag_category_map[$n['tag']]]++;
            if ($n['mtts'] > -1 && $n['sleep_state'] > 0) {
                $all_mtts_total = $all_mtts_total + $n['mtts'];
            }
        }

    }
    $all_wake_ups_pct = ($all_sleep_status_agg[1] / ($all_sleep_status_agg[1] + $all_sleep_status_agg[0]))*100;
    $total_per_week = round($total_notifications / $total_weeks, 2);
    $all_mtts = round ( ($all_mtts_total / $all_sleep_status_agg[1]) / 60, 2);
    $all_noaction_pct = ($all_tag_agg_norm['noaction'] / ($all_tag_agg_norm['noaction'] + $all_tag_agg_norm['action']))*100;

    $per_week_diff = $total_per_week - $personal_per_week;
    $per_week_diff = ($total_per_week > $personal_per_week) ?
        "<span class='text-success'><i class='icon-arrow-up'></i> $per_week_diff better off</span>" :
        "<span class='text-error'><i class='icon-arrow-down'></i> $per_week_diff worse off</span>";

    if ($profile && $profile['sleeptracking_provider'] != "none" && $profile['sleeptracking_provider'] != "") {
        $sleep_tracking = true;
        $sleep_name = $sleep_providers[$profile['sleeptracking_provider']]['display_name'];
        $sleep_logo = $sleep_providers[$profile['sleeptracking_provider']]['logo'];
    }

    // Build up sleep-related metrics for the last few on-call rotations, for a simple retrospective.
    $oncall_sleep_retrospective_count = getTeamConfig('oncall_sleep_retrospective_count');
    if ($oncall_sleep_retrospective_count) {
        // Get the last few on-call rotations, in descending order.
        $oncall_sleep_retrospective_weeks = array_slice(array_reverse($oncall_ranges), 0, $oncall_sleep_retrospective_count);
        foreach ($oncall_sleep_retrospective_weeks as $week) {
            $results = getOnCallReportForWeek($week['range_start'], $week['range_end']);
            $week_wake_ups = 0;
            $week_sleep_loss = 0; // Seconds.
            $week_sleep_abandoned = 0;
            foreach ($results as $n) {
                // Count the number of wake ups.
                if ($n['sleep_state'] > 0) {
                    $week_wake_ups++;
                }
                // If we have non-zero MTTS data and the operator was asleep (1), sum the sleep loss.
                if ($n['mtts'] > -1 && $n['sleep_state'] > 0) {
                    $week_sleep_loss = $week_sleep_loss + $n['mtts'];
                }
                // If MTTS is 0 and the operator was asleep when notified, the operator abandoned sleep.
                if ($n['mtts'] == 0 && $n['sleep_state'] > 0) {
                    $week_sleep_abandoned++;
                }
            }
            $week_sleep_loss_in_hours = round(($week_sleep_loss / 60 / 60), 2);
            $week_mtts_in_minutes = round( (($week_sleep_loss / 60) / $week_wake_ups), 2);
            // Append the retrospective data to our array.
            $oncall_sleep_retrospective_details[] = array(
                'range_start' => $week['range_start'],
                'range_end' => $week['range_end'],
                'week_wake_ups' => $week_wake_ups,
                'week_sleep_loss_in_hours' => $week_sleep_loss_in_hours,
                'week_mtts_in_minutes' => $week_mtts_in_minutes,
                'week_sleep_abandoned' => $week_sleep_abandoned
            );
        }
    }

}

?>

<style>
    .stats li { margin: 0.6em }
</style>
<div class="row">
    <div class="span12">
        <h2>Your Summary</h2>
        <p class='lead'>You've submitted <a href="<?php echo $ROOT_URL; ?>/user_updates.php"><?php echo $num_weekly; ?> weekly reports</a>
        <?php if ($oncall) { ?>
            and <?php echo $num_oncall; ?> on call reports</p>
        <h3>On Call</h3>
        <?php if ($sleep_tracking) { ?>
        <h4>Sleep Summary</h4>
        <ul class='stats lead'>
        <li>You're using sleep tracking from  <img src="<?php echo $sleep_logo; ?>"> <strong><?php echo $sleep_name; ?></strong></li>
        <li>You've been woken up an average of <strong><?php echo round($sleep_status_agg[1] / $num_oncall, 1); ?> times per week</strong>
            and have lost a total of <strong><?php echo round( $mtts_total / 60 / 60, 2); ?> hours of sleep</strong> to notifications</li>
        <li>That's an average of <strong><?php echo round ( ($mtts_total / $num_oncall) / 60 / 60, 2); ?> hours sleep lost per week</strong> 
            due to notifications (<?php echo round ( ($all_mtts_total / $total_weeks) / 60 / 60, 1);  ?> hours globally)</li>
        <li><strong><?php echo round($my_wake_ups_pct, 1); ?>%</strong> of notifications have woken you up, 
            compared to the average of <?php echo round($all_wake_ups_pct, 1); ?>%</li>
        <li>The service that woke you up the most was <strong>'<?php echo array_search(max($top_wakeup_cause), $top_wakeup_cause); ?>'
            </strong> which it did <?php echo max($top_wakeup_cause); ?> times</li>
        <li>Your <strong>Mean Time To Sleep is <?php echo $mtts; ?> minutes</strong> compared to an average from all users of 
            <?php echo $all_mtts; ?> minutes</li>
        </ul>

            <?php if ($oncall_sleep_retrospective_count) { ?>
        <h4>Sleep Retrospective</h4>
        <p class='lead'>The impact on your sleep, for the last <?php echo $oncall_sleep_retrospective_count; ?> on-call rotations breaks down as follows:</p>
                <?php foreach ($oncall_sleep_retrospective_details as $retrospective) { ?>
        <h5>On-call Period: <?php echo date("l jS F Y", $retrospective['range_start']) . ' - ' . date("l jS F Y", $retrospective['range_end']); ?></h5>
        <ul>
        <li>You were woken <b><?php echo $retrospective['week_wake_ups']; ?> times</b>.</li>
        <li>You were awake for a total of <b><?php echo $retrospective['week_sleep_loss_in_hours']; ?> hours</b>.</li>
        <li>Your mean time to sleep (MTTS) was <b><?php echo $retrospective['week_mtts_in_minutes']; ?> minutes</b>.</li>
        <li>You abandoned sleep <b><?php echo $retrospective['week_sleep_abandoned']; ?> times</b>.</li>
        </ul>
                <?php } ?>
            <?php } ?>
        <?php } ?>
        
        <h4>Notifications</h4>
        <ul class='stats lead'>
        <li>You've had a total of <?php echo $personal_notifications; ?> notifications across <?php echo $num_oncall; ?> weeks, giving an
            average of <strong><?php echo $personal_per_week; ?> alerts per week</strong>. </li>
        <li>This compares to an average of <?php echo $total_per_week; ?> per week in total in the last year, 
            leaving you <strong><?php echo $per_week_diff; ?></strong></li>
        <li>You tagged <strong><?php echo round($my_noaction_pct, 2); ?>% of alerts 'no action taken'</strong> compared to 
            <?php echo round($all_noaction_pct, 2); ?>% globally. </li>
        </ul>
        <br />
        <h4>Personal Notification Time Map</h4>
            <p>This illustrates the hours of the day you received notifications during your on call periods. </p>
            <div id="cal-heatmap"></div>
            <script type="text/javascript">
            var time_data = <?php echo json_encode($timemap_data) ?>;

            var cal = new CalHeatMap();
            cal.init({
                data: time_data,
                domain : "hour",
                start: new Date(<?php echo date("U", strtotime("today")) ?>*1000),
                subDomain : "min",
                range : 24,
                cellsize: 6,
                cellpadding: 1,
                domainGutter: 1,
                itemName: ["notification", "notifications"],
                format: { date: "%H:%M", legend: "%H:%M" },
            });
            </script>
        <br />
        <h4>Your on calls at a glance</h4>
            <div id="alert_tags_chart"></div>
            <br />

            <script type="text/javascript">
                var data = google.visualization.arrayToDataTable(<?php echo json_encode($tag_graph_data) ?>);

                var options = {
                    hAxis: { title: 'Week Commencing' },
                    vAxis: { title: 'Number of notifications' }
                };

                var chart = new google.visualization.LineChart(document.getElementById('alert_tags_chart'));
                chart.draw(data, options);
            </script>

            <div id="alert_tags_pct_chart"></div>

        <br />
        <?php } ?>
    </div>
</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
