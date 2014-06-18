<div class="row">
    <div class="span12">
        <h2>Week Stats <small> for week <?= $pretty_start ?> - <?= $pretty_end ?></small></h2>
        <p class='lead'><?= $total_notifications ?> notifications received this week </p>
        <h3>Alert Status Distribution</h3>
        <p>Breakdown of the type of notifications recieved during the week</p>
        <?php echo renderStatusProgressBar($week_status_agg, $week_status_agg_total); ?>
        <br />

        <h3>Tag Status Summary</h3>
        <p>Breakdown of the tags applied to the notifications recieved during the week</p>
        <?php echo renderTagTable($week_tag_agg, $week_tag_agg_total, $nagios_alert_tags) ?>

        <p>Breakdown of the tags applied (normalised)</p>
        <?php echo renderTagTable($week_tag_agg_normalised, $week_tag_agg_total, $nagios_tag_categories) ?>
        <br />

        <?php
        if ( ($week_sleep_status_agg[1] > 0) || ($week_sleep_status_agg[0] > 0) ) { ?>
            <h3>Sleep State Summary</h3>
            <p>Breakdown of sleep states person was in during notifications this week</p>
            <?php
            echo renderSleepStatus($week_sleep_status_agg, $week_status_agg_total, $week_mtts_total, $week_rtts_count, $week_ntts_count);
        }
        ?>
        <br />

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
            echo renderTopNTableBody($week_top_host_agg);
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
            echo renderTopNTableBody($week_top_service_agg);
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
