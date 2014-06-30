<div class="navbar navbar-inverse navbar-static-top">
 <div class="navbar-inner">
    <div class="container">
      <a class="brand" href="<?=$ROOT_URL;?>/"><?=getTeamName()?>weekly</a>
      <ul class="nav">
        <?php
            printHeaderNav();
        ?>
      </ul>
      <form class="navbar-search pull-left" action="<?=$ROOT_URL;?>/search.php" method="get">
        <input id="nav-search" type="text" name="query" class="search-query" placeholder="Search Reports and Notifications" value="<?php echo $query ?>">
        <script>
            $("#nav-search").on("click").popover({
                placement: "bottom",
                trigger: "focus",
                html: true,
                title: "Search Tips",
                content: "Default 'best effort' search, or: </br><small>" 
                    + "<li><b>service: query</b> - Search for alerts by service name</li>"
                    + "<li><b>host: query</b> - Search for alerts by host name</li>"
                    + "<li><b>report: query</b> - Search weekly reports</li>"
                    + "<li><b>meeting: query</b> - Search meeting notes</li></small>"
            });


        </script>
      </form>
      <ul class="nav pull-right">
      <?php if (isset($end_ts)) { ?>
        <li class="dropdown">
            <a href="#" id="dLabel" role="button" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-calendar icon-white"></i> Set Date<b class="caret"></b></a>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dLabel">
                <form id="setdate" action="<?php echo $_SERVER['PHP_SELF'] ?>" method="post">
                <div id="menu-datepicker" data-date="<?php echo date('m/d/y', $end_ts) ?>"><input id="picked-date" type="hidden" name="date" value=""></div>
                <script>
                    $('#menu-datepicker').datepicker({ format: "mm/dd/yy", todayHighlight: true, weekStart: 1 })
                        .on('changeDate', function(e) {
                        $('input[name=date]').val(e.date.toString());
                        $('#setdate').submit();
                    });
                </script>
                </form> 
            </ul>
        </li>
      <?php } ?>

        <li class="dropdown">
            <a href="#" id="dTZ" role="button" class="dropdown-toggle" data-toggle="dropdown">
                <i class="icon-time icon-white"></i> Timezone<b class="caret"></b>
            </a>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dTZ">
                <li><a tabindex="-1" href="set_locale.php?l=UK">London, UK</a></li>
                <li><a tabindex="-1" href="set_locale.php?l=ET">Brooklyn, NY</a></li>
                <li><a tabindex="-1" href="set_locale.php?l=PT">San Francisco, CA</a></li>
            </ul>
        </li>
        <li class="dropdown">
            <a href="/profile.php" id="dProfile" role="button" class="dropdown-toggle" data-toggle="dropdown">
                <i class="icon-user icon-white"></i> <?php echo getUsername() ?><b class="caret"></b>
            </a>
            <ul class="dropdown-menu" role="menu" aria-labelledby="dProfile">
                <li><a tabindex="-1" href="<?=$ROOT_URL;?>/profile.php">View Profile</a></li>
                <li><a tabindex="-1" href="<?=$ROOT_URL;?>/edit_profile.php">Edit Profile</a></li>
            </ul>
        </li>

      </ul>
    </div>
  </div>

</div>
