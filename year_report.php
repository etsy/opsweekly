<div class="row">
    <div class="span12">
        <h2>Overall stats <small> for last year </small></h2>
        <p class='lead'><?php echo $year_total_notifications; ?> notifications received this year 
            over <?php echo $num_weeks; ?> weeks of data (average of <?php echo $week_avg; ?> per week)<br>
            Most pages in one week: <?php echo $year_week_most_pages; ?><br>
            Fewest pages in one week: <?php echo $year_week_fewest_pages; ?></p>
        <h3>Alert Status Distribution</h3>
        <p>Breakdown of the type of notifications received during the last year</p>
        <?php echo renderStatusProgressBar($year_status_agg, $year_status_agg_total); ?>
        <br />

        <h3>Tag Status Summary</h3>
        <p>Breakdown of the tags applied to the notifications received during the last year</p>
        <?php echo renderTagTable($year_tag_agg, $year_tag_agg_total, $nagios_alert_tags) ?>

        <p>Breakdown of the tags applied (normalised)</p>
        <?php echo renderTagTable($year_tag_agg_normalised, $year_tag_agg_total, $nagios_tag_categories) ?>
        <br />

        <?php
        if ( ($year_sleep_status_agg[1] > 0) || ($year_sleep_status_agg[0] > 0) ) { ?>
            <h3>Sleep State Summary</h3>
            <p>Breakdown of sleep states people were in during notifications this year</p>
            <?php
            echo renderSleepStatus($year_sleep_status_agg, $year_status_agg_total, $year_mtts_total, $year_rtts_count, $year_ntts_count);
        }
        ?>
        <br />

        <h3>Top Notifying Hosts</h3>
        <p>Hosts that received the most notifications during this year</p>
        <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
            <th>Hostname</th><th>Count</th>
            </tr>
        </thead>
        <tbody> 
        <?php
            echo renderTopNTableBody($year_top_host_agg, 10, 'host');
        ?>
        </tbody>
        </table>
        <?php if (count($year_top_waking_host) > 1) { ?>
        <p>The top 5 hosts that woke people up were:
            <?php echo renderTopNPrettyLine($year_top_waking_host) ?>
        </p>
        <?php } ?>

        <h3>Top Notifying Services</h3>
        <p>Services that received the most notifications during this year</p>
        <table class="table table-striped table-bordered table-hover">
        <thead>
            <tr>
            <th>Service</th><th>Count</th>
            </tr>
        </thead>
        <tbody> 
        <?php
            echo renderTopNTableBody($year_top_service_agg, 10, 'service');
        ?>
        </tbody>
        </table>
        <?php if (count($year_top_waking_service) > 1) { ?>
        <p>The top 5 services that woke people up were:
            <?php echo renderTopNPrettyLine($year_top_waking_service) ?>
        </p>
        <?php } ?>



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

    </div>

</div>
