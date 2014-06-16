<?php

// Called from various places using Ajax to produce a HTML report of type 'week' or 'year'
// which summarises the on call data stored about those time periods.
// TODO: I'm really sorry. This should be much much smaller and broken down into many small functions.

include_once("phplib/base.php");


$time_requested = (isset($_POST['date'])) ? $_POST['date'] : "now";
$report_type = (isset($_POST['type'])) ? $_POST['type'] : "week";

$start_end = getWeekRange($time_requested);
$start_ts = $start_end[0];
$end_ts = $start_end[1];

$oncall_period = getOnCallWeekRange($time_requested); 
$oncall_start = $oncall_period[0];
$oncall_end = $oncall_period[1];

if ($report_type == "week") {

?>

<div class="row">
    <div class="span12">
    <?php
        if($results = getOnCallReportForWeek($oncall_start, $oncall_end)) {
            $total_notifications = count($results);

            echo "<h2>Week Stats <small> for week " . date("l jS F Y", $oncall_start) . " - " . date("l jS F Y", $oncall_end);
            echo "</small></h2>";
            echo "<p class='lead'>{$total_notifications} notifications received this week </p>";

            $week_status_total = array();
            $week_tag_total = array();
            $week_top_host_agg = array();
            $week_top_service_agg = array();
            $week_time_counts = array();
            $week_sleep_status_agg = array();
            $per_day_total = array();
            $per_day_graph_data[] = array("Day of Week", "Alerts");
            $week_status_agg_total = 0;
            $week_tag_agg_total = 0;
            $week_mtts_total = 0;
            $week_rtts_count = 0;
            $week_ntts_count = 0;

            foreach ($results as $n) {
                // Stand back, it's time to add
                $week_status_agg[$n['state']]++;
                $week_status_agg_total++;
                $week_tag_agg_normalised[$nagios_tag_category_map[$n['tag']]]++;
                $week_tag_agg[$n['tag']]++;
                $week_tag_agg_total++;
                $week_top_host_agg[$n['hostname']]++;
                $week_top_service_agg[$n['service']]++;
                $week_time_counts[$n['timestamp']]++;
                $per_day_total[date('w', $n['timestamp'])]++;

                // Sleep stats
                $week_sleep_status_agg[$n['sleep_state']]++;
                if ($n['mtts'] > 0 && $n['sleep_state'] > 0) {
                    $week_mtts_total = $week_mtts_total + $n['mtts'];
                    // Number of times person returned to sleep
                    $week_rtts_count++;
                }
                if ($n['mtts'] == 0 && $n['sleep_state'] > 0) {
                    // This indicates a 'no back to sleep' situation after an awakening
                    $week_ntts_count++;
                }
            }

            // Collect up the per day totals into the graph array
            ksort($per_day_total);
            foreach ($per_day_total as $d => $c) {
                // This seems a bit bizarre but there's no way to convert a DoW number to name? I could hardcode but
                // what if people want it in their own locale?
                $per_day_graph_data[] = array(date('l', strtotime("Sunday +{$d} days")), $c);
            }
        ?>
        <h3>Alert Status Distribution</h3>
        <p>Breakdown of the type of notifications recieved during the week</p>
            <?php 
                $html_status_summary = "";
                $html_status_bar = "";
                foreach ($week_status_agg as $type => $number) {
                    $pct = round( ($number / $week_status_agg_total) * 100, 2);
                    $html_status_summary .= "<span class='well well-small'><span class='label label-{$nagios_state_to_badge[$type]}'>{$type}</span>  {$number} ({$pct}%) </span>&nbsp;";
                    $html_status_bar .= "<div class='bar bar-{$nagios_state_to_bar[$type]}' style='width: {$pct}%;'></div>";
                }
            ?>
        <div class="progress input-xxlarge">
        <?php echo $html_status_bar ?>
        </div>
        <p><?php echo $html_status_summary ?></p>
        <br />

        <h3>Tag Status Summary</h3>
        <p>Breakdown of the tags applied to the notifications recieved during the week</p>
            <?php
                $html_status_summary = "";
                foreach ($week_tag_agg as $type => $number) {
                    $pct = round( ($number / $week_tag_agg_total) * 100, 2);
                    $html_status_summary .= "<tr><td><span class='label'>{$nagios_alert_tags[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
            ?>
        <table class="table"><?php echo $html_status_summary ?></table>
        <p>Breakdown of the tags applied (normalised)</p>
             <?php
                $html_status_summary = "";
                foreach ($week_tag_agg_normalised as $type => $number) {
                    $pct = round( ($number / $week_tag_agg_total) * 100, 2);
                    $html_status_summary .= "<tr><td><span class='label'>{$nagios_tag_categories[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
            ?>
        <table class="table"><?php echo $html_status_summary ?></table>
        <br />

<?php if ( ($week_sleep_status_agg[1] > 0) || ($week_sleep_status_agg[0] > 0) ) { ?>
        <h3>Sleep State Summary</h3>
        <p>Breakdown of sleep states person was in during notifications this week</p>
             <?php
                $html_status_summary = "";
                foreach ($week_sleep_status_agg as $type => $number) {
                    $pct = round( ($number / $week_status_agg_total) * 100, 2);
                    $html_status_summary .= "<tr><td><i class='{$sleep_state_icons[$type]}'></i> <span class='label'>
                                             {$sleep_states[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
            ?>
        <table class="table"><?php echo $html_status_summary ?></table>

        <p class='lead'>Mean Time to Sleep: <i class='icon-time'> </i> 
            <?php echo round( ($week_mtts_total / $week_rtts_count) / 60, 2) ?> minutes</p>
        <p class='lead'>Total time spent awake due to notifications:
            <?php echo round( $week_mtts_total / 60, 0) ?> minutes</p>
        <p class='lead'>Number of times sleep was abandoned: 
            <?php echo $week_ntts_count ?> times</p>

        <br />

<?php } ?>

        <h3>Top Notifying Hosts</h3>
        <p>Hosts that recieved the most notifications during this week</p>
        <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
            <th>Hostname</th><th>Count</th>
            </tr>
        </thead>
        <tbody> 
        <?php
            # Get the top 10
            arsort($week_top_host_agg);
            $week_top_host_agg = array_slice($week_top_host_agg, 0, 10);

            foreach($week_top_host_agg as $host_name => $number) {
                echo "<tr><td>{$host_name}</td><td>{$number}</td></tr>";
            }
        ?>
        </tbody>
        </table>

        <h3>Top Notifying Services</h3>
        <p>Services that recieved the most notifications during this week</p>
        <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
            <th>Service</th><th>Count</th>
            </tr>
        </thead>
        <tbody> 
        <?php
            arsort($week_top_service_agg);
            $week_top_service_agg = array_slice($week_top_service_agg, 0, 10);

            foreach($week_top_service_agg as $service_name => $number) {
                echo "<tr><td>{$service_name}</td><td>{$number}</td></tr>";
            }
        ?>
        </tbody>
        </table>

        <h3>Alert Volume per Day</h3>
        <p>Breakdown of the number of alerts received per day over the last week</p>
        <div id="per_day_volume"></div>
        <br />

        <script type="text/javascript">
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($per_day_graph_data) ?>);

            var options = {
                hAxis: { title: 'Day of Week', gridlines: { count: 10 } },
                vAxis: { title: 'Number of alerts' }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('per_day_volume'));
            chart.draw(data, options);
        </script>


        <h3>Notification Time Map</h3>
        <p>Grids read from top to bottom through hours, the darker the more alerts were recieved <small>(Hover over the blocks for a count)</small></p>
        <div id="cal-heatmap"></div>
        <script type="text/javascript">
        var time_data = <?php echo json_encode($week_time_counts) ?>;

        var cal = new CalHeatMap();
        cal.init({
            data: time_data,
            domain : "day",
            start: new Date(<?php echo date("U", $oncall_start) ?>*1000),
            subDomain : "hour",
            range : 8,
            itemName: ["notification", "notifications"],
            domainGutter: 4
        });
        </script>
        <br />

    </div>
</div>

<?php 
        } else { # There is no on call data for this period
            echo insertNotify("error", "There is no on call report for the period " . date("l jS F Y", $oncall_start) . " - " . date("l jS F Y", $oncall_end) . " yet. Choose a different week in the nav bar.");
        }
        
    } elseif($report_type == "year") { ?>

<div class="row">
    <div class="span12">

        <h2>Overall stats <small> for last year </small></h2>
        <?php
        $weeks = getAvailableOnCallRangesForLastYear();
        $num_weeks = count($weeks);
        $year_status_total = array();
        $year_tag_total = array();
        $year_top_host_agg = array();
        $year_top_service_agg = array();
        $year_time_counts = array();
        $year_status_agg_total = 0;
        $year_tag_agg_total = 0;
        $year_sleep_status_agg = array();
        $year_mtts_total = 0;
        $year_rtts_total = 0;
        $year_ntts_count = 0;
        $per_day_total = array();
        $per_day_graph_data[] = array("Day of Week", "Alerts");

        $status_graph_data[] = array("Week", "Critical", "Warning", "Down", "Unknown");
        $tag_graph_data[] = array("Week", "Action Taken", "No Action Taken");
        $tag_pct_graph_data[] = array("Week", "Action Taken", "No Action Taken");
        $user_tag_graph_data[] = array("Week/Person", "Action Taken", "No Action Taken");

        if (count($weeks) < 2) {
            echo insertNotify("error", "There is not enough data to generate this report yet! You must have at least two weeks of data. ");
        } else {
            foreach ($weeks as $week) {
                $results = getOnCallReportForWeek($week['range_start'], $week['range_end']);
                $year_total_notifications = $year_total_notifications + count($results);

                $week_status_summary = array("CRITICAL" => 0, "WARNING" => 0, "DOWN" => 0, "UNKNOWN" => 0);
                $week_tag_summary = array("action" => 0, "noaction" => 0);
                foreach ($results as $n) {
                    // This aggregates the state types over the year e.g. CRITICAL
                    $year_status_agg[$n['state']]++;

                    // This aggregates the state for the week for the status graph per week
                    $week_status_summary[$n['state']]++;

                    // This aggregates the tags for the week for the tag graph per week
                    $week_tag_summary[$nagios_tag_category_map[$n['tag']]]++;

                    // This aggregates the tags down to a normalized version for display
                    $year_tag_agg_normalised[$nagios_tag_category_map[$n['tag']]]++;

                    // This is used to form a summary of normalized tags per user for the user graph
                    $year_user_tag_agg[$n['contact']][$nagios_tag_category_map[$n['tag']]]++;

                    // Count of Total alerts
                    $year_status_agg_total++;

                    // Counts up the tags per week for display
                    $year_tag_agg[$n['tag']]++;

                    // Count of Total statuses
                    $year_tag_agg_total++;

                    // Collect data for the top host and service
                    $year_top_host_agg[$n['hostname']]++;
                    $year_top_service_agg[$n['service']]++;

                    // Add all the timestamps into an array to make a heatmap of alerts over time
                    $year_time_counts[$n['timestamp']]++;

                    // Increment the day of week array for this alert 
                    $per_day_total[date('w', $n['timestamp'])]++;

                    // Sleep stats
                    $year_sleep_status_agg[$n['sleep_state']]++;
                    if ($n['mtts'] > 0 && $n['sleep_state'] > 0) {
                        $year_mtts_total = $year_mtts_total + $n['mtts'];
                        // Number of times person returned to sleep
                        $year_rtts_count++;
                    }
                    if ($n['mtts'] == 0 && $n['sleep_state'] > 0) {
                        // This indicates a 'no back to sleep' situation after an awakening
                        $year_ntts_count++;
                    }
                }

                // Add the week data to the two graphs that show data over weeks
                $status_graph_data[] = array(date("j M y", $week['range_start']), $week_status_summary["CRITICAL"], $week_status_summary["WARNING"], $week_status_summary["DOWN"], $week_status_summary["UNKNOWN"]);
                $tag_graph_data[] = array(date("j M y", $week['range_start']), $week_tag_summary["action"], $week_tag_summary["noaction"]);

                // Create a percentage based copy of the tag summary so we can see relative breakdown over time rather than absolute
                if ($week_tag_summary["action"] + $week_tag_summary["noaction"] > 0) {
                    $week_action_pct = ($week_tag_summary["action"]/ ($week_tag_summary["action"] + $week_tag_summary["noaction"]));
                    $week_noaction_pct = ($week_tag_summary["noaction"]/ ($week_tag_summary["action"] + $week_tag_summary["noaction"]));
                } else {
                    $week_action_pct = 0;
                    $week_noaction_pct = 0;
                }
                $tag_pct_graph_data[] = array(date("j M y", $week['range_start']), $week_action_pct, $week_noaction_pct);
                $user_tag_graph_data[] = array(date("j M y", $week['range_start']) . " {$n['contact']}", $week_tag_summary["action"], $week_tag_summary["noaction"]);


            }

            // Collect up the per day totals into the graph array
            ksort($per_day_total);
            foreach ($per_day_total as $d => $c) {
                // This seems a bit bizarre but there's no way to convert a DoW number to name? I could hardcode but
                // what if people want it in their own locale?
                // For yearly this is also average per day so it's divided by the number of weeks. 
                $per_day_graph_data[] = array(date('l', strtotime("Sunday +{$d} days")), $c / $num_weeks);
            }


            // Create the array for the alert types per user bar graph
            $user_graph_data[] = array("Person", "Action Taken", "No Action Taken");
            foreach ($year_user_tag_agg as $alert_user => $alert_tags) {
                $user_graph_data[] = array($alert_user, $alert_tags["action"] / $num_weeks, $alert_tags["noaction"] / $num_weeks);
            }

            $week_avg = round($year_total_notifications / $num_weeks, 2);
            echo "<p class='lead'>{$year_total_notifications} notifications received this year over {$num_weeks} weeks of data (average of {$week_avg} per week)</p>";
        ?>

        <h3>Alert Status Distribution</h3>
        <p>Breakdown of the type of notifications recieved during the last year</p>
            <?php 
                $html_status_summary = "";
                $html_status_bar = "";
                foreach ($year_status_agg as $type => $number) {
                    $pct = round( ($number / $year_status_agg_total) * 100, 2);
                    $html_status_summary .= "<span class='well well-small'><span class='label label-{$nagios_state_to_badge[$type]}'>{$type}</span>  {$number} ({$pct}%) </span>&nbsp;";
                    $html_status_bar .= "<div class='bar bar-{$nagios_state_to_bar[$type]}' style='width: {$pct}%;'></div>";
                }
            ?>
        <div class="progress input-xxlarge">
        <?php echo $html_status_bar ?>
        </div>
        <p><?php echo $html_status_summary ?></p>
        <br />

        <h3>Tag Status Summary</h3>
        <p>Breakdown of the tags applied to the notifications recieved during the last year</p>
            <?php
                $html_status_summary = "";
                foreach ($year_tag_agg as $type => $number) {
                    $pct = round( ($number / $year_tag_agg_total) * 100, 2);
                    $html_status_summary .= "<tr><td><span class='label'>{$nagios_alert_tags[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
            ?>
        <table class="table"><?php echo $html_status_summary ?></table>
        <p>Breakdown of the tags applied (normalised)</p>
             <?php
                $html_status_summary = "";
                foreach ($year_tag_agg_normalised as $type => $number) {
                    $pct = round( ($number / $year_tag_agg_total) * 100, 2);
                    $html_status_summary .= "<tr><td><span class='label'>{$nagios_tag_categories[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
            ?>
        <table class="table"><?php echo $html_status_summary ?></table>
        <br />

<?php if ( ($year_sleep_status_agg[1] > 0) || ($year_sleep_status_agg[0] > 0) ) { ?>
        <h3>Sleep State Summary</h3>
        <p>Breakdown of sleep states people were in during notifications this year</p>
             <?php
                $html_status_summary = "";
                foreach ($year_sleep_status_agg as $type => $number) {
                    $pct = round( ($number / $year_status_agg_total) * 100, 2);
                    $html_status_summary .= "<tr><td><i class='{$sleep_state_icons[$type]}'></i> <span class='label'>
                                             {$sleep_states[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
            ?>
        <table class="table"><?php echo $html_status_summary ?></table>

        <p class='lead'>Mean Time to Sleep: <i class='icon-time'> </i> 
            <?php echo round( ($year_mtts_total / $year_rtts_count) / 60, 2) ?> minutes</p>
        <p class='lead'>Time spent awake due to notifications this year: <i class='icon-time'> </i> 
            <?php echo round( ($year_mtts_total / 60 / 60), 2) ?> hours</p>
        <p class='lead'>Number of times sleep was abandoned: 
            <?php echo $year_ntts_count ?> times</p>
        <br />

<?php } ?>

        <h3>Top Notifying Hosts</h3>
        <p>Hosts that recieved the most notifications during this year</p>
        <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
            <th>Hostname</th><th>Count</th>
            </tr>
        </thead>
        <tbody> 
        <?php
            # Get the top 10
            arsort($year_top_host_agg);
            $year_top_host_agg = array_slice($year_top_host_agg, 0, 10);

            foreach($year_top_host_agg as $host_name => $number) {
                echo "<tr><td>{$host_name}</td><td>{$number}</td></tr>";
            }
        ?>
        </tbody>
        </table>

        <h3>Top Notifying Services</h3>
        <p>Services that recieved the most notifications during this year</p>
        <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
            <th>Service</th><th>Count</th>
            </tr>
        </thead>
        <tbody> 
        <?php
            arsort($year_top_service_agg);
            $year_top_service_agg = array_slice($year_top_service_agg, 0, 10);

            foreach($year_top_service_agg as $service_name => $number) {
                echo "<tr><td>{$service_name}</td><td>{$number}</td></tr>";
            }
        ?>
        </tbody>
        </table>



        <h3>Notification Time Map</h3>
        <p>This illustrates alerts received over the last year, in a heat map fashion. Hovering over the times
           gives you more detail about the number of alerts received. </p>
        <div id="cal-heatmap-year"></div>
        <script type="text/javascript">
        var year_time_data = <?php echo json_encode($year_time_counts) ?>;

        var year_cal = new CalHeatMap();
        year_cal.init({
            data: year_time_data,
            domain : "month",
            start: new Date(<?php echo date("U", strtotime("-1 year")) ?>*1000),
            subDomain : "day",
            id: "cal-heatmap-year",
            range : 13,
            itemName: ["notification", "notifications"],
            domainGutter: 2
        });
        </script>
        <br />

        <h3>Average Alert Volume per Day</h3>
        <p>What is the busiest day, alert volume wise? This is the average number of alerts received per day. </p>
        <div id="per_day_volume"></div>
        <br />

        <script type="text/javascript">
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($per_day_graph_data) ?>);

            var options = {
                hAxis: { title: 'Day of Week', gridlines: { count: 10 } },
                vAxis: { title: 'Number of alerts' }
            };

            var chart = new google.visualization.ColumnChart(document.getElementById('per_day_volume'));
            chart.draw(data, options);
        </script>


        <h3>Alert Types Over Time</h3>
        <p>The number of the difference type of alerts received over the last year</p>
        <div id="alert_types_chart"></div>
        <br />

        <script type="text/javascript">
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($status_graph_data) ?>);

            var options = {
                colors: ['#b94a48', '#f89406', '#333333', '#3a87ad'],
                hAxis: { title: 'Week Commencing' },
                vAxis: { title: 'Number of notifications' }
            };

            var chart = new google.visualization.LineChart(document.getElementById('alert_types_chart'));
            chart.draw(data, options);
        </script>

        <h3>Tag Summary Over Time</h3>
        <p>These graphs show your 'alert hygeine' over the past year. Hopefully, both the quantity and the
           percentage of 'No Action Taken' alerts decrease over time. </p>
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

        <script type="text/javascript">
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($tag_pct_graph_data) ?>);

            var options = {
                hAxis: { title: 'Week Commencing' },
                vAxis: { title: 'Percent of Notifications', format: '#%' },
                isStacked: true,
            };
            var formatter = new google.visualization.NumberFormat({pattern: '0.00%'});
            formatter.format(data, 1);
            formatter.format(data, 2);
            var chart = new google.visualization.AreaChart(document.getElementById('alert_tags_pct_chart'));
            chart.draw(data, options);
        </script>


        <h3>Average Tag Summary per Person</h3>
        <p>Breakdown of the average number of alerts received by each person whilst on call</p>
        <div id="user_tags_chart"></div>
        <br />

        <script type="text/javascript">
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($user_graph_data) ?>);

            var options = {
                hAxis: { title: 'Number of alerts by tag type', gridlines: { count: 10 } },
                vAxis: { title: 'Person' },
                isStacked: true,
            };

            var chart = new google.visualization.BarChart(document.getElementById('user_tags_chart'));
            chart.draw(data, options);
        </script>

        <h3>Weekly Tag Summary per Person</h3>
        <p>Breakdown of the tag type used per person for the last on call periods</p>
        <div id="weekly_user_tags_chart"></div>
        <br />

        <script type="text/javascript">
            var data = google.visualization.arrayToDataTable(<?php echo json_encode($user_tag_graph_data) ?>);

            var options = {
                hAxis: { title: 'Number of alerts by tag type', gridlines: { count: 10 } },
                vAxis: { title: 'Person/Week Commencing', textStyle: { fontSize: '12' } },
                isStacked: true,
                chartArea: {left: 200 },
                height: 800,
                legend: { position: 'bottom' }
            };

            var chart = new google.visualization.BarChart(document.getElementById('weekly_user_tags_chart'));
            chart.draw(data, options);
        </script>

    <? } ?>

    </div>

</div>

<? } ?>

