<?php
require_once('config/version.php');
require_once('config/ircddblocal.php');
require_once('config/language.php');
$configs = array();
if ($configfile = fopen($gatewayConfigPath,'r')) {
        while ($line = fgets($configfile)) {
                list($key,$value) = explode("=",$line);
                $value = trim(str_replace('"','',$value));
                if ($key != 'ircddbPassword' && strlen($value) > 0)
                $configs[$key] = $value;
        }

}
$progname = basename($_SERVER['SCRIPT_FILENAME'],".php");
$rev=$version;
$MYCALL=strtoupper($callsign);

//Load the Pi-Star Release file
$pistarReleaseConfig = '/etc/pistar-release';
$configPistarRelease = array();
$configPistarRelease = parse_ini_file($pistarReleaseConfig, true);

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" lang="en">
<head>
    <meta name="robots" content="index" />
    <meta name="robots" content="follow" />
    <meta name="language" content="English" />
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <?php echo "<meta name=\"generator\" content=\"$progname $rev\" />\n"; ?>
    <meta name="Author" content="" />
    <meta name="Description" content="Pi-Star Dashboard" />
    <meta name="KeyWords" content="" />
    <meta http-equiv="cache-control" content="max-age=0" />
    <meta http-equiv="cache-control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="expires" content="0" />
    <meta http-equiv="pragma" content="no-cache" />
    <link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon">
    <title><?php echo "BH4FCZ"." - ".$lang['digital_voice']." ".$lang['dashboard'];?></title>
  <meta http-equiv="Content-Security-Policy" content="default-src * 'self' 'unsafe-inline' 'unsafe-eval' data: gap: content:">
  <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, minimum-scale=1, user-scalable=no, minimal-ui, viewport-fit=cover">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="theme-color" content="#2196f3">
  <meta name="format-detection" content="telephone=no">
  <meta name="msapplication-tap-highlight" content="no">
  <meta name="apple-mobile-web-app-capable" content="yes">

  <link rel="stylesheet" href="framework7/css/framework7.min.css">
  <link rel="stylesheet" href="framework7/css/framework7-icons.css">

  <link rel="stylesheet" type="text/css" href="css/mini_ircddb.css">

  <link rel="stylesheet" href="css/icons.css">
  <link rel="stylesheet" href="css/app.css">

  <link rel="apple-touch-icon" href="mobileicon.png">
  <link rel="apple-touch-icon" sizes="152x152" href="mobileicon.png">
  <link rel="apple-touch-icon" sizes="180x180" href="mobileicon.png">
  <link rel="apple-touch-icon" sizes="167x167" href="mobileicon.png">

   <script type="text/javascript" src="jquery.min.js"></script>
   <script type="text/javascript" src="functions.js"></script>
   <script type="text/javascript">
      $.ajaxSetup({ cache: false });
   </script>
   <link href="featherlight.css" type="text/css" rel="stylesheet" />
   <script src="featherlight.js" type="text/javascript" charset="utf-8"></script>

   <style>
   .ios .block{margin:0px 0;padding:0 15px;}
   .ios .block-strong{color:#000;background:#fff;padding:0px 2px;}
   .block-strong{margin:0px 0;padding:0 15px;}
   .block-strong{color:#000;background:#fff;padding:0px 2px;}
   </style>

</head>
<body>
  <div id="app">
    <!-- Status bar overlay for fullscreen mode-->
    <div class="statusbar"></div>
    <!-- Left panel with cover effect-->
    <div class="panel panel-left panel-cover theme-dark">
      <div class="view">
        <div class="page">
          <div class="navbar">
            <div class="navbar-inner">
              <div class="title">Status</div>
            </div>
          </div>
          <div class="page-content">
           <?php
			  // First lets figure out if we are in MMDVMHost mode, or dstarrepeater mode;
if (file_exists('/etc/dstar-radio.mmdvmhost')) {
	include '/config/config.php';					// MMDVMDash Config
	include '/mmdvmhost/tools.php';					// MMDVMDash Tools
	//include 'mmdvmhost/functions.php';				// MMDVMDash Functions

	//echo '<div class="nav">'."\n";					// Start the Side Menu
	echo '<script type="text/javascript">'."\n";
	echo 'function reloadRepeaterInfo(){'."\n";
	echo '  $("#repeaterInfo").load("../mmdvmhost/repeaterinfo.php");'."\n";
	echo '}'."\n";
	echo 'setInterval(function(){reloadRepeaterInfo()}, 1000);'."\n";
	echo '$(window).trigger(\'resize\');'."\n";
	echo '</script>'."\n";
	echo '<div id="repeaterInfo">'."\n";
	include '../mmdvmhost/repeaterinfo.php';				// MMDVMDash Repeater Info
	echo '</div>'."\n";
	//echo '</div>'."\n";
}
			  ?>
          </div>
        </div>
      </div>
    </div>
    <!-- Right panel with reveal effect-->
    <div class="panel panel-right panel-reveal theme-dark">
      <div class="view">
        <div class="page">
          <div class="navbar">
            <div class="navbar-inner">
              <div class="title">Hardware</div>
            </div>
           </div>
          <div class="page-content">
            <div style="font-size:12px; text-align: left; padding-left: 12px; float: left;">Hostname: <strong><?php echo exec('cat /etc/hostname'); ?></strong></div><div style="font-size: 12px; text-align: left; padding-left: 12px;"><br />Pi-Star:<?php echo $configPistarRelease['Pi-Star']['Version']?> / <?php echo $lang['dashboard'].": ".$version; ?><br />Mobile Dashboard: <?php echo $mobile?></div>

			 <?php

			echo '<script type="text/javascript">'."\n";
			echo 'function reloadSysInfo(){'."\n";
			echo '  $("#sysInfo").load("system.php");'."\n";
			echo '}'."\n";
			echo 'setInterval(function(){reloadSysInfo()}, 15000);'."\n";
			echo '$(window).trigger(\'resize\');'."\n";
			echo '</script>'."\n";
			echo '<div id="sysInfo">'."\n";
			include 'system.php';				// Basic System Info
			echo '</div>'."\n";
			//echo '</div>'."\n";

			?>
          </div>
        </div>
      </div>
    </div>

    <!-- Views/Tabs container -->
    <div class="views tabs ios-edges">
      <!-- Tabbar for switching views-tabs -->
      <div class="toolbar tabbar-labels toolbar-bottom-md">
        <div class="toolbar-inner">
          <a href="#view-home" class="tab-link tab-link-active">
			<i class="f7-icons size-30">list</i>
  			<span class="icon-name">Dashboard</span>
            <span class="tabbar-label">Dashboard</span>
          </a>
          <a href="#view-admin" class="tab-link">
            <i class="f7-icons size-30">drawers</i>
  			<span class="icon-name">Admin</span>
            <span class="tabbar-label">Admin</span>
          </a>
          <a href="#view-settings" class="tab-link">
			<i class="f7-icons size-30">settings</i>
  			<span class="icon-name">Settings</span>
            <span class="tabbar-label">Settings</span>
          </a>
          <a href="#view-power" class="tab-link">
  			<i class="f7-icons size-30">bolt</i>
  			<span class="icon-name">Power</span>

            <span class="tabbar-label">Power</span>
          </a>
        </div>
      </div>
      <!-- Your main view/tab, should have "view-main" class. It also has "tab-active" class -->
      <div id="view-home" class="view view-main tab tab-active">
        <!-- Page, data-name contains page name which can be used in page callbacks -->
        <div class="page" data-name="home">
          <!-- Top Navbar -->
          <div class="navbar">
            <div class="navbar-inner">
              <div class="left">
                <a href="#" class="link icon-only panel-open" data-panel="left">
                  <i class="f7-icons size-30">menu</i>

                </a>
              </div>
              <div class="title sliding">Pi-Star <?php echo " - ".$MYCALL; ?></div>
              <div class="right">
                <a href="#" class="link icon-only panel-open" data-panel="right">
                  <i class="f7-icons size-30">menu</i>

                </a>
              </div>
            </div>
          </div>

          <!-- Scrollable page content-->
          <div class="page-content">
            <div class="block block-strong">
            <br />

            <?php

			echo '<script type="text/javascript">'."\n";
	echo 'function reloadLastHeardDetails(){'."\n";
	echo '  $("#lastHerdDetails").load("lh_details.php");'."\n";
	echo '  $("#lastHerdDetails3").load("lh_livedisplay.php");'."\n";
	echo '}'."\n";
	echo 'setInterval(function(){reloadLastHeardDetails()}, 1500);'."\n";
	echo '$(window).trigger(\'resize\');'."\n";
	echo '</script>'."\n";
	echo '<div id="lastHerdDetails">'."\n";
	include 'lh_details.php';					// MMDVMDash Last Herd
	echo '</div>'."\n";
	echo "<br />\n";


			 ?>

               <?php

			echo '<script type="text/javascript">'."\n";
	echo 'function reloadLocalTx(){'."\n";
	echo '  $("#localTxs").load("localtx.php");'."\n";
	echo '  $("#lastHerd").load("lh.php");'."\n";
	echo '}'."\n";
	echo 'setInterval(function(){reloadLocalTx()}, 1500);'."\n";
	echo '$(window).trigger(\'resize\');'."\n";
	echo '</script>'."\n";
	echo '<div id="lastHerd">'."\n";
	include 'lh.php';					// MMDVMDash Last Herd
	echo '</div>'."\n";
	echo "<br />\n";
	echo '<div id="localTxs">'."\n";
	include 'localtx.php';				// MMDVMDash Local Trasmissions
	echo '</div>'."\n";

			 ?>
            </div>

            <div class="block-title">Pi-Star Digital Voice Dashboard</div>
            <div class="block block-strong">
              <div class="row">
                <div class="col-50">
                  <a href="#" class="button button-raised button-fill popup-open" data-popup="#my-popup">About</a>
                </div>
                <div class="col-50">
                  <a href="http://www.facebook.com/groups/pistar/" class="button button-raised button-fill popup-open" data-popup="#my-popup2">Facebook</a>
                </div>
              </div>
            </div>

          <div class="list">
            <ul>
              <li>
                <a href="/livedisplay/" class="item-content item-link">
                  <div class="item-inner">
                    <div class="item-title">Live Caller Display (Landscape Mode)</div>
                  </div>
                </a>
              </li>
            </ul>
          </div>

          </div>
        </div>
      </div>

      <!-- Admin View -->
      <div id="view-admin" class="view tab">

        <!-- Admin page will be loaded here dynamically from /admin/ route -->
      </div>

      <!-- Settings View -->
      <div id="view-settings" class="view tab">
        <!-- Settings page will be loaded here dynamically from /settings/ route -->
      </div>

       <!-- Power View -->
      <div id="view-power" class="view tab">
        <!-- Power page will be loaded here dynamically from /power/ route -->
      </div>
    </div>

    <!-- Popup -->
    <div class="popup" id="my-popup">
      <div class="view">
        <div class="page">
          <div class="navbar">
            <div class="navbar-inner">
              <div class="title">About</div>
              <div class="right">
                <a href="#" class="link popup-close">Close</a>
              </div>
            </div>
          </div>
          <div class="page-content">
            <div class="block">

Pi-Star / Pi-Star Dashboard For V4, © Andy Taylor (MW0MWZ) 2014-2018 Modified By Li Ma (BH4FCZ).<br />
ircDDBGateway Dashboard by Hans-J. Barthen (DL5DI),<br />
MMDVMDash developed by Kim Huebel (DG9VH), <br />
Need help? <a href="https://www.facebook.com/groups/pistar/" class="link external">Click here for the Support Group</a><br />
Get your copy of Pi-Star from <a href="http://www.pistar.uk/downloads/" class="link external">here</a>.<br /><br />
</div>
          </div>
        </div>
      </div>
    </div>


      </div>
    </div>
  </div>

  <!-- Framework7 library -->
  <script src="framework7/js/framework7.min.js"></script>

  <!-- App routes -->
  <script src="js/routes.js"></script>

  <!-- Your custom app scripts -->
  <script src="js/app.js"></script>
</body>
</html>
