<?php

// Called from various places using Ajax to produce a HTML report of type 'week' or 'year'
// which summarises the on call data stored about those time periods.
// TODO: I'm really sorry. This should be much much smaller and broken down into many small functions.

include_once("phplib/base.php");

// Get the period requested, and the type of report
$time_requested = (isset($_POST['date'])) ? $_POST['date'] : "now";
$report_type = (isset($_POST['type'])) ? $_POST['type'] : "week";

// Resolve that time to a weekly and on call period
$start_end = getWeekRange($time_requested);
$start_ts = $start_end[0];
$end_ts = $start_end[1];

$oncall_period = getOnCallWeekRange($time_requested); 
$oncall_start = $oncall_period[0];
$oncall_end = $oncall_period[1];

// Figure out the report type and do the neccessary calculations based on the report type

if ($report_type == "week") { // Weekly report.. Pretty simple, get the week, process data.
    $pretty_start = date("l jS F Y", $oncall_start);
    $pretty_end = date("l jS F Y", $oncall_end);

    // First, check the week exists/there is data. 
    if($results = getOnCallReportForWeek($oncall_start, $oncall_end)) {
        $total_notifications = count($results);

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

        // We have data! Report rendering time.
        require_once 'week_report.php';

    } else { // There is no on call data for this period
        $error = "There is no on call report for the period {$pretty_start} - {$pretty_end} yet. Choose a different week in the nav bar.";
        echo insertNotify("error", $error);
    }

} elseif ($report_type == "year") {

    // For a year report, we get every week for the last year from the database. 
    $weeks = getAvailableOnCallRangesForLastYear();
    $num_weeks = count($weeks);

    // Initialise variables used for calculations
    $year_status_total = array();
    $year_tag_total = array();
    $year_top_host_agg = array();
    $year_top_service_agg = array();
    $year_top_waking_service = array();
    $year_top_waking_host = array();
    $year_time_counts = array();
    $year_status_agg_total = 0;
    $year_tag_agg_total = 0;
    $year_sleep_status_agg = array();
    $year_mtts_total = 0;
    $year_rtts_total = 0;
    $year_ntts_count = 0;
    $year_week_most_pages = 0;
    $year_week_fewest_pages = 0;
    $per_day_total = array();
    $per_day_graph_data[] = array("Day of Week", "Alerts");

    // Graph headers
    $status_graph_data[] = array("Week", "Critical", "Warning", "Down", "Unknown");
    $tag_graph_data[] = array("Week", "Action Taken", "No Action Taken");
    $tag_pct_graph_data[] = array("Week", "Action Taken", "No Action Taken");
    $user_tag_graph_data[] = array("Week/Person", "Action Taken", "No Action Taken");

    if ($num_weeks > 2) { // Require at least 2 weeks of data for yearly report
        foreach ($weeks as $week) {
            $results = getOnCallReportForWeek($week['range_start'], $week['range_end']);
            $year_total_notifications = $year_total_notifications + count($results);
            if (count($results) > $year_week_most_pages) {
                $year_week_most_pages = count($results);
            }
            if (count($results) < $year_week_fewest_pages) {
                $year_week_fewest_pages = count($results);
            }

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
                    // Keep a summary count in an array of the top waking hosts and services
                    $year_top_waking_service[$n['service']]++;
                    $year_top_waking_host[$n['hostname']]++;
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

        // Data ahoy, time to render.
        require_once 'year_report.php';
    } else { // If there wasn't more than 2 weeks, we're down here: if ($num_weeks > 2)
        echo insertNotify("error", "There is not enough data to generate this report yet! You must have at least two weeks of data.");
    }

} else { // Just in case the report type is something weird.
    insertNotify("error", "You want WHAT type of report?! I have no idea what {$report_type} is.");
}

