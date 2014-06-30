<?php

include_once("phplib/base.php");

$time_requested = getOrSetRequestedDate();

$start_end = getWeekRange($time_requested);
$start_ts = $start_end[0];
$end_ts = $start_end[1];


$page_title = getTeamName() . " Weekly Updates - Reports";
include_once('phplib/header.php');
include_once('phplib/nav.php');

?>

<style>
.icon-time {
    -moz-transform:scale(1.2,1.2); /* Firefox */
    -webkit-transform:scale(1.2,1.2); /* Safari and Chrome */
}
</style>

<div class="container">
<h1>On Call Reporting</h1>
<?php
if (getTeamConfig('oncall')) { 
?>
<ul id="myTab" class="nav nav-tabs">
    <li class="active"><a href="#week" report="week" data-toggle="tab">Week</a></li>
    <li><a href="#year" report="year" data-toggle="tab">Year</a></li>
</ul>

<div id="myTabContent" class="tab-content">
<div class="tab-pane fade active in" id="week"><div id="week-report-container"></div></div>
<div class="tab-pane fade" id="year"><div id="year-report-container"></div></div>
</div>

<script>
$('#week-report-container').html('<h2>Generating...</h2>');
var want_report = "week";
$.post("/generate_report.php", { type: want_report, date: '<?php echo $time_requested ?>' }).done(function(data) {
    $('#' + want_report + '-report-container').html(data);
});
</script>

<script>
$('a[data-toggle="tab"]').on('show', function (e) {
    $('#week-report-container').html('<h2>Generating...</h2>');
    $('#year-report-container').html('<h2>Generating...</h2>');
    var want_report = $(e.target).attr('report');
    $.post("/generate_report.php", { type: want_report, date: '<?php echo $time_requested ?>' }).done(function(data) {
       $('#' + want_report + '-report-container').html(data);
    });

})
</script>
<?php } else {
    echo "<p class='lead'>This feature is disabled because you have disabled 'on call' support. Please enable it and try again. </p>";
}
?>
</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
