<?php

include_once("phplib/base.php");

$my_username = getUsername();

$page_title = getTeamName() . " Weekly Updates - Edit your profile";
include_once('phplib/header.php');
include_once('phplib/nav.php');

?>

<div class="container">
<h1>Edit profile <small>for user <?php echo $my_username; ?></small></h1>

<?php

if (!$profile = checkForUserProfile($my_username)) {
    echo insertNotify("info", "Welcome to your profile page! Fill in the data below");
    $profile = null;
} else {
    $sleeptracking_settings = json_decode($profile['sleeptracking_settings'], 1);
}

if (isset($_GET['succ'])) {
    echo insertNotify("success", "Thanks! Your profile details have been saved. ");
}
?>

<div class="row">
    <div class="span12">

        <form method='POST' action='<?php echo $ROOT_URL; ?>/save_profile.php'>
            <fieldset>
            <legend>User details</legend>
            <label>Full Name</label><input type="text" placeholder="Bob Robertson" name="full_name" value="<?php echo $profile['full_name']; ?>">
            <label>Jira Username</label><input type="text" placeholder="username@yourcompany.com" name="jira_username" value="<?php echo $profile['jira_username']; ?>">
            <label>GitHub Username</label><input type="text" placeholder="git_username" name="github_username" value="<?php echo $profile['github_username']; ?>">
            <label>Bitbucket Username</label><input type="text" placeholder="bitbucket_username" name="bitbucket_username" value="<?php echo $profile['bitbucket_username']; ?>">
            <label>Time Zone</label>
            <select name="timezone">
                <option></option>
                <?php
                    foreach($locales as $l => $d) {
                        $checked = ($profile['timezone'] == $l) ? " selected" : "";
                        echo "<option value='{$l}'{$checked}>{$d}</option>";
                    }
                ?>
            </select>
            </fieldset>
            <br />
            <fieldset>
            <legend>Sleep Tracking</legend>
            <label class="radio">
                <input type="radio" name="sleeptracking_provider" id="sleeptracking-none" value="none" checked>
                No sleep tracking - I hate my life and my sleep doesn't matter to me
            </label>
            <?php
                foreach ($sleep_providers as $provider_id => $p_config) {
                    $checked = ($profile['sleeptracking_provider'] == $provider_id) ? " checked" : "";
                    echo '<label class="radio">';
                    echo "<input type='radio' name='sleeptracking_provider' id='sleeptracking-{$provider_id}' value='{$provider_id}'{$checked}>";
                    echo "<img src='{$ROOT_URL}{$p_config['logo']}'> <strong>{$p_config['display_name']}</strong> - {$p_config['description']}";
                    echo "</label>";
                    echo "<div class='well hide options_div' id='{$provider_id}-options'>";
                    foreach ($p_config['options'] as $option_id => $o_config) {
                        echo "<label><strong>{$o_config['name']}</strong></label>";
                        echo "<input type='{$o_config['type']}' name='sleeptracking[{$provider_id}][{$option_id}]' placeholder='{$o_config['placeholder']}' 
                            data-toggle='tooltip' title='{$o_config['description']}' value='{$sleeptracking_settings[$provider_id][$option_id]}'>";
                    }
                    echo "</div>";
                }
            ?>
            </fieldset>

            <div class="form-actions">
                <button type="submit" class="btn btn-primary">Save changes</button>
                <button type="button" class="btn">Cancel</button>
            </div>
        </form>
        <script>
            // Show/hide the options windows as the option is chosen
            $("input:radio").click(function() {
                $(".options_div").fadeOut("fast");
                var show_div = "#" + $(this).val() + "-options";
                $(show_div).fadeIn("fast");
            });
            $("input:text").tooltip({
                placement: "right",
                trigger: "focus"
            });
        </script>
    </div>
</div>
<?php include_once('phplib/footer.php'); ?>
</body>
</html>
