<?php

include_once("phplib/base.php");


$user_requested = (isset($_POST['username'])) ? $_POST['username'] : getUsername();

$page_title = getTeamName() . " Weekly Updates - Updates for {$user_requested}";
include_once('phplib/header.php');
include_once('phplib/nav.php');

?>

<div class="container">
<h1>All Weeekly Updates <small>for user <?= $user_requested ?></small></h1>
<div class="row">
    <div class="span9">
    <?php 
        $updates = getGenericWeeklyReportsForUser($user_requested);
        if (!$updates) {
            echo insertNotify("error", "This user doesn't appear to have any weekly updates!");
        } else {
            foreach ($updates as $update) {
                $pretty_time = getPrettyTime($update['timestamp']);
                $ending_date = date("l jS F Y", $update['range_end'] );
                echo "<h3>Week ending {$ending_date} <small>written {$pretty_time}</small></h3>";
                echo "<div class='well well-small'><p>{$update['report']}</p></div>";
            }
        }
    ?>
    </div>

    <div class="span3">
    <h4>Choose person</h3>
    <form id="setperson" action="user_updates.php" method="post">
    <select name="username" onchange="this.form.submit()"><option></option>
        <?php 
            $list_of_users = getListOfPeopleWithReports();
                foreach($list_of_users as $user) {
                    $selected = ($user == $user_requested) ? " selected" : "";
                    echo "<option{$selected}>{$user}</option>";
            }
        ?>
    </select>
    </form>

    </div>


</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
