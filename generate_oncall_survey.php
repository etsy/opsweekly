<?php 

// Handle a time period requested other than now
$time_requested = (isset($_GET['date'])) ? $_GET['date'] : "now";

include_once('phplib/base.php');
$my_username = getUsername();
$start_end = getOnCallWeekRange($time_requested);
$start_ts = $start_end[0];
$end_ts = $start_end[1];

// Get the alerts with timezone data so it matches the users expectations
$oncall_start_end = getOnCallWeekRangeWithTZ($time_requested);
$oncall_start_ts = $oncall_start_end[0];
$oncall_end_ts = $oncall_start_end[1];
?>


<form action="<?php echo $ROOT_URL; ?>/add_oncall_weekly.php" method="POST" class="form-horizontal">
    <h3>Alerts received this week (<?php echo date("l jS F Y", $start_ts) . " - " . date("l jS F Y", $end_ts) ?>)</h3>
    <table class="table table-striped table-bordered table-hover">
    <thead>
        <tr>
        <th>Date/Time</th><th>Host</th><th>Service</th><th>Output</th><th>State</th><th>Bulk Change</th><th>Hide Event?</th>
        </tr>
    </thead>
    <tbody>
    <?php echo printOnCallNotifications($my_username, $start_ts, $end_ts, $oncall_start_ts, $oncall_end_ts); ?>
    </tbody>
    </table>

<p><span class="label label-warning">Warning!</span> Please submit this section using the button below before saving or emailing your weekly report</p>
<button class="btn btn-primary" type="submit">Save On-Call Report</button>
</form>

<div id='bulk-change-navbar' class='navbar navbar-fixed-bottom' style='display: none'>
    <div class="navbar-inner">
    <form class="navbar-form pull-right">
    <select id='bulk-edit' class='input-xlarge'>
        <option>Bulk Change Tags</option>
    <?php
        foreach ($nagios_alert_tags as $tag => $tag_name) {
            echo "<option value='{$tag}'{$selected}>{$tag_name}</option>";
        }
    ?>
    </select>
    </div>
    <script>
        $('.bulk-check').change(function () {
            if ( $(".bulk-check:checked").length > 0 ) {
                $("#bulk-change-navbar").fadeIn('fast').effect("highlight");
                $("#bulk-edit").effect("highlight", {}, 1000);
            } else {
                $("#bulk-change-navbar").fadeOut('fast');
            }
        });
        $('#bulk-edit').change(function () {
            $(".bulk-check:checked").each(function () {
                field_num = ($(this).data("num"));
                new_value = $('#bulk-edit').val();
                $('select[name="oncall[notifications][not_' + field_num + '][tag]"]').val(new_value);
                $(this).attr('checked', false);
            });
            $(this).val(0);
            $("#bulk-change-navbar").fadeOut('fast');
        });
    </script>
    </form>
    </div>
</div>
