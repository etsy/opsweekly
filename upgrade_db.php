<?php

# This is a crude script to be run to check that all the updates made to
# the database schema in various commits have been run, in case people
# are continously upgrading their version from Github.
# Ideally we'd have proper versioning and upgrade scripts but there you go.
# This is better than nothing at least...

include_once("phplib/base.php");
$page_title = "Applying schema changes";
include_once('phplib/header.php');
include_once('phplib/nav.php');


$schema_changes = array(
    "hide_event" => array(
        "commit" => "ba15e454c717fdc584457991b569c812eb936037",
        "name" => "hide_event",
        "description" => "Adds a column that allows events to be hidden",
        "command" => "ALTER IGNORE TABLE oncall_weekly ADD COLUMN hide_event tinyint(1) NOT NULL DEFAULT 0;"),
    "profile_jira_username" => array(
        "commit" => "9e1bb46ffe7b7ee84310999995870292b6003ab7",
        "name" => "profile_jira_username",
        "description" => "Adds a column to store the JIRA username, in case it differs from the logged in username",
        "command" => "ALTER IGNORE TABLE user_profile ADD COLUMN jira_username varchar(255) NOT NULL;"),
    "profile_github_username" => array(
        "commit" => "529ee6e63c5b138442b57264774778b6d9c94e76",
        "name" => "profile_github_username",
        "description" => "Adds a column to store the Github username, in case it differs from the logged in username",
        "command" => "ALTER IGNORE TABLE user_profile ADD COLUMN github_username varchar(255) NOT NULL;"),
    "profile_bitbucket_username" => array(
        "commit" => "f8d9f6a4a4138a1e6e5cc767706f595098ba550e",
        "name" => "profile_github_username",
        "description" => "Adds a column to store the BitBucket username, in case it differs from the logged in username",
        "command" => "ALTER IGNORE TABLE user_profile ADD COLUMN bitbucket_username varchar(255) NOT NULL;"),

    // Please add any other schema changes here
);
?>
<div class="container">
<h1>Applying Schema Changes</h1>

<ul>

<?php

$mysql_db = getTeamConfig('database');

echo "<li>Changes will be executed on database '{$mysql_db}'</li>";

foreach($schema_changes as $k => $v) {
    echo "<li>Applying {$k}...</li><ul>";
    echo "<li>From commit {$v['commit']}, purpose: {$v['description']}</li>";
    echo "<li>Running SQL: <pre>{$v['command']}</pre></li>";
    db::prepare_and_execute($v['command']);
    echo "<li>Run. </li></ul>";
}

?>
</ul>
</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
