<?php
include '../config/config.php';					// MMDVMDash Config
include '../mmdvmhost/tools.php';					// MMDVMDash Tools
//include 'mmdvmhost/functions.php';				// MMDVMDash Functions

require_once('../config/version.php');
require_once('../config/ircddblocal.php');
require_once('../config/language.php');

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
?>
 <link href="../framework7/css/framework7.min.css" rel="stylesheet" type="text/css">
 <link rel="stylesheet" href="../framework7/css/framework7-icons.css">
 
 <link rel="stylesheet" type="text/css" href="../css/mini_ircddb.css">

<div class="page" data-name="dashboard">
  <div class="navbar">
    <div class="navbar-inner sliding">
      <div class="title">Dashboard</div>
    </div>
  </div>
  <div class="page-content">
  <div class="block block-strong">
  <br />
  <style>
 input[type="text"] {
	box-sizing: border-box;
	border-radius: 0px;
	border-style: solid;
	border-color: #333;
	font-size:13px;
}
 input[type="password"] {
	box-sizing: border-box;
	border-radius: 0px;
	border-style: solid;
	border-color: #333;
	font-size:13px;
	width:200px;
}
 select  {
	width: 100%;
	box-sizing: border-box;
	border-radius: 0px;
	border-style: solid;
	border-color: #333;
	text-align: center;
	font-size: 13px;
}
</style>
<?php  
	include '../mmdvmhost/functions.php';				// MMDVMDash Functions

$testMMDVModeDSTARnet = getConfigItem("D-Star Network", "Enable", $mmdvmconfigs);

        if ( $testMMDVModeDSTARnet == 1 ) {				// If D-Star network is enabled, add these extra features.

		echo '<script type="text/javascript">'."\n";
		echo 'function reloadrefLinks(){'."\n";
		echo '  $("#refLinks").load("../dstarrepeater/active_reflector_links.php");'."\n";
		echo '}'."\n";
		echo 'setInterval(function(){reloadrefLinks()}, 2500);'."\n";
		echo '$(window).trigger(\'resize\');'."\n";
		echo '</script>'."\n";
		echo '<div id="refLinks">'."\n";
		include '../dstarrepeater/active_reflector_links.php';	// dstarrepeater gateway config
	        echo '</div>'."\n";
	        echo '<br />'."\n";

		include '../dstarrepeater/link_manager.php';		// D-Star Link Manager
		echo "<br />\n";

        echo '<script type="text/javascript">'."\n";
        echo 'function reloadcssConnections(){'."\n";
        echo '  $("#cssConnects").load("../dstarrepeater/css_connections.php");'."\n";
        echo '}'."\n";
        echo 'setInterval(function(){reloadcssConnections()}, 15000);'."\n";
	echo '$(window).trigger(\'resize\');'."\n";
        echo '</script>'."\n";
        echo '<div id="cssConnects">'."\n";
	include '../dstarrepeater/css_connections.php';			// dstarrepeater gateway config
	echo '</div>'."\n";
	}



		echo '<script type="text/javascript">'."\n";
        echo 'function reloadbmConnections(){'."\n";
        echo '  $("#bmConnects").load("../bm_links.php");'."\n";
        echo '}'."\n";
        echo 'setInterval(function(){reloadbmConnections()}, 15000);'."\n";
		echo '$(window).trigger(\'resize\');'."\n";
        echo '</script>'."\n";
        echo '<div id="bmConnects">'."\n";
		include '../bm_links.php';                       // BM Links
		echo '</div>'."\n";

		include '../bm_manager.php';                     // DMR Link Manager


        
?>
</div>
  </div>
</div>
