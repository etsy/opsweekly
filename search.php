<?php

include_once("phplib/base.php");

$query = ((isset($_GET['query'])) && ($_GET['query'] != "")) ? mysql_real_escape_string($_GET['query']) : null;

if ($query) {
    // Try and pick out our filters
    if (preg_match('/^((?P<context>\w+):\s)?(?P<term>.*)/i', $query, $matches) === false) {
        $header = "Search Failed";
        $no_search = true;
    } else {
        $term = $matches['term'];
        $context = $matches['context'];
        $page = isset($_GET['page']) ? ((int) $_GET['page']) : 1;

        // Match on the 'context' for special searches that are more specific. 
        switch($context) {
        case 'service':
        case 'host':
            $format_filter = ($context == 'host') ? 'hostname' : 'service';
            $title = "{$context} '{$term}'";
            $header = "Results from on call reports for {$context} '{$term}'";
            if (!$results = handleSearch($context, $term)) break;
            $html = formatSearchResults($results, $format_filter, $term, $search_results_per_page,($page-1)*$search_results_per_page);
            $html .= formatPagination($page, count($results));
            // Stats calculation for on call items
            foreach ($results as $n) {
                $status_agg[$n['state']]++;
                $status_agg_total++;
                $tag_agg_normalised[$nagios_tag_category_map[$n['tag']]]++;
                $tag_agg[$n['tag']]++;
                $tag_agg_total++;
                $time_counts[$n['timestamp']]++;
            }
            break;
        case 'report':
            $title = "report '{$term}'";
            $header = "Results from weekly reports for '{$term}'";
            if (!$results = handleSearch('generic_reports', $term)) break;
            $html = formatSearchResults($results, 'report', $term, $search_results_per_page,($page-1)*$search_results_per_page);
            $html .= formatPagination($page, count($results));
            break;
        case 'meeting':
            $title = "meeting notes containing '{$term}'";
            $header = "Results from meeting notes for '{$term}'";
            if (!$results = handleSearch('meeting_notes', $term)) break;
            $html = formatSearchResults($results, 'notes', $term, $search_results_per_page,($page-1)*$search_results_per_page);
            $html .= formatPagination($page, count($results));
            break;
        default:
            $everything = true;
            $title = $term = $matches[0];
            $header = "Results from everything for '{$term}'";
            // A little bit of everything. 
            if ($results = handleSearch('service', $term)) {
                $html = "<h3>Services</h3>";
                $html .= formatSearchResults($results, 'service', $term, 5);
                $html .= printMoreSearchTypeButton('service', $term);
            }
            if ($results = handleSearch('host', $term)) {
                $html .= "<h3>Hosts</h3>";
                $html .= formatSearchResults($results, 'hostname', $term, 5);
                $html .= printMoreSearchTypeButton('host', $term);
            }
            if ($results = handleSearch('generic_reports', $term)) {
                $html .= "<h3>Weekly Reports</h3>";
                $html .= formatSearchResults($results, 'report', $term, 3);
                $html .= printMoreSearchTypeButton('report', $term);
            }
            if ($results = handleSearch('meeting_notes', $term)) {
                $html .= "<h3>Meeting Notes</h3>";
                $html .= formatSearchResults($results, 'notes', $term, 2);
                $html .= printMoreSearchTypeButton('meeting', $term);
            }

            break;
        }
    }

} else {
    $header = "Search";
    $no_search = true;
}


$page_title = getTeamName() . " Weekly Updates - Search results for {$title}";
include_once('phplib/header.php');
include_once('phplib/nav.php');

?>

<div class="container">
<h1><?php echo $header ?></h1>
<div class="row">
    <div class="span12">
    <?php
    if ($no_search) {
        echo insertNotify("error", "No search term was entered, please enter something to search for. ");
        echo insertNotify("info", "<b>Tips:</b> You can either enter a phrase for a best effort search of weekly reports and on call data, or 
            specific host/service alerts. Click the search box for more information");
    } else {
        if ($html) {
            if (!$everything) {
                echo "<p class='lead'>" . count($results). " results total</p>";
            }


            echo $html;
            // Stats for on call items
            if ($context == 'service' || $context == 'host') {
                echo "<h3>Stats for these search results</h3>";
                $stats_html = "<h4>Alert Status Distribution</h4>";
                foreach ($status_agg as $type => $number) {
                    $pct = round( ($number / $status_agg_total) * 100, 2);
                    $html_status_summary .= "<span class='well well-small'><span class='label label-{$nagios_state_to_badge[$type]}'>{$type}</span>  {$number} ({$pct}%) </span>&nbsp;";
                    $html_status_bar .= "<div class='bar bar-{$nagios_state_to_bar[$type]}' style='width: {$pct}%;'></div>";
                }
                $stats_html .= "<div class='progress input-xxlarge'>{$html_status_bar}</div><p>{$html_status_summary}</p><br />";

                $stats_html .= "<h4>Tag Status Summary</h4><p>Breakdown of the tags applied to the notifications in this search</p><table class='table'>";
                foreach ($tag_agg as $type => $number) {
                    $pct = round( ($number / $tag_agg_total) * 100, 2);
                    $stats_html .= "<tr><td><span class='label'>{$nagios_alert_tags[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
                $stats_html .= "</table><p>Breakdown of the tags applied (normalised)</p><table class='table'>";
                foreach ($tag_agg_normalised as $type => $number) {
                    $pct = round( ($number / $tag_agg_total) * 100, 2);
                    $stats_html .= "<tr><td><span class='label'>{$nagios_tag_categories[$type]}</span></td> <td> {$number} ({$pct}%) </td></tr>";
                }
                $stats_html .= "</table><br />";
                echo $stats_html;

                ?>

                <h3>Notification Time Map</h3>
                <p>Grids read from top to bottom through hours, the darker the more alerts were recieved <small>(Hover over the blocks for a count)</small></p>
                <div id="cal-heatmap"></div>
                <script type="text/javascript">
                var time_data = <?php echo json_encode($time_counts) ?>;

                var cal = new CalHeatMap();
                cal.init({
                    data: time_data,
                    domain : "month",
                    start: new Date(<?php echo date("U", strtotime("-1 year")) ?>*1000),
                    subDomain : "day",
                    range : 13,
                    itemName: ["notification", "notifications"],
                    domainGutter: 2
                });
                </script>
                <br />

                <?
            }

        } else {
            echo insertNotify("error", "Nothing was found for that search. If you believe this is in error, you may be right. Complain to the developer. ");
        }
    }


    ?>
    </div>


</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
