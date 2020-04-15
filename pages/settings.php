<?php
// Pull in some config
require_once('../config/version.php');
require_once('../config/ircddblocal.php');
require_once('../config/language.php');
$cpuLoad = sys_getloadavg();

// Load the pistar-release file
$pistarReleaseConfig = '/etc/pistar-release';
$configPistarRelease = parse_ini_file($pistarReleaseConfig, true);

// Load the dstarrepeater config file
$configdstar = array();
if ($configdstarfile = fopen('/etc/dstarrepeater','r')) {
        while ($line1 = fgets($configdstarfile)) {
		if (strpos($line1, '=') !== false) {
                	list($key1,$value1) = explode("=",$line1);
                	$value1 = trim(str_replace('"','',$value1));
                	if (strlen($value1) > 0)
                	$configdstar[$key1] = $value1;
		}
        }
        fclose($configdstarfile);
}

// Load the ircDDBGateway config file
$configs = array();
if ($configfile = fopen($gatewayConfigPath,'r')) {
        while ($line = fgets($configfile)) {
		if (strpos($line, '=') !== false) {
                	list($key,$value) = explode("=",$line);
                	$value = trim(str_replace('"','',$value));
                	if ($key != 'ircddbPassword' && strlen($value) > 0)
                	$configs[$key] = $value;
		}
        }
        fclose($configfile);
}

// Load the mmdvmhost config file
$mmdvmConfigFile = '/etc/mmdvmhost';
$configmmdvm = parse_ini_file($mmdvmConfigFile, true);

// Load the ysfgateway config file
$ysfgatewayConfigFile = '/etc/ysfgateway';
$configysfgateway = parse_ini_file($ysfgatewayConfigFile, true);

// Load the ysf2dmr config file
if (file_exists('/etc/ysf2dmr')) {
	$ysf2dmrConfigFile = '/etc/ysf2dmr';
	if (fopen($ysf2dmrConfigFile,'r')) { $configysf2dmr = parse_ini_file($ysf2dmrConfigFile, true); }
}

// Load the p25gateway config file
$p25gatewayConfigFile = '/etc/p25gateway';
$configp25gateway = parse_ini_file($p25gatewayConfigFile, true);

// Load the dmrgateway config file
$dmrGatewayConfigFile = '/etc/dmrgateway';
if (fopen($dmrGatewayConfigFile,'r')) { $configdmrgateway = parse_ini_file($dmrGatewayConfigFile, true); }

// Load the modem config information
if (file_exists('/etc/dstar-radio.dstarrepeater')) {
	$modemConfigFileDStarRepeater = '/etc/dstar-radio.dstarrepeater';
	if (fopen($modemConfigFileDStarRepeater,'r')) { $configModem = parse_ini_file($modemConfigFileDStarRepeater, true); }
}

if (file_exists('/etc/dstar-radio.mmdvmhost')) {
	$modemConfigFileMMDVMHost = '/etc/dstar-radio.mmdvmhost';
	if (fopen($modemConfigFileMMDVMHost,'r')) { $configModem = parse_ini_file($modemConfigFileMMDVMHost, true); }
}

function aprspass ($callsign) {
	$stophere = strpos($callsign, '-');
	if ($stophere) $callsign = substr($callsign, 0, $stophere);
	$realcall = strtoupper(substr($callsign, 0, 10));
	// initialize hash
	$hash = 0x73e2;
	$i = 0;
	$len = strlen($realcall);
	// hash callsign two bytes at a time
	while ($i < $len) {
		$hash ^= ord(substr($realcall, $i, 1))<<8;
		$hash ^= ord(substr($realcall, $i + 1, 1));
		$i += 2;
	}
	// mask off the high bit so number is always positive
	return $hash & 0x7fff;
}

$progname = basename($_SERVER['SCRIPT_FILENAME'],".php");
$rev=$version;
$MYCALL=strtoupper($callsign);
?>


    

<?php if (!empty($_POST)){
	
	?>

	<link rel="stylesheet" type="text/css" href="../css/mini_ircddb.css">
	<link rel="stylesheet" href="../css/icons.css">
	<link rel="stylesheet" href="../css/app.css">
  
<br />
    
    <?php
	
	
	// Make the root filesystem writable
	system('sudo mount -o remount,rw /');

	// Stop Cron (occasionally remounts root as RO - would be bad if it did this at the wrong time....)
	system('sudo systemctl stop cron.service > /dev/null 2>/dev/null &');			//Cron

	// Stop the DV Services
	system('sudo systemctl stop dstarrepeater.service > /dev/null 2>/dev/null &');		// D-Star Radio Service
	system('sudo systemctl stop mmdvmhost.service > /dev/null 2>/dev/null &');		// MMDVMHost Radio Service
	system('sudo systemctl stop ircddbgateway.service > /dev/null 2>/dev/null &');		// ircDDBGateway Service
	system('sudo systemctl stop timeserver.service > /dev/null 2>/dev/null &');		// Time Server Service
	system('sudo systemctl stop pistar-watchdog.service > /dev/null 2>/dev/null &');	// PiStar-Watchdog Service
	system('sudo systemctl stop pistar-remote.service > /dev/null 2>/dev/null &');		// PiStar-Remote Service
	system('sudo systemctl stop ysfgateway.service > /dev/null 2>/dev/null &');		// YSFGateway
	system('sudo systemctl stop ysf2dmr.service > /dev/null 2>/dev/null &');		// YSF2DMR
	system('sudo systemctl stop ysfparrot.service > /dev/null 2>/dev/null &');		// YSFParrot
	system('sudo systemctl stop p25gateway.service > /dev/null 2>/dev/null &');		// P25Gateway
	system('sudo systemctl stop p25parrot.service > /dev/null 2>/dev/null &');		// P25Parrot
	system('sudo systemctl stop dmrgateway.service > /dev/null 2>/dev/null &');		// DMRGateway

	echo "<table>\n";
	echo "<tr><th>Working...</th></tr>\n";
	echo "<tr><td>Stopping services and applying your configuration changes...</td></tr>\n";
	echo "</table>\n";

	// Let the services actualy stop
	sleep(1);


	// Handle the case where the config is not read correctly
	if (count($configmmdvm) <= 18) {
	  echo "<br />\n";
	  echo "<table>\n";
	  echo "<tr><th>ERROR</th></tr>\n";
	  echo "<tr><td>Unable to read source configuration file(s)...</td><tr>\n";
	  echo "<tr><td>Please wait a few seconds and retry...</td></tr>\n";
	  echo "</table>\n";
	  unset($_POST);
	  echo '<script type="text/javascript">setTimeout(function() { window.location=window.location;},5000);</script>';
	  die();
	}


	// HostAP
	if (empty($_POST['autoAP']) != TRUE ) {
	  if (escapeshellcmd($_POST['autoAP']) == 'OFF') { system('sudo touch /etc/hostap.off'); }
	  if (escapeshellcmd($_POST['autoAP']) == 'ON') { system('sudo rm -rf /etc/hostap.off'); }
	}

	// Change Dashboard Language
	if (empty($_POST['dashboardLanguage']) != TRUE ) {
	  $rollDashLang = 'sudo sed -i "/pistarLanguage=/c\\$pistarLanguage=\''.escapeshellcmd($_POST['dashboardLanguage']).'\';" /var/www/dashboard/config/language.php';
	  system($rollDashLang);
	  }

	// Set the Latitude
	if (empty($_POST['confLatitude']) != TRUE ) {
	  $newConfLatitude = preg_replace('/[^0-9\.\-]/', '', $_POST['confLatitude']);
	  $rollConfLat0 = 'sudo sed -i "/latitude=/c\\latitude='.$newConfLatitude.'" /etc/ircddbgateway';
	  $rollConfLat1 = 'sudo sed -i "/latitude1=/c\\latitude1='.$newConfLatitude.'" /etc/ircddbgateway';
	  $configmmdvm['Info']['Latitude'] = $newConfLatitude;
	  $configysfgateway['Info']['Latitude'] = $newConfLatitude;
	  $configysf2dmr['Info']['Latitude'] = $newConfLatitude;
	  $configdmrgateway['Info']['Latitude'] = $newConfLatitude;
	  system($rollConfLat0);
	  system($rollConfLat1);
	  }

	// Set the Longitude
	if (empty($_POST['confLongitude']) != TRUE ) {
	  $newConfLongitude = preg_replace('/[^0-9\.\-]/', '', $_POST['confLongitude']);
	  $rollConfLon0 = 'sudo sed -i "/longitude=/c\\longitude='.$newConfLongitude.'" /etc/ircddbgateway';
	  $rollConfLon1 = 'sudo sed -i "/longitude1=/c\\longitude1='.$newConfLongitude.'" /etc/ircddbgateway';
	  $configmmdvm['Info']['Longitude'] = $newConfLongitude;
	  $configysfgateway['Info']['Longitude'] = $newConfLongitude;
	  $configysf2dmr['Info']['Longitude'] = $newConfLongitude;
	  $configdmrgateway['Info']['Longitude'] = $newConfLongitude;
	  system($rollConfLon0);
	  system($rollConfLon1);
	  }

	// Set the Town
	if (empty($_POST['confDesc1']) != TRUE ) {
	  $newConfDesc1 = preg_replace('/[^A-Za-z0-9\.\s\,\-]/', '', $_POST['confDesc1']);
	  $rollDesc1 = 'sudo sed -i "/description1=/c\\description1='.$newConfDesc1.'" /etc/ircddbgateway';
	  $rollDesc11 = 'sudo sed -i "/description1_1=/c\\description1_1='.$newConfDesc1.'" /etc/ircddbgateway';
	  $configmmdvm['Info']['Location'] = '"'.$newConfDesc1.'"';
	  $configdmrgateway['Info']['Location'] = '"'.$newConfDesc1.'"';
          $configysfgateway['Info']['Name'] = '"'.$newConfDesc1.'"';
	  $configysf2dmr['Info']['Location'] = '"'.$newConfDesc1.'"';
	  system($rollDesc1);
	  system($rollDesc11);
	  }

	// Set the Country
	if (empty($_POST['confDesc2']) != TRUE ) {
	  $newConfDesc2 = preg_replace('/[^A-Za-z0-9\.\s\,\-]/', '', $_POST['confDesc2']);
	  $rollDesc2 = 'sudo sed -i "/description2=/c\\description2='.$newConfDesc2.'" /etc/ircddbgateway';
	  $rollDesc22 = 'sudo sed -i "/description1_2=/c\\description1_2='.$newConfDesc2.'" /etc/ircddbgateway';
          $configmmdvm['Info']['Description'] = '"'.$newConfDesc2.'"';
	  $configdmrgateway['Info']['Description'] = '"'.$newConfDesc2.'"';
          $configysfgateway['Info']['Description'] = '"'.$newConfDesc2.'"';
	  $configysf2dmr['Info']['Description'] = '"'.$newConfDesc2.'"';
	  system($rollDesc2);
	  system($rollDesc22);
	  }

	// Set the URL
	if (empty($_POST['confURL']) != TRUE ) {
	  $newConfURL = strtolower(preg_replace('/[^A-Za-z0-9\.\s\,\-\/\:]/', '', $_POST['confURL']));
	  if (escapeshellcmd($_POST['urlAuto']) == 'auto') { $txtURL = "http://www.qrz.com/db/".strtoupper(escapeshellcmd($_POST['confCallsign'])); }
	  if (escapeshellcmd($_POST['urlAuto']) == 'man')  { $txtURL = $newConfURL; }
	  if (escapeshellcmd($_POST['urlAuto']) == 'auto') { $rollURL0 = 'sudo sed -i "/url=/c\\url=http://www.qrz.com/db/'.strtoupper(escapeshellcmd($_POST['confCallsign'])).'" /etc/ircddbgateway';  }
	  if (escapeshellcmd($_POST['urlAuto']) == 'man') { $rollURL0 = 'sudo sed -i "/url=/c\\url='.$newConfURL.'" /etc/ircddbgateway'; }
          $configmmdvm['Info']['URL'] = $txtURL;
	  $configysf2dmr['Info']['URL'] = $txtURL;
	  $configdmrgateway['Info']['URL'] = $txtURL;
	  system($rollURL0);
	  }

	// Set the Frequency for Duplex
	if (empty($_POST['confFREQtx']) != TRUE && empty($_POST['confFREQrx']) != TRUE ) {
	  if (empty($_POST['confHardware']) != TRUE ) { $confHardware = escapeshellcmd($_POST['confHardware']); }
	  $newConfFREQtx = preg_replace('/[^0-9\.]/', '', $_POST['confFREQtx']);
	  $newConfFREQrx = preg_replace('/[^0-9\.]/', '', $_POST['confFREQrx']);
	  $newFREQtx = str_pad(str_replace(".", "", $newConfFREQtx), 9, "0");
	  $newFREQtx = mb_strimwidth($newFREQtx, 0, 9);
	  $newFREQrx = str_pad(str_replace(".", "", $newConfFREQrx), 9, "0");
	  $newFREQrx = mb_strimwidth($newFREQrx, 0, 9);
	  $newFREQirc = substr_replace($newFREQtx, '.', '3', 0);
	  $newFREQirc = mb_strimwidth($newFREQirc, 0, 9);
	  $rollFREQirc = 'sudo sed -i "/frequency1=/c\\frequency1='.$newFREQirc.'" /etc/ircddbgateway';
	  $rollFREQdvap = 'sudo sed -i "/dvapFrequency=/c\\dvapFrequency='.$newFREQrx.'" /etc/dstarrepeater';
	  $rollFREQdvmegaRx = 'sudo sed -i "/dvmegaRXFrequency=/c\\dvmegaRXFrequency='.$newFREQrx.'" /etc/dstarrepeater';
	  $rollFREQdvmegaTx = 'sudo sed -i "/dvmegaTXFrequency=/c\\dvmegaTXFrequency='.$newFREQtx.'" /etc/dstarrepeater';
	  $rollModeDuplex = 'sudo sed -i "/mode=/c\\mode=0" /etc/dstarrepeater';
	  $configmmdvm['Info']['RXFrequency'] = $newFREQrx;
	  $configmmdvm['Info']['TXFrequency'] = $newFREQtx;
	  $configdmrgateway['Info']['RXFrequency'] = $newFREQrx;
	  $configdmrgateway['Info']['TXFrequency'] = $newFREQtx;
	  $configysfgateway['Info']['RXFrequency'] = $newFREQrx;
	  $configysfgateway['Info']['TXFrequency'] = $newFREQtx;
	  $configysf2dmr['Info']['RXFrequency'] = $newFREQrx;
	  $configysf2dmr['Info']['TXFrequency'] = $newFREQtx;

	  system($rollFREQirc);
	  system($rollFREQdvap);
	  system($rollFREQdvmegaRx);
	  system($rollFREQdvmegaTx);
	  system($rollModeDuplex);

	// Set RPT1 and RPT2
	  if (empty($_POST['confDStarModuleSuffix'])) {
	    if ($newFREQtx >= 1240000000 && $newFREQtx <= 1300000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."A";
		$confIRCrepeaterBand1 = "A";
		$configmmdvm['D-Star']['Module'] = "A";
		$rollTimeserverBand = 'sudo sed -i "/sendA=/c\\sendA=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	    if ($newFREQtx >= 420000000 && $newFREQtx <= 450000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."B";
		$confIRCrepeaterBand1 = "B";
		$configmmdvm['D-Star']['Module'] = "B";
		$rollTimeserverBand = 'sudo sed -i "/sendB=/c\\sendB=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	    if ($newFREQtx >= 218000000 && $newFREQtx <= 226000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."A";
		$confIRCrepeaterBand1 = "A";
		$configmmdvm['D-Star']['Module'] = "A";
		$rollTimeserverBand = 'sudo sed -i "/sendA=/c\\sendA=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	    if ($newFREQtx >= 144000000 && $newFREQtx <= 148000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."C";
		$confIRCrepeaterBand1 = "C";
		$configmmdvm['D-Star']['Module'] = "C";
		$rollTimeserverBand = 'sudo sed -i "/sendC=/c\\sendC=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	  }
	  else {
	     $confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ").strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix']));
	     $confIRCrepeaterBand1 = strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix']));
	     $configmmdvm['D-Star']['Module'] = strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix']));
	     $rollTimeserverBand = 'sudo sed -i "/send'.strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix'])).'=/c\\send'.strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix'])).'=1" /etc/timeserver';
	     system($rollTimeserverBand);
	  }

	  $newCallsignUpper = strtoupper(escapeshellcmd($_POST['confCallsign']));
	  $confRPT2 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."G";

	  $confRPT1 = strtoupper($confRPT1);
	  $confRPT2 = strtoupper($confRPT2);

	  $rollRPT1 = 'sudo sed -i "/callsign=/c\\callsign='.$confRPT1.'" /etc/dstarrepeater';
	  $rollRPT2 = 'sudo sed -i "/gateway=/c\\gateway='.$confRPT2.'" /etc/dstarrepeater';
	  $rollBEACONTEXT = 'sudo sed -i "/beaconText=/c\\beaconText='.$confRPT1.'" /etc/dstarrepeater';
	  $rollIRCrepeaterBand1 = 'sudo sed -i "/repeaterBand1=/c\\repeaterBand1='.$confIRCrepeaterBand1.'" /etc/ircddbgateway';
	  $rollIRCrepeaterCall1 = 'sudo sed -i "/repeaterCall1=/c\\repeaterCall1='.$newCallsignUpper.'" /etc/ircddbgateway';

	  system($rollRPT1);
	  system($rollRPT2);
	  system($rollBEACONTEXT);
	  system($rollIRCrepeaterBand1);
	  system($rollIRCrepeaterCall1);
	}

	// Set the Frequency for Simplex
	if (empty($_POST['confFREQ']) != TRUE ) {
	  if (empty($_POST['confHardware']) != TRUE ) { $confHardware = escapeshellcmd($_POST['confHardware']); }
	  $newConfFREQ = preg_replace('/[^0-9\.]/', '', $_POST['confFREQ']);
	  $newFREQ = str_pad(str_replace(".", "", $newConfFREQ), 9, "0");
	  $newFREQ = mb_strimwidth($newFREQ, 0, 9);
	  $newFREQirc = substr_replace($newFREQ, '.', '3', 0);
	  $newFREQirc = mb_strimwidth($newFREQirc, 0, 9);
	  $rollFREQirc = 'sudo sed -i "/frequency1=/c\\frequency1='.$newFREQirc.'" /etc/ircddbgateway';
	  $rollFREQdvap = 'sudo sed -i "/dvapFrequency=/c\\dvapFrequency='.$newFREQ.'" /etc/dstarrepeater';
	  $rollFREQdvmegaRx = 'sudo sed -i "/dvmegaRXFrequency=/c\\dvmegaRXFrequency='.$newFREQ.'" /etc/dstarrepeater';
	  $rollFREQdvmegaTx = 'sudo sed -i "/dvmegaTXFrequency=/c\\dvmegaTXFrequency='.$newFREQ.'" /etc/dstarrepeater';
	  $rollModeSimplex = 'sudo sed -i "/mode=/c\\mode=1" /etc/dstarrepeater';
	  $configmmdvm['Info']['RXFrequency'] = $newFREQ;
	  $configmmdvm['Info']['TXFrequency'] = $newFREQ;
	  $configdmrgateway['Info']['RXFrequency'] = $newFREQ;
	  $configdmrgateway['Info']['TXFrequency'] = $newFREQ;
	  $configysfgateway['Info']['RXFrequency'] = $newFREQ;
	  $configysfgateway['Info']['TXFrequency'] = $newFREQ;
	  $configysf2dmr['Info']['RXFrequency'] = $newFREQ;
	  $configysf2dmr['Info']['TXFrequency'] = $newFREQ;

	  system($rollFREQirc);
	  system($rollFREQdvap);
	  system($rollFREQdvmegaRx);
	  system($rollFREQdvmegaTx);
	  system($rollModeSimplex);

	// Set RPT1 and RPT2
	  if (empty($_POST['confDStarModuleSuffix'])) {
	    if ($newFREQ >= 1240000000 && $newFREQ <= 1300000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."A";
		$confIRCrepeaterBand1 = "A";
		$configmmdvm['D-Star']['Module'] = "A";
		$rollTimeserverBand = 'sudo sed -i "/sendA=/c\\sendA=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	    if ($newFREQ >= 420000000 && $newFREQ <= 450000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."B";
		$confIRCrepeaterBand1 = "B";
		$configmmdvm['D-Star']['Module'] = "B";
		$rollTimeserverBand = 'sudo sed -i "/sendB=/c\\sendB=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	    if ($newFREQ >= 218000000 && $newFREQ <= 226000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."A";
		$confIRCrepeaterBand1 = "A";
		$configmmdvm['D-Star']['Module'] = "A";
		$rollTimeserverBand = 'sudo sed -i "/sendA=/c\\sendA=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	    if ($newFREQ >= 144000000 && $newFREQ <= 148000000) {
		$confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."C";
		$confIRCrepeaterBand1 = "C";
		$configmmdvm['D-Star']['Module'] = "C";
		$rollTimeserverBand = 'sudo sed -i "/sendA=/c\\sendA=1" /etc/timeserver';
		system($rollTimeserverBand);
		}
	  }
	  else {
	     $confRPT1 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ").strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix']));
	     $confIRCrepeaterBand1 = strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix']));
	     $configmmdvm['D-Star']['Module'] = strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix']));
	     $rollTimeserverBand = 'sudo sed -i "/send'.strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix'])).'=/c\\send'.strtoupper(escapeshellcmd($_POST['confDStarModuleSuffix'])).'=1" /etc/timeserver';
	     system($rollTimeserverBand);
	  }

	  $newCallsignUpper = strtoupper(escapeshellcmd($_POST['confCallsign']));
	  $confRPT2 = str_pad(escapeshellcmd($_POST['confCallsign']), 7, " ")."G";

	  $confRPT1 = strtoupper($confRPT1);
	  $confRPT2 = strtoupper($confRPT2);

	  $rollRPT1 = 'sudo sed -i "/callsign=/c\\callsign='.$confRPT1.'" /etc/dstarrepeater';
	  $rollRPT2 = 'sudo sed -i "/gateway=/c\\gateway='.$confRPT2.'" /etc/dstarrepeater';
	  $rollBEACONTEXT = 'sudo sed -i "/beaconText=/c\\beaconText='.$confRPT1.'" /etc/dstarrepeater';
	  $rollIRCrepeaterBand1 = 'sudo sed -i "/repeaterBand1=/c\\repeaterBand1='.$confIRCrepeaterBand1.'" /etc/ircddbgateway';
	  $rollIRCrepeaterCall1 = 'sudo sed -i "/repeaterCall1=/c\\repeaterCall1='.$newCallsignUpper.'" /etc/ircddbgateway';

	  system($rollRPT1);
	  system($rollRPT2);
	  system($rollBEACONTEXT);
	  system($rollIRCrepeaterBand1);
	  system($rollIRCrepeaterCall1);
	  }

	// Set Callsign
	if (empty($_POST['confCallsign']) != TRUE ) {
	  $newCallsignUpper = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $_POST['confCallsign']));
	  if (preg_match("/^[0-9]/", $newCallsignUpper)) { $newCallsignUpperIRC = 'r'.$newCallsignUpper; } else { $newCallsignUpperIRC = $newCallsignUpper; }

	  $rollGATECALL = 'sudo sed -i "/gatewayCallsign=/c\\gatewayCallsign='.$newCallsignUpper.'" /etc/ircddbgateway';
	  $rollDPLUSLOGIN = 'sudo sed -i "/dplusLogin=/c\\dplusLogin='.$newCallsignUpper.'" /etc/ircddbgateway';
	  $rollDASHBOARDcall = 'sudo sed -i "/callsign=/c\\$callsign=\''.$newCallsignUpper.'\';" /var/www/dashboard/config/ircddblocal.php';
	  $rollTIMESERVERcall = 'sudo sed -i "/callsign=/c\\callsign='.$newCallsignUpper.'" /etc/timeserver';
	  $rollSTARNETSERVERcall = 'sudo sed -i "/callsign=/c\\callsign='.$newCallsignUpper.'" /etc/starnetserver';
	  $rollSTARNETSERVERirc = 'sudo sed -i "/ircddbUsername=/c\\ircddbUsername='.$newCallsignUpperIRC.'" /etc/starnetserver';
	  $rollP25GATEWAY = 'sudo sed -i "/Callsign=/c\\Callsign='.$newCallsignUpper.'" /etc/p25gateway';

	  // Only roll ircDDBGateway Username if using OpenQuad
	  if ($configs['ircddbHostname'] == "rr.openquad.net") {
		  $rollIRCUSER = 'sudo sed -i "/ircddbUsername=/c\\ircddbUsername='.$newCallsignUpperIRC.'" /etc/ircddbgateway';
		  system($rollIRCUSER);
	  }

	  $configmmdvm['General']['Callsign'] = $newCallsignUpper;
	  $configysfgateway['General']['Callsign'] = $newCallsignUpper;
	  $configysfgateway['aprs.fi']['Password'] = aprspass($newCallsignUpper);
	  $configysfgateway['aprs.fi']['Description'] = $newCallsignUpper."_Pi-Star";
	  $configysf2dmr['aprs.fi']['Password'] = aprspass($newCallsignUpper);
	  $configysf2dmr['aprs.fi']['Description'] = $newCallsignUpper."_Pi-Star";
	  $configysf2dmr['YSF Network']['Callsign'] = $newCallsignUpper;

	  system($rollGATECALL);
	  system($rollIRCUSER);
	  system($rollDPLUSLOGIN);
	  system($rollDASHBOARDcall);
	  system($rollTIMESERVERcall);
	  system($rollSTARNETSERVERcall);
	  system($rollSTARNETSERVERirc);
	  system($rollP25GATEWAY);

	}

	

	// Set Duplex
	if (empty($_POST['trxMode']) != TRUE ) {
	  if ($configmmdvm['Info']['RXFrequency'] === $configmmdvm['Info']['TXFrequency'] && $_POST['trxMode'] == "DUPLEX" ) {
	    $configmmdvm['Info']['RXFrequency'] = $configmmdvm['Info']['TXFrequency'] - 1;
	    }
	  if ($configmmdvm['Info']['RXFrequency'] !== $configmmdvm['Info']['TXFrequency'] && $_POST['trxMode'] == "SIMPLEX" ) {
	    $configmmdvm['Info']['RXFrequency'] = $configmmdvm['Info']['TXFrequency'];
	    }
	  if ($_POST['trxMode'] == "DUPLEX") {
	    $configmmdvm['General']['Duplex'] = 1;
	    $configmmdvm['DMR Network']['Slot1'] = '1';
	    $configmmdvm['DMR Network']['Slot2'] = '1';
	  }
	  if ($_POST['trxMode'] == "SIMPLEX") {
	    $configmmdvm['General']['Duplex'] = 0;
	    $configmmdvm['DMR Network']['Slot1'] = '0';
	    $configmmdvm['DMR Network']['Slot2'] = '1';
	  }
	}

	// Set DMR / CCS7 ID
	if (empty($_POST['dmrId']) != TRUE ) {
	  $newPostDmrId = preg_replace('/[^0-9]/', '', $_POST['dmrId']);
	  //$configmmdvm['DMR']['Id'] = $newPostDmrId;
	  unset($configmmdvm['DMR']['Id']);
	  if (empty($_POST['dmrMasterHost']) != TRUE ) {
		  $dmrMasterHostArrTest = explode(',', escapeshellcmd($_POST['dmrMasterHost']));
		  if (substr($dmrMasterHostArrTest[3], 0, 4) == 'DMR+') { $newPostDmrId = substr($newPostDmrId, 0, 7); }
	  }
	  $configmmdvm['General']['Id'] = $newPostDmrId;
	  $configdmrgateway['XLX Network']['Id'] = substr($newPostDmrId,0,7);
	  $configdmrgateway['XLX Network 1']['Id'] = substr($newPostDmrId,0,7);
	  $configdmrgateway['DMR Network 2']['Id'] = substr($newPostDmrId,0,7);
	}

	// Set NXDN ID
	if (empty($_POST['nxdnId']) != TRUE ) {
	  $newPostNxdnId = preg_replace('/[^0-9]/', '', $_POST['nxdnId']);
	  $configmmdvm['NXDN']['Id'] = $newPostNxdnId;
	  if ($configmmdvm['NXDN']['Id'] > 65535) { unset($configmmdvm['NXDN']['Id']); }
	}
	
	// Set DMR Master Server
	if (empty($_POST['dmrMasterHost']) != TRUE ) {
	  $dmrMasterHostArr = explode(',', escapeshellcmd($_POST['dmrMasterHost']));
	  $configmmdvm['DMR Network']['Address'] = $dmrMasterHostArr[0];
	  $configmmdvm['DMR Network']['Password'] = $dmrMasterHostArr[1];
	  $configmmdvm['DMR Network']['Port'] = $dmrMasterHostArr[2];

		if (substr($dmrMasterHostArr[3], 0, 2) == "BM") {
			unset ($configmmdvm['DMR Network']['Options']);
			unset ($configdmrgateway['DMR Network 2']['Options']);
			unset ($configmmdvm['DMR Network']['Local']);
			unset ($configysf2dmr['DMR Network']['Options']);
			unset ($configysf2dmr['DMR Network']['Local']);
		}

		if ($dmrMasterHostArr[0] == '127.0.0.1') {
			unset ($configmmdvm['DMR Network']['Options']);
			unset ($configdmrgateway['DMR Network 2']['Options']);
			$configmmdvm['DMR Network']['Local'] = "62032";
			unset ($configysf2dmr['DMR Network']['Options']);
			$configysf2dmr['DMR Network']['Local'] = "62032";
		}

		// Set the DMR+ Options= line
		if (substr($dmrMasterHostArr[3], 0, 4) == "DMR+") {
			unset ($configmmdvm['DMR Network']['Local']);
			unset ($configysf2dmr['DMR Network']['Local']);
			if (empty($_POST['dmrNetworkOptions']) != TRUE ) {
				$dmrOptionsLineStripped = str_replace('"', "", $_POST['dmrNetworkOptions']);
				$configmmdvm['DMR Network']['Options'] = '"'.$dmrOptionsLineStripped.'"';
				$configdmrgateway['DMR Network 2']['Options'] = '"'.$dmrOptionsLineStripped.'"';
			}
			else {
				unset ($configmmdvm['DMR Network']['Options']);
				unset ($configdmrgateway['DMR Network 2']['Options']);
				unset ($configysf2dmr['DMR Network']['Options']);
			}
		}
	}
	if (empty($_POST['dmrMasterHost']) == TRUE ) {
		unset ($configmmdvm['DMR Network']['Options']);
		unset ($configdmrgateway['DMR Network 2']['Options']);
	}
	if (empty($_POST['dmrMasterHost1']) != TRUE ) {
	  $dmrMasterHostArr1 = explode(',', escapeshellcmd($_POST['dmrMasterHost1']));
	  $configdmrgateway['DMR Network 1']['Address'] = $dmrMasterHostArr1[0];
	  $configdmrgateway['DMR Network 1']['Password'] = $dmrMasterHostArr1[1];
	  $configdmrgateway['DMR Network 1']['Port'] = $dmrMasterHostArr1[2];
	  $configdmrgateway['DMR Network 1']['Name'] = $dmrMasterHostArr1[3];
	}
	if (empty($_POST['dmrMasterHost2']) != TRUE ) {
	  $dmrMasterHostArr2 = explode(',', escapeshellcmd($_POST['dmrMasterHost2']));
	  $configdmrgateway['DMR Network 2']['Address'] = $dmrMasterHostArr2[0];
	  $configdmrgateway['DMR Network 2']['Password'] = $dmrMasterHostArr2[1];
	  $configdmrgateway['DMR Network 2']['Port'] = $dmrMasterHostArr2[2];
	  $configdmrgateway['DMR Network 2']['Name'] = $dmrMasterHostArr2[3];
	  if (empty($_POST['dmrNetworkOptions']) != TRUE ) {
	    $dmrOptionsLineStripped = str_replace('"', "", $_POST['dmrNetworkOptions']);
	    unset ($configmmdvm['DMR Network']['Options']);
	    $configdmrgateway['DMR Network 2']['Options'] = '"'.$dmrOptionsLineStripped.'"';
	  }
	  else {
		unset ($configdmrgateway['DMR Network 2']['Options']);
	       }
	}
	if (empty($_POST['dmrMasterHost3']) != TRUE ) {
	  $dmrMasterHostArr3 = explode(',', escapeshellcmd($_POST['dmrMasterHost3']));
	  $configdmrgateway['XLX Network 1']['Address'] = $dmrMasterHostArr3[0];
	  $configdmrgateway['XLX Network 1']['Password'] = $dmrMasterHostArr3[1];
	  $configdmrgateway['XLX Network 1']['Port'] = $dmrMasterHostArr3[2];
	  $configdmrgateway['XLX Network 1']['Name'] = $dmrMasterHostArr3[3];
	  $configdmrgateway['XLX Network']['Startup'] = substr($dmrMasterHostArr3[3], 4);
	}

	// XLX StartUp TG
	if (empty($_POST['dmrMasterHost3Startup']) != TRUE ) {
	  $dmrMasterHost3Startup = escapeshellcmd($_POST['dmrMasterHost3Startup']);
	  if ($dmrMasterHost3Startup != "None") {
	    $configdmrgateway['XLX Network 1']['Startup'] = $dmrMasterHost3Startup;
	  }
	  else { unset($configdmrgateway['XLX Network 1']['Startup']); }
	}

	// Set JutterBuffer Option
	//if (empty($_POST['dmrDMRnetJitterBufer']) != TRUE ) {
	//  if (escapeshellcmd($_POST['dmrDMRnetJitterBufer']) == 'ON' ) { $configmmdvm['DMR Network']['JitterEnabled'] = "1"; $configysf2dmr['DMR Network']['JitterEnabled'] = "1"; }
	//  if (escapeshellcmd($_POST['dmrDMRnetJitterBufer']) == 'OFF' ) { $configmmdvm['DMR Network']['JitterEnabled'] = "0"; $configysf2dmr['DMR Network']['JitterEnabled'] = "0"; }
	//}
	unset($configmmdvm['DMR Network']['JitterEnabled']);

	// Set Talker Alias Option
	if (empty($_POST['dmrEmbeddedLCOnly']) != TRUE ) {
	  if (escapeshellcmd($_POST['dmrEmbeddedLCOnly']) == 'ON' ) { $configmmdvm['DMR']['EmbeddedLCOnly'] = "1"; }
	  if (escapeshellcmd($_POST['dmrEmbeddedLCOnly']) == 'OFF' ) { $configmmdvm['DMR']['EmbeddedLCOnly'] = "0"; }
	}

	// Set Dump TA Data Option for GPS support
	if (empty($_POST['dmrDumpTAData']) != TRUE ) {
	  if (escapeshellcmd($_POST['dmrDumpTAData']) == 'ON' ) { $configmmdvm['DMR']['DumpTAData'] = "1"; }
	  if (escapeshellcmd($_POST['dmrDumpTAData']) == 'OFF' ) { $configmmdvm['DMR']['DumpTAData'] = "0"; }
	}

	// Set the XLX DMRGateway Master On or Off
	if (empty($_POST['dmrGatewayXlxEn']) != TRUE ) {
	  if (escapeshellcmd($_POST['dmrGatewayXlxEn']) == 'ON' ) { $configdmrgateway['XLX Network 1']['Enabled'] = "1"; $configdmrgateway['XLX Network']['Enabled'] = "1"; }
	  if (escapeshellcmd($_POST['dmrGatewayXlxEn']) == 'OFF' ) { $configdmrgateway['XLX Network 1']['Enabled'] = "0"; $configdmrgateway['XLX Network']['Enabled'] = "0"; }
	}

	if (empty($_POST['dmrGatewayXlxEn']) && !empty($_POST['dmrMasterHost3'])) { $configdmrgateway['XLX Network 1']['Enabled'] = "0"; $configdmrgateway['XLX Network']['Enabled'] = "0"; }
	
	
	// Remove old settings
	if (isset($configmmdvm['General']['ModeHang'])) { unset($configmmdvm['General']['ModeHang']); }
	if (isset($configdmrgateway['General']['Timeout'])) { unset($configdmrgateway['General']['Timeout']); }
	if (isset($configmmdvm['General']['RFModeHang'])) { $configmmdvm['General']['RFModeHang'] = 300; }
	if (isset($configmmdvm['General']['NetModeHang'])) { $configmmdvm['General']['NetModeHang'] = 300; }


	// Set DMR Hang Timers
	if (empty($_POST['dmrRfHangTime']) != TRUE ) {
	  $configmmdvm['DMR']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['dmrRfHangTime']);
	  $configdmrgateway['General']['RFTimeout'] = preg_replace('/[^0-9]/', '', $_POST['dmrRfHangTime']);
	}
	if (empty($_POST['dmrNetHangTime']) != TRUE ) {
	  $configmmdvm['DMR Network']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['dmrNetHangTime']);
	  $configdmrgateway['General']['NetTimeout'] = preg_replace('/[^0-9]/', '', $_POST['dmrNetHangTime']);
	}
	// Set D-Star Hang Timers
	if (empty($_POST['dstarRfHangTime']) != TRUE ) {
	  $configmmdvm['D-Star']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['dstarRfHangTime']);
	}
	if (empty($_POST['dstarNetHangTime']) != TRUE ) {
	  $configmmdvm['D-Star Network']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['dstarNetHangTime']);
	}
	// Set YSF Hang Timers
	if (empty($_POST['ysfRfHangTime']) != TRUE ) {
	  $configmmdvm['System Fusion']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['ysfRfHangTime']);
	}
	if (empty($_POST['ysfNetHangTime']) != TRUE ) {
	  $configmmdvm['System Fusion Network']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['ysfNetHangTime']);
	}
	// Set P25 Hang Timers
	if (empty($_POST['p25RfHangTime']) != TRUE ) {
	  $configmmdvm['P25']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['p25RfHangTime']);
	}
	if (empty($_POST['p25NetHangTime']) != TRUE ) {
	  $configmmdvm['P25 Network']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['p25NetHangTime']);
	}
	// Set NXDN Hang Timers
	if (empty($_POST['nxdnRfHangTime']) != TRUE ) {
	  $configmmdvm['NXDN']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['nxdnRfHangTime']);
	}
	if (empty($_POST['nxdnNetHangTime']) != TRUE ) {
	  $configmmdvm['NXDN Network']['ModeHang'] = preg_replace('/[^0-9]/', '', $_POST['nxdnNetHangTime']);
	}

	// Set the hardware type
	if (empty($_POST['confHardware']) != TRUE ) {
	$confHardware = escapeshellcmd($_POST['confHardware']);
	$configModem['Modem']['Hardware'] = $confHardware;

	  if ( $confHardware == 'dvmpis' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyAMA0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=2" /etc/dstarrepeater';
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'dvmpid' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyAMA0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=3" /etc/dstarrepeater';
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'dvmuadu' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyUSB0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=3" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyUSB0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'dvmuada' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyACM0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=3" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'dvmbss' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyUSB0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=2" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyUSB0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'dvmbsd' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyUSB0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=3" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyUSB0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'dvmuagmsku' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyUSB0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=0" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyUSB0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	  }

	  if ( $confHardware == 'dvmuagmska' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVMEGA" /etc/dstarrepeater';
	    $rollDVMegaPort = 'sudo sed -i "/dvmegaPort=/c\\dvmegaPort=/dev/ttyACM0" /etc/dstarrepeater';
	    $rollDVMegaVariant = 'sudo sed -i "/dvmegaVariant=/c\\dvmegaVariant=0" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	    system($rollModemType);
	    system($rollDVMegaPort);
	    system($rollDVMegaVariant);
	  }

	  if ( $confHardware == 'dvrptr1' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DV-RPTR V1" /etc/dstarrepeater';
	    $rollDVRPTRPort = 'sudo sed -i "/dvrptr1Port=/c\\dvrptr1Port=/dev/ttyACM0" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	    system($rollModemType);
	    system($rollDVRPTRPort);
	  }

	  if ( $confHardware == 'dvrptr2' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DV-RPTR V2" /etc/dstarrepeater';
	    $rollDVRPTRPort = 'sudo sed -i "/dvrptr1Port=/c\\dvrptr1Port=/dev/ttyACM0" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	    system($rollModemType);
	    system($rollDVRPTRPort);
	  }

	  if ( $confHardware == 'dvrptr3' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DV-RPTR V3" /etc/dstarrepeater';
	    $rollDVRPTRPort = 'sudo sed -i "/dvrptr1Port=/c\\dvrptr1Port=/dev/ttyACM0" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	    system($rollModemType);
	    system($rollDVRPTRPort);
	  }

	  if ( $confHardware == 'gmsk_modem' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=GMSK Modem" /etc/dstarrepeater';
	    system($rollModemType);
	  }

	  if ( $confHardware == 'dvap' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=DVAP" /etc/dstarrepeater';
            $configmmdvm['Modem']['Port'] = "/dev/ttyUSB0";
	    system($rollModemType);
	  }

	  if ( $confHardware == 'zumspotlibre' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'zumspotusb' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'zumspotgpio' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'zumradiopigpio' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	  }

	  if ( $confHardware == 'zum' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
            $configmmdvm['Modem']['Port'] = "/dev/ttyACM0";
	  }

	  if ( $confHardware == 'stm32dvm' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	  }

	  if ( $confHardware == 'stm32usb' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyUSB0";
	  }

	  if ( $confHardware == 'f4mgpio' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	  }

	  if ( $confHardware == 'mmdvmhshat' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'mmdvmhshatd' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    $configmmdvm['General']['Duplex'] = 1;
	  }

	  if ( $confHardware == 'mmdvmmdohat' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'mmdvmvyehat' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'mnnano-spot' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyAMA0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }

	  if ( $confHardware == 'mnnano-teensy' ) {
	    $rollModemType = 'sudo sed -i "/modemType=/c\\modemType=MMDVM" /etc/dstarrepeater';
	    system($rollModemType);
	    $configmmdvm['Modem']['Port'] = "/dev/ttyUSB0";
	    $configmmdvm['General']['Duplex'] = 0;
	  }
	}

	// Set the Dashboard Public
	if (empty($_POST['dashAccess']) != TRUE ) {
	  $publicDashboard = 'sudo sed -i \'/$DAEMON -a $ipVar 80/c\\\t\t$DAEMON -a $ipVar 80 80 TCP > /dev/null 2>&1 &\' /usr/local/sbin/pistar-upnp.service';
	  $privateDashboard = 'sudo sed -i \'/$DAEMON -a $ipVar 80/ s/^#*/#/\' /usr/local/sbin/pistar-upnp.service';

	  if (escapeshellcmd($_POST['dashAccess']) == 'PUB' ) { system($publicDashboard); }
	  if (escapeshellcmd($_POST['dashAccess']) == 'PRV' ) { system($privateDashboard); }
	}

	// Set the ircDDBGateway Remote Public
	if (empty($_POST['ircRCAccess']) != TRUE ) {
	  $publicRCirc = 'sudo sed -i \'/$DAEMON -a $ipVar 10022/c\\\t\t$DAEMON -a $ipVar 10022 10022 UDP > /dev/null 2>&1 &\' /usr/local/sbin/pistar-upnp.service';
	  $privateRCirc = 'sudo sed -i \'/$DAEMON -a $ipVar 10022/ s/^#*/#/\' /usr/local/sbin/pistar-upnp.service';

	  if (escapeshellcmd($_POST['ircRCAccess']) == 'PUB' ) { system($publicRCirc); }
	  if (escapeshellcmd($_POST['ircRCAccess']) == 'PRV' ) { system($privateRCirc); }
	}

	// Set SSH Access Public
	if (empty($_POST['sshAccess']) != TRUE ) {
	  $publicSSH = 'sudo sed -i \'/$DAEMON -a $ipVar 22/c\\\t\t$DAEMON -a $ipVar 22 22 TCP > /dev/null 2>&1 &\' /usr/local/sbin/pistar-upnp.service';
	  $privateSSH = 'sudo sed -i \'/$DAEMON -a $ipVar 22/ s/^#*/#/\' /usr/local/sbin/pistar-upnp.service';

	  if (escapeshellcmd($_POST['sshAccess']) == 'PUB' ) { system($publicSSH); }
	  if (escapeshellcmd($_POST['sshAccess']) == 'PRV' ) { system($privateSSH); }
	}


	// Set MMDVMHost DMR Mode
	if (empty($_POST['MMDVMModeDMR']) != TRUE ) {
	  if (escapeshellcmd($_POST['MMDVMModeDMR']) == 'ON' )  { $configmmdvm['DMR']['Enable'] = "1"; $configmmdvm['DMR Network']['Enable'] = "1"; }
	  if (escapeshellcmd($_POST['MMDVMModeDMR']) == 'OFF' ) { $configmmdvm['DMR']['Enable'] = "0"; $configmmdvm['DMR Network']['Enable'] = "0"; }
	}

	// Set MMDVMHost D-Star Mode
	if (empty($_POST['MMDVMModeDSTAR']) != TRUE ) {
          if (escapeshellcmd($_POST['MMDVMModeDSTAR']) == 'ON' )  { $configmmdvm['D-Star']['Enable'] = "1"; $configmmdvm['D-Star Network']['Enable'] = "1"; }
          if (escapeshellcmd($_POST['MMDVMModeDSTAR']) == 'OFF' ) { $configmmdvm['D-Star']['Enable'] = "0"; $configmmdvm['D-Star Network']['Enable'] = "0"; }
	}

	// Set MMDVMHost Fusion Mode
	if (empty($_POST['MMDVMModeFUSION']) != TRUE ) {
          if (escapeshellcmd($_POST['MMDVMModeFUSION']) == 'ON' )  { $configmmdvm['System Fusion']['Enable'] = "1"; $configmmdvm['System Fusion Network']['Enable'] = "1"; }
          if (escapeshellcmd($_POST['MMDVMModeFUSION']) == 'OFF' ) { $configmmdvm['System Fusion']['Enable'] = "0"; $configmmdvm['System Fusion Network']['Enable'] = "0"; }
	}

	// Set MMDVMHost P25 Mode
	if (empty($_POST['MMDVMModeP25']) != TRUE ) {
          if (escapeshellcmd($_POST['MMDVMModeP25']) == 'ON' )  { $configmmdvm['P25']['Enable'] = "1"; $configmmdvm['P25 Network']['Enable'] = "1"; }
          if (escapeshellcmd($_POST['MMDVMModeP25']) == 'OFF' ) { $configmmdvm['P25']['Enable'] = "0"; $configmmdvm['P25 Network']['Enable'] = "0"; }
	}
	
	// Set MMDVMHost NXDN Mode
	if (empty($_POST['MMDVMModeNXDN']) != TRUE ) {
          if (escapeshellcmd($_POST['MMDVMModeNXDN']) == 'ON' )  { $configmmdvm['NXDN']['Enable'] = "1"; $configmmdvm['NXDN Network']['Enable'] = "1"; }
          if (escapeshellcmd($_POST['MMDVMModeNXDN']) == 'OFF' ) { $configmmdvm['NXDN']['Enable'] = "0"; $configmmdvm['NXDN Network']['Enable'] = "0"; }
	}

	// Set YSF2DMR Mode
	if (empty($_POST['MMDVMModeYSF2DMR']) != TRUE ) {
          if (escapeshellcmd($_POST['MMDVMModeYSF2DMR']) == 'ON' )  { $configysf2dmr['Enabled']['Enabled'] = "1"; }
          if (escapeshellcmd($_POST['MMDVMModeYSF2DMR']) == 'OFF' ) { $configysf2dmr['Enabled']['Enabled'] = "0"; }
	}

// Set the MMDVMHost Display Type
	if  (empty($_POST['mmdvmDisplayType']) != TRUE ) {
	  $configmmdvm['General']['Display'] = escapeshellcmd($_POST['mmdvmDisplayType']);
	}

	// Set the MMDVMHost Display Type
	if  (empty($_POST['mmdvmDisplayPort']) != TRUE ) {
	  $configmmdvm['TFT Serial']['Port'] = $_POST['mmdvmDisplayPort'];
	  $configmmdvm['Nextion']['Port'] = $_POST['mmdvmDisplayPort'];
	}

	// Set the Nextion Display Layout
	if (empty($_POST['mmdvmNextionDisplayType']) != TRUE ) {
	  if (escapeshellcmd($_POST['mmdvmNextionDisplayType']) == "G4KLX") { $configmmdvm['Nextion']['ScreenLayout'] = "0"; }
	  if (escapeshellcmd($_POST['mmdvmNextionDisplayType']) == "ON7LDS") { $configmmdvm['Nextion']['ScreenLayout'] = "2"; }
	}

	// Set MMDVMHost DMR Colour Code
	if (empty($_POST['dmrColorCode']) != TRUE ) {
          $configmmdvm['DMR']['ColorCode'] = escapeshellcmd($_POST['dmrColorCode']);
	}

	// Set Node Lock Status
	if (empty($_POST['nodeMode']) != TRUE ) {
	  if (escapeshellcmd($_POST['nodeMode']) == 'prv' ) {
            $configmmdvm['DMR']['SelfOnly'] = 1;
            $configmmdvm['D-Star']['SelfOnly'] = 1;
	    $configmmdvm['System Fusion']['SelfOnly'] = 1;
	    $configmmdvm['P25']['SelfOnly'] = 1;
	    $configmmdvm['NXDN']['SelfOnly'] = 1;
            system('sudo sed -i "/restriction=/c\\restriction=1" /etc/dstarrepeater');
          }
	  if (escapeshellcmd($_POST['nodeMode']) == 'pub' ) {
            $configmmdvm['DMR']['SelfOnly'] = 0;
            $configmmdvm['D-Star']['SelfOnly'] = 0;
	    $configmmdvm['System Fusion']['SelfOnly'] = 0;
	    $configmmdvm['P25']['SelfOnly'] = 0;
	    $configmmdvm['NXDN']['SelfOnly'] = 0;
            system('sudo sed -i "/restriction=/c\\restriction=0" /etc/dstarrepeater');
          }
	}


	// Set the Hostname
	if (empty($_POST['confHostame']) != TRUE ) {
	  $newHostnameLower = strtolower(preg_replace('/[^A-Za-z0-9\-]/', '', $_POST['confHostame']));
	  $currHostname = exec('cat /etc/hostname');
	  $rollHostname = 'sudo sed -i "s/'.$currHostname.'/'.$newHostnameLower.'/" /etc/hostname';
	  $rollHosts = 'sudo sed -i "s/'.$currHostname.'/'.$newHostnameLower.'/" /etc/hosts';
	  $rollMotd = 'sudo sed -i "s/'.$currHostname.'/'.$newHostnameLower.'/" /etc/motd';
	  system($rollHostname);
	  system($rollHosts);
	  system($rollMotd);
	  if (file_exists('/etc/hostapd/hostapd.conf')) {
		  // Update the Hotspot name to the Hostname
		  $rollApSsid = 'sudo sed -i "/ssid=/c\\ssid='.$newHostnameLower.'" /etc/hostapd/hostapd.conf';
		  system($rollApSsid);
	  }
	}

	// Add missing values to DMRGateway
	if (!isset($configdmrgateway['Info']['Enabled'])) { $configdmrgateway['Info']['Enabled'] = "0"; }
	if (!isset($configdmrgateway['Info']['Power'])) { $configdmrgateway['Info']['Power'] = $configmmdvm['Info']['Power']; }
	if (!isset($configdmrgateway['Info']['Height'])) { $configdmrgateway['Info']['Height'] = $configmmdvm['Info']['Height']; }
	if (!isset($configdmrgateway['XLX Network']['Enabled'])) { $configdmrgateway['XLX Network']['Enabled'] = "0"; }
	if (!isset($configdmrgateway['XLX Network']['File'])) { $configdmrgateway['XLX Network']['File'] = "/usr/local/etc/XLXHosts.txt"; }
	if (!isset($configdmrgateway['XLX Network']['Port'])) { $configdmrgateway['XLX Network']['Port'] = "62030"; }
	if (!isset($configdmrgateway['XLX Network']['Password'])) { $configdmrgateway['XLX Network']['Password'] = "passw0rd"; }
	if (!isset($configdmrgateway['XLX Network']['ReloadTime'])) { $configdmrgateway['XLX Network']['ReloadTime'] = "60"; }
	if (!isset($configdmrgateway['XLX Network']['Slot'])) { $configdmrgateway['XLX Network']['Slot'] = "2"; }
	if (!isset($configdmrgateway['XLX Network']['TG'])) { $configdmrgateway['XLX Network']['TG'] = "6"; }
	if (!isset($configdmrgateway['XLX Network']['Base'])) { $configdmrgateway['XLX Network']['Base'] = "64000"; }
	if (!isset($configdmrgateway['XLX Network']['Startup'])) { $configdmrgateway['XLX Network']['Startup'] = "950"; }
	if (!isset($configdmrgateway['XLX Network']['Relink'])) { $configdmrgateway['XLX Network']['Relink'] = "60"; }
	if (!isset($configdmrgateway['XLX Network']['Debug'])) { $configdmrgateway['XLX Network']['Debug'] = "0"; }
	if (!isset($configdmrgateway['DMR Network 3']['Enabled'])) { $configdmrgateway['DMR Network 3']['Enabled'] = "0"; }
	if (!isset($configdmrgateway['DMR Network 3']['Name'])) { $configdmrgateway['DMR Network 3']['Name'] = "HBLink"; }
	if (!isset($configdmrgateway['DMR Network 3']['Address'])) { $configdmrgateway['DMR Network 3']['Address'] = "1.2.3.4"; }
	if (!isset($configdmrgateway['DMR Network 3']['Port'])) { $configdmrgateway['DMR Network 3']['Port'] = "5555"; }
	if (!isset($configdmrgateway['DMR Network 3']['TGRewrite'])) { $configdmrgateway['DMR Network 3']['TGRewrite'] = "2,11,2,11,1"; }
	if (!isset($configdmrgateway['DMR Network 3']['Password'])) { $configdmrgateway['DMR Network 3']['Password'] = "PASSWORD"; }
	if (!isset($configdmrgateway['DMR Network 3']['Location'])) { $configdmrgateway['DMR Network 3']['Location'] = "0"; }
	if (!isset($configdmrgateway['DMR Network 3']['Debug'])) { $configdmrgateway['DMR Network 3']['Debug'] = "0"; }

	// Add missing options to MMDVMHost
	$configmmdvm['DMR Network']['Password'] = "none";
	if (!isset($configmmdvm['Modem']['RFLevel'])) { $configmmdvm['Modem']['RFLevel'] = "100"; }
	if (!isset($configmmdvm['Modem']['RXDCOffset'])) { $configmmdvm['Modem']['RXDCOffset'] = "0"; }
	if (!isset($configmmdvm['Modem']['TXDCOffset'])) { $configmmdvm['Modem']['TXDCOffset'] = "0"; }
	if (!isset($configmmdvm['Modem']['CWIdTXLevel'])) { $configmmdvm['Modem']['CWIdTXLevel'] = "50"; }
	if (!isset($configmmdvm['Modem']['NXDNTXLevel'])) { $configmmdvm['Modem']['NXDNTXLevel'] = "50"; }
	if (!isset($configmmdvm['D-Star']['AckReply'])) { $configmmdvm['D-Star']['AckReply'] = "1"; }
	if (!isset($configmmdvm['D-Star']['AckTime'])) { $configmmdvm['D-Star']['AckTime'] = "750"; }
	if (!isset($configmmdvm['D-Star']['RemoteGateway'])) { $configmmdvm['D-Star']['RemoteGateway'] = "0"; }
	if (!isset($configmmdvm['DMR']['BeaconInterval'])) { $configmmdvm['DMR']['BeaconInterval'] = "60"; }
	if (!isset($configmmdvm['DMR']['BeaconDuration'])) { $configmmdvm['DMR']['BeaconDuration'] = "3"; }
	if (!isset($configmmdvm['P25']['RemoteGateway'])) { $configmmdvm['P25']['RemoteGateway'] = "0"; }
	if (!isset($configmmdvm['OLED']['Scroll'])) { $configmmdvm['OLED']['Scroll'] = "0"; }
	if (!isset($configmmdvm['NXDN']['Enable'])) { $configmmdvm['NXDN']['Enable'] = "0"; }
	if (!isset($configmmdvm['NXDN']['RAN'])) { $configmmdvm['NXDN']['RAN'] = "1"; }
	if (!isset($configmmdvm['NXDN']['SelfOnly'])) { $configmmdvm['NXDN']['SelfOnly'] = "1"; }
	if (!isset($configmmdvm['NXDN']['RemoteGateway'])) { $configmmdvm['NXDN']['RemoteGateway'] = "0"; }
	if (!isset($configmmdvm['NXDN Network']['Enable'])) { $configmmdvm['NXDN Network']['Enable'] = "0"; }
	if (!isset($configmmdvm['NXDN Network']['LocalPort'])) { $configmmdvm['NXDN Network']['LocalPort'] = "3300"; }
	if (!isset($configmmdvm['NXDN Network']['GatewayAddress'])) { $configmmdvm['NXDN Network']['GatewayAddress'] = "127.0.0.1"; }
	if (!isset($configmmdvm['NXDN Network']['GatewayPort'])) { $configmmdvm['NXDN Network']['GatewayPort'] = "4300"; }
	if (!isset($configmmdvm['NXDN Network']['Debug'])) { $configmmdvm['NXDN Network']['Debug'] = "0"; }
	if (!isset($configmmdvm['NXDN Id Lookup']['File'])) { $configmmdvm['NXDN Id Lookup']['File'] = "/usr/local/etc/NXDN.csv"; }
	if (!isset($configmmdvm['NXDN Id Lookup']['Time'])) { $configmmdvm['NXDN Id Lookup']['Time'] = "24"; }

	// Add missing options to YSFGateway
	if (!isset($configysfgateway['Network']['Revert'])) { $configysfgateway['Network']['Revert'] = "0"; }
	if (!isset($configysfgateway['Network']['Port'])) { $configysfgateway['Network']['Port'] = "42000"; }
	if (!isset($configysfgateway['Network']['YSF2DMRAddress'])) { $configysfgateway['Network']['YSF2DMRAddress'] = "127.0.0.1"; }
	if (!isset($configysfgateway['Network']['YSF2DMRPort'])) { $configysfgateway['Network']['YSF2DMRPort'] = "42013"; }
	unset($configysfgateway['Network']['DataPort']);
	unset($configysfgateway['Network']['StatusPort']);

	// Add missing options to YSF2DMR
	if (!isset($configysf2dmr['Info']['Power'])) { $configysf2dmr['Info']['Power'] = "1"; }
	if (!isset($configysf2dmr['Info']['Height'])) { $configysf2dmr['Info']['Height'] = "0"; }
	if (!isset($configysf2dmr['YSF Network']['DstAddress'])) { $configysf2dmr['YSF Network']['DstAddress'] = "127.0.0.1"; }
	if (!isset($configysf2dmr['YSF Network']['DstPort'])) { $configysf2dmr['YSF Network']['DstPort'] = "42000"; }
	if (!isset($configysf2dmr['YSF Network']['LocalAddress'])) { $configysf2dmr['YSF Network']['LocalAddress'] = "127.0.0.1"; }
	if (!isset($configysf2dmr['YSF Network']['LocalPort'])) { $configysf2dmr['YSF Network']['LocalPort'] = "42013"; }
	if (!isset($configysf2dmr['YSF Network']['Daemon'])) { $configysf2dmr['YSF Network']['Daemon'] = "1"; }
	if (!isset($configysf2dmr['YSF Network']['EnableWiresX'])) { $configysf2dmr['YSF Network']['EnableWiresX'] = "1"; }
	if (!isset($configysf2dmr['DMR Network']['StartupDstId'])) { $configysf2dmr['DMR Network']['StartupDstId'] = "31672"; }
	if (!isset($configysf2dmr['DMR Network']['StartupPC'])) { $configysf2dmr['DMR Network']['StartupPC'] = "0"; }
	if (!isset($configysf2dmr['DMR Network']['Jitter'])) { $configysf2dmr['DMR Network']['Jitter'] = "500"; }
	if (!isset($configysf2dmr['DMR Network']['EnableUnlink'])) { $configysf2dmr['DMR Network']['EnableUnlink'] = "1"; }
	if (!isset($configysf2dmr['DMR Network']['TGUnlink'])) { $configysf2dmr['DMR Network']['TGUnlink'] = "4000"; }
	if (!isset($configysf2dmr['DMR Network']['PCUnlink'])) { $configysf2dmr['DMR Network']['PCUnlink'] = "0"; }	
	if (!isset($configysf2dmr['DMR Network']['Debug'])) { $configysf2dmr['DMR Network']['Debug'] = "0"; }
	if ( (!isset($configysf2dmr['DMR Network']['TGListFile'])) && (file_exists('/usr/local/etc/TGList_BM.txt')) ) { $configysf2dmr['DMR Network']['TGListFile'] = "/usr/local/etc/TGList_BM.txt"; }
	if (!isset($configysf2dmr['DMR Id Lookup']['File'])) { $configysf2dmr['DMR Id Lookup']['File'] = "/usr/local/etc/DMRIds.dat"; }
	if (!isset($configysf2dmr['DMR Id Lookup']['Time'])) { $configysf2dmr['DMR Id Lookup']['Time'] = "24"; }
	if (!isset($configysf2dmr['Log']['DisplayLevel'])) { $configysf2dmr['Log']['DisplayLevel'] = "1"; }
	if (!isset($configysf2dmr['Log']['FileLevel'])) { $configysf2dmr['Log']['FileLevel'] = "1"; }
	if (!isset($configysf2dmr['Log']['FilePath'])) { $configysf2dmr['Log']['FilePath'] = "/var/log/pi-star"; }
	if (!isset($configysf2dmr['Log']['FileRoot'])) { $configysf2dmr['Log']['FileRoot'] = "YSF2DMR"; }
	if (!isset($configysf2dmr['aprs.fi']['Enable'])) { $configysf2dmr['aprs.fi']['Enable'] = "0"; }
	if (!isset($configysf2dmr['aprs.fi']['Port'])) { $configysf2dmr['aprs.fi']['Port'] = "14580"; }
	if (!isset($configysf2dmr['aprs.fi']['Refresh'])) { $configysf2dmr['aprs.fi']['Refresh'] = "240"; }
	if (!isset($configysf2dmr['Enabled']['Enabled'])) { $configysf2dmr['Enabled']['Enabled'] = "0"; }
	unset($configysf2dmr['Info']['Enabled']);
	unset($configysf2dmr['DMR Network']['JitterEnabled']);


	// Clean up legacy options
	$dmrGatewayVer = exec("DMRGateway -v | awk {'print $3'} | cut -c 1-8");
	if ($dmrGatewayVer > 20170924) {
		unset($configdmrgateway['XLX Network 1']);
		unset($configdmrgateway['XLX Network 2']);
		unset($configmmdvm['NXDN Network']['LocalAddress']);
	}
	

	// Create the hostfiles.nodextra file if required
	if (empty($_POST['confHostFilesNoDExtra']) != TRUE ) {
		if (escapeshellcmd($_POST['confHostFilesNoDExtra']) == 'ON' )  {
			if (!file_exists('/etc/hostfiles.nodextra')) { system('sudo touch /etc/hostfiles.nodextra'); }
		}
		if (escapeshellcmd($_POST['confHostFilesNoDExtra']) == 'OFF' )  {
			if (file_exists('/etc/hostfiles.nodextra')) { system('sudo rm -rf /etc/hostfiles.nodextra'); }
		}
	}

	// Continue Page Output
	echo "<br />";
	echo "<table>\n";
	echo "<tr><th>Done...</th></tr>\n";
	echo "<tr><td>Changes applied, starting services...</td></tr>\n";
	echo "</table>\n";


	// MMDVMHost config file wrangling
	$mmdvmContent = "";
	foreach($configmmdvm as $mmdvmSection=>$mmdvmValues) {
		// UnBreak special cases
		$mmdvmSection = str_replace("_", " ", $mmdvmSection);
		$mmdvmContent .= "[".$mmdvmSection."]\n";
                // append the values
                foreach($mmdvmValues as $mmdvmKey=>$mmdvmValue) {
			$mmdvmContent .= $mmdvmKey."=".$mmdvmValue."\n";
			}
			$mmdvmContent .= "\n";
		}

	if (!$handleMMDVMHostConfig = fopen('/tmp/bW1kdm1ob3N0DQo.tmp', 'w')) {
		return false;
	}
	if (!is_writable('/tmp/bW1kdm1ob3N0DQo.tmp')) {
          echo "<br />\n";
          echo "<table>\n";
          echo "<tr><th>ERROR</th></tr>\n";
          echo "<tr><td>Unable to write configuration file(s)...</td><tr>\n";
          echo "<tr><td>Please wait a few seconds and retry...</td></tr>\n";
          echo "</table>\n";
          unset($_POST);
          echo '<script type="text/javascript">setTimeout(function() { window.location=window.location;},5000);</script>';
          die();
	}
	else {
		$success = fwrite($handleMMDVMHostConfig, $mmdvmContent);
		fclose($handleMMDVMHostConfig);
		if (intval(exec('cat /tmp/bW1kdm1ob3N0DQo.tmp | wc -l')) > 140 ) {
			exec('sudo mv /tmp/bW1kdm1ob3N0DQo.tmp /etc/mmdvmhost');		// Move the file back
			exec('sudo chmod 644 /etc/mmdvmhost');					// Set the correct runtime permissions
			exec('sudo chown root:root /etc/mmdvmhost');				// Set the owner
		}
	}

        // ysfgateway config file wrangling
	$ysfgwContent = "";
        foreach($configysfgateway as $ysfgwSection=>$ysfgwValues) {
                // UnBreak special cases
                $ysfgwSection = str_replace("_", " ", $ysfgwSection);
                $ysfgwContent .= "[".$ysfgwSection."]\n";
                // append the values
                foreach($ysfgwValues as $ysfgwKey=>$ysfgwValue) {
                        $ysfgwContent .= $ysfgwKey."=".$ysfgwValue."\n";
                        }
                        $ysfgwContent .= "\n";
                }

        if (!$handleYSFGWconfig = fopen('/tmp/eXNmZ2F0ZXdheQ.tmp', 'w')) {
                return false;
        }

	if (!is_writable('/tmp/eXNmZ2F0ZXdheQ.tmp')) {
          echo "<br />\n";
          echo "<table>\n";
          echo "<tr><th>ERROR</th></tr>\n";
          echo "<tr><td>Unable to write configuration file(s)...</td><tr>\n";
          echo "<tr><td>Please wait a few seconds and retry...</td></tr>\n";
          echo "</table>\n";
          unset($_POST);
          echo '<script type="text/javascript">setTimeout(function() { window.location=window.location;},5000);</script>';
          die();
	}
	else {
	        $success = fwrite($handleYSFGWconfig, $ysfgwContent);
	        fclose($handleYSFGWconfig);
		if (intval(exec('cat /tmp/eXNmZ2F0ZXdheQ.tmp | wc -l')) > 35 ) {
			exec('sudo mv /tmp/eXNmZ2F0ZXdheQ.tmp /etc/ysfgateway');		// Move the file back
			exec('sudo chmod 644 /etc/ysfgateway');					// Set the correct runtime permissions
			exec('sudo chown root:root /etc/ysfgateway');				// Set the owner
		}
	}

        // ysf2dmr config file wrangling
        $ysf2dmrContent = "";
        foreach($configysf2dmr as $ysf2dmrSection=>$ysf2dmrValues) {
                // UnBreak special cases
                $ysf2dmrSection = str_replace("_", " ", $ysf2dmrSection);
                $ysf2dmrContent .= "[".$ysf2dmrSection."]\n";
                // append the values
                foreach($ysf2dmrValues as $ysf2dmrKey=>$ysf2dmrValue) {
                        $ysf2dmrContent .= $ysf2dmrKey."=".$ysf2dmrValue."\n";
                        }
                        $ysf2dmrContent .= "\n";
                }

        if (!$handleYSF2DMRconfig = fopen('/tmp/dsWGR34tHRrSFFGA.tmp', 'w')) {
                return false;
        }

        if (!is_writable('/tmp/dsWGR34tHRrSFFGA.tmp')) {
          echo "<br />\n";
          echo "<table>\n";
          echo "<tr><th>ERROR</th></tr>\n";
          echo "<tr><td>Unable to write configuration file(s)...</td><tr>\n";
          echo "<tr><td>Please wait a few seconds and retry...</td></tr>\n";
          echo "</table>\n";
          unset($_POST);
          echo '<script type="text/javascript">setTimeout(function() { window.location=window.location;},5000);</script>';
          die();
        }
        else {
                $success = fwrite($handleYSF2DMRconfig, $ysf2dmrContent);
                fclose($handleYSF2DMRconfig);
                if (intval(exec('cat /tmp/dsWGR34tHRrSFFGA.tmp | wc -l')) > 35 ) {
                        exec('sudo mv /tmp/dsWGR34tHRrSFFGA.tmp /etc/ysf2dmr');                 // Move the file back
                        exec('sudo chmod 644 /etc/ysf2dmr');                                    // Set the correct runtime permissions
                        exec('sudo chown root:root /etc/ysf2dmr');                              // Set the owner
                }
        }



	// dmrgateway config file wrangling
	$dmrgwContent = "";
        foreach($configdmrgateway as $dmrgwSection=>$dmrgwValues) {
                // UnBreak special cases
                $dmrgwSection = str_replace("_", " ", $dmrgwSection);
                $dmrgwContent .= "[".$dmrgwSection."]\n";
                // append the values
                foreach($dmrgwValues as $dmrgwKey=>$dmrgwValue) {
                        $dmrgwContent .= $dmrgwKey."=".$dmrgwValue."\n";
                        }
                        $dmrgwContent .= "\n";
                }
        if (!$handledmrGWconfig = fopen('/tmp/k4jhdd34jeFr8f.tmp', 'w')) {
                return false;
        }
	if (!is_writable('/tmp/k4jhdd34jeFr8f.tmp')) {
          echo "<br />\n";
          echo "<table>\n";
          echo "<tr><th>ERROR</th></tr>\n";
          echo "<tr><td>Unable to write configuration file(s)...</td><tr>\n";
          echo "<tr><td>Please wait a few seconds and retry...</td></tr>\n";
          echo "</table>\n";
          unset($_POST);
          echo '<script type="text/javascript">setTimeout(function() { window.location=window.location;},5000);</script>';
          die();
	}
	else {
	        $success = fwrite($handledmrGWconfig, $dmrgwContent);
	        fclose($handledmrGWconfig);
		if (fopen($dmrGatewayConfigFile,'r')) {
			if (intval(exec('cat /tmp/k4jhdd34jeFr8f.tmp | wc -l')) > 55 ) {
          			exec('sudo mv /tmp/k4jhdd34jeFr8f.tmp /etc/dmrgateway');	// Move the file back
          			exec('sudo chmod 644 /etc/dmrgateway');				// Set the correct runtime permissions
	 			exec('sudo chown root:root /etc/dmrgateway');			// Set the owner
			}
		}
	}

	// modem config file wrangling
        $configModemContent = "";
        foreach($configModem as $configModemSection=>$configModemValues) {
                // UnBreak special cases
                $configModemSection = str_replace("_", " ", $configModemSection);
                $configModemContent .= "[".$configModemSection."]\n";
                // append the values
                foreach($configModemValues as $modemKey=>$modemValue) {
                        $configModemContent .= $modemKey."=".$modemValue."\n";
                        }
                        $configModemContent .= "\n";
                }

        if (!$handleModemConfig = fopen('/tmp/sja7hFRkw4euG7.tmp', 'w')) {
                return false;
        }

        if (!is_writable('/tmp/sja7hFRkw4euG7.tmp')) {
          echo "<br />\n";
          echo "<table>\n";
          echo "<tr><th>ERROR</th></tr>\n";
          echo "<tr><td>Unable to write configuration file(s)...</td><tr>\n";
          echo "<tr><td>Please wait a few seconds and retry...</td></tr>\n";
          echo "</table>\n";
          unset($_POST);
          echo '<script type="text/javascript">setTimeout(function() { window.location=window.location;},5000);</script>';
          die();
        }
	else {
                $success = fwrite($handleModemConfig, $configModemContent);
                fclose($handleModemConfig);
		if (file_exists('/etc/dstar-radio.dstarrepeater')) {
                    if (fopen($modemConfigFileDStarRepeater,'r')) {
                        exec('sudo mv /tmp/sja7hFRkw4euG7.tmp '.$modemConfigFileDStarRepeater);	// Move the file back
                        exec('sudo chmod 644 $modemConfigFileDStarRepeater');			// Set the correct runtime permissions
                        exec('sudo chown root:root $modemConfigFileDStarRepeater');			// Set the owner
                    }
		}
		if (file_exists('/etc/dstar-radio.mmdvmhost')) {
                    if (fopen($modemConfigFileMMDVMHost,'r')) {
                        exec('sudo mv /tmp/sja7hFRkw4euG7.tmp '.$modemConfigFileMMDVMHost);		// Move the file back
                        exec('sudo chmod 644 $modemConfigFileMMDVMHost');				// Set the correct runtime permissions
                        exec('sudo chown root:root $modemConfigFileMMDVMHost');			// Set the owner
                    }
		}
        }


	// Start the DV Services
	system('sudo systemctl daemon-reload > /dev/null 2>/dev/null &');			// Restart Systemd to account for any service changes
	system('sudo systemctl start dstarrepeater.service > /dev/null 2>/dev/null &');		// D-Star Radio Service
	system('sudo systemctl start mmdvmhost.service > /dev/null 2>/dev/null &');		// MMDVMHost Radio Service
	system('sudo systemctl start ircddbgateway.service > /dev/null 2>/dev/null &');		// ircDDBGateway Service
	system('sudo systemctl start timeserver.service > /dev/null 2>/dev/null &');		// Time Server Service
	system('sudo systemctl start pistar-watchdog.service > /dev/null 2>/dev/null &');	// PiStar-Watchdog Service
	system('sudo systemctl start pistar-remote.service > /dev/null 2>/dev/null &');		// PiStar-Remote Service
	system('sudo systemctl start pistar-upnp.service > /dev/null 2>/dev/null &');		// PiStar-UPnP Service
	system('sudo systemctl start ysf2dmr.service > /dev/null 2>/dev/null &');		// YSF2DMR
	system('sudo systemctl start ysfgateway.service > /dev/null 2>/dev/null &');		// YSFGateway
	system('sudo systemctl start ysfparrot.service > /dev/null 2>/dev/null &');		// YSFParrot
	system('sudo systemctl start p25gateway.service > /dev/null 2>/dev/null &');		// P25Gateway
	system('sudo systemctl start p25parrot.service > /dev/null 2>/dev/null &');		// P25Parrot
	system('sudo systemctl start dmrgateway.service > /dev/null 2>/dev/null &');		// DMRGateway

	// Set the system timezone
	$rollTimeZone = 'sudo timedatectl set-timezone '.escapeshellcmd($_POST['systemTimezone']);
	system($rollTimeZone);
	$rollTimeZoneConfig = 'sudo sed -i "/date_default_timezone_set/c\\date_default_timezone_set(\''.escapeshellcmd($_POST['systemTimezone']).'\')\;" /var/www/dashboard/config/config.php';
	system($rollTimeZoneConfig);

	// Start Cron (occasionally remounts root as RO - would be bad if it did this at the wrong time....)
	system('sudo systemctl start cron.service > /dev/null 2>/dev/null &');			//Cron

	unset($_POST);
	echo '<script type="text/javascript">setTimeout(function() { window.location.replace("../index.php");},7500);</script>';

	// Make the root filesystem read-only
	system('sudo mount -o remount,ro /');

} else { ?>
<div class="page" data-name="settings">
  <div class="navbar">
    <div class="navbar-inner sliding">
      <div class="title">Pi-Star Settings</div>
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

<?php	if ((file_exists('/etc/dstar-radio.mmdvmhost') || file_exists('/etc/dstar-radio.dstarrepeater')) && !$configModem['Modem']['Hardware']) { echo "<script type\"text/javascript\">\n\talert(\"WARNING:\\nThe Modem selection section has been updated,\\nPlease re-select your modem from the list.\")\n</script>\n"; }
?>

<form id="config" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
	
<?php if (file_exists('/etc/dstar-radio.mmdvmhost')) { ?>
    <input type="hidden" name="MMDVMModeDMR" value="OFF" />
    <input type="hidden" name="MMDVMModeDSTAR" value="OFF" />
    <input type="hidden" name="MMDVMModeFUSION" value="OFF" />
    <input type="hidden" name="MMDVMModeP25" value="OFF" />
    <input type="hidden" name="MMDVMModeNXDN" value="OFF" />
    <input type="hidden" name="MMDVMModeYSF2DMR" value="OFF" />
	<div><b><?php echo $lang['mmdvmhost_config'];?></b></div>
    <table>
    <tr>
    <th width="130"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
    <th colspan="2"><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from<br />the configuration files</span></a></th>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_mode'];?>:<span><b>DMR Mode</b>Turn on DMR Features</span></a></td>
    <?php
	if ( $configmmdvm['DMR']['Enable'] == 1 ) {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-dmr\"  type=\"checkbox\" name=\"MMDVMModeDMR\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-dmr\"></label></div></td>\n";
		}
	else {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-dmr\"  type=\"checkbox\" name=\"MMDVMModeDMR\" value=\"ON\" /><label for=\"toggle-dmr\"></label></div></td>\n";
	}
    ?>
    <td>RF Hangtime: <input type="text" name="dmrRfHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['DMR']['ModeHang'])) { echo $configmmdvm['DMR']['ModeHang']; } else { echo "20"; } ?>" />
    Net Hangtime: <input type="text" name="dmrNetHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['DMR Network']['ModeHang'])) { echo $configmmdvm['DMR Network']['ModeHang']; } else { echo "20"; } ?>" />
    </td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['d-star_mode'];?>:<span><b>D-Star Mode</b>Turn on D-Star Features</span></a></td>
    <?php
	if ( $configmmdvm['D-Star']['Enable'] == 1 ) {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-dstar\"  type=\"checkbox\" name=\"MMDVMModeDSTAR\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-dstar\"></label></div></td>\n";
		}
	else {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-dstar\"  type=\"checkbox\" name=\"MMDVMModeDSTAR\" value=\"ON\" /><label for=\"toggle-dstar\"></label></div></td>\n";
	}
    ?>
    <td>RF Hangtime: <input type="text" name="dstarRfHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['D-Star']['ModeHang'])) { echo $configmmdvm['D-Star']['ModeHang']; } else { echo "20"; } ?>" />
    Net Hangtime: <input type="text" name="dstarNetHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['D-Star Network']['ModeHang'])) { echo $configmmdvm['D-Star Network']['ModeHang']; } else { echo "20"; } ?>" />
    </td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['ysf_mode'];?>:<span><b>YSF Mode</b>Turn on YSF Features</span></a></td>
    <?php
	if ( $configmmdvm['System Fusion']['Enable'] == 1 ) {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-ysf\"  type=\"checkbox\" name=\"MMDVMModeFUSION\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-ysf\"></label></div></td>\n";
		}
	else {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-ysf\"  type=\"checkbox\" name=\"MMDVMModeFUSION\" value=\"ON\" /><label for=\"toggle-ysf\"></label></div></td>\n";
	}
    ?>
    <td>RF Hangtime: <input type="text" name="ysfRfHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['System Fusion']['ModeHang'])) { echo $configmmdvm['System Fusion']['ModeHang']; } else { echo "20"; } ?>" />
    Net Hangtime: <input type="text" name="ysfNetHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['System Fusion Network']['ModeHang'])) { echo $configmmdvm['System Fusion Network']['ModeHang']; } else { echo "20"; } ?>" />
    </td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['p25_mode'];?>:<span><b>P25 Mode</b>Turn on P25 Features</span></a></td>
    <?php
	if ( $configmmdvm['P25']['Enable'] == 1 ) {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-p25\"  type=\"checkbox\" name=\"MMDVMModeP25\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-p25\"></label></div></td>\n";
		}
	else {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-p25\"  type=\"checkbox\" name=\"MMDVMModeP25\" value=\"ON\" /><label for=\"toggle-p25\"></label></div></td>\n";
	}
    ?>
    <td>RF Hangtime: <input type="text" name="p25RfHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['P25']['ModeHang'])) { echo $configmmdvm['P25']['ModeHang']; } else { echo "20"; } ?>" />
    Net Hangtime: <input type="text" name="p25NetHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['P25 Network']['ModeHang'])) { echo $configmmdvm['P25 Network']['ModeHang']; } else { echo "20"; } ?>" />
    </td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['nxdn_mode'];?>:<span><b>NXDN Mode</b>Turn on NXDN Features</span></a></td>
    <?php
	if ( $configmmdvm['NXDN']['Enable'] == 1 ) {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-nxdn\"  type=\"checkbox\" name=\"MMDVMModeNXDN\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-nxdn\"></label></div></td>\n";
		}
	else {
		echo "<td align=\"left\"><div class=\"switch\"><input id=\"toggle-nxdn\"  type=\"checkbox\" name=\"MMDVMModeNXDN\" value=\"ON\" /><label for=\"toggle-nxdn\"></label></div></td>\n";
	}
    ?>
    <td>RF Hangtime: <input type="text" name="nxdnRfHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['NXDN']['ModeHang'])) { echo $configmmdvm['NXDN']['ModeHang']; } else { echo "20"; } ?>" />
    Net Hangtime: <input type="text" name="nxdnNetHangTime" size="7" maxlength="3" value="<?php if (isset($configmmdvm['NXDN Network']['ModeHang'])) { echo $configmmdvm['NXDN Network']['ModeHang']; } else { echo "20"; } ?>" />
    </td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#">YSF2DMR:<span><b>YSF2DMR Mode</b>Turn on YSF2DMR Features</span></a></td>
    <?php
	if ( $configysf2dmr['Enabled']['Enabled'] == 1 ) {
		echo "<td colspan=\"2\" align=\"left\"><div class=\"switch\"><input id=\"toggle-ysf2dmr\"  type=\"checkbox\" name=\"MMDVMModeYSF2DMR\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-ysf2dmr\"></label></div></td>\n";
		}
	else {
		echo "<td colspan=\"2\" align=\"left\"><div class=\"switch\"><input id=\"toggle-ysf2dmr\"  type=\"checkbox\" name=\"MMDVMModeYSF2DMR\" value=\"ON\" /><label for=\"toggle-ysf2dmr\"></label></div></td>\n";
	}
    ?>
    </tr>
  <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['mmdvm_display'];?>:<span><b>Display Type</b>Choose your display<br />type if you have one.</span></a></td>
    <td align="left" colspan="2"><select name="mmdvmDisplayType">
	    <option <?php if (($configmmdvm['General']['Display'] == "None") || ($configmmdvm['General']['Display'] == "") ) {echo 'selected="selected" ';}; ?>value="None">None</option>
	    <option <?php if ($configmmdvm['General']['Display'] == "OLED") {echo 'selected="selected" ';}; ?>value="OLED">OLED</option>
	    <option <?php if ($configmmdvm['General']['Display'] == "Nextion") {echo 'selected="selected" ';}; ?>value="Nextion">Nextion</option>
	    <option <?php if ($configmmdvm['General']['Display'] == "HD44780") {echo 'selected="selected" ';}; ?>value="HD44780">HD44780</option>
	    <option <?php if ($configmmdvm['General']['Display'] == "TFT Serial") {echo 'selected="selected" ';}; ?>value="TFT Serial">TFT Serial</option>
	    <option <?php if ($configmmdvm['General']['Display'] == "LCDproc") {echo 'selected="selected" ';}; ?>value="LCDproc">LCDproc</option>
	    </select>
	    Port: <select name="mmdvmDisplayPort">
	    <option <?php if (($configmmdvm['General']['Display'] == "None") || ($configmmdvm['General']['Display'] == "") ) {echo 'selected="selected" ';}; ?>value="None">None</option>
	    <option <?php if ($configmmdvm['Nextion']['Port'] == "modem") {echo 'selected="selected" ';}; ?>value="modem">Modem</option>
	    <option <?php if ($configmmdvm['Nextion']['Port'] == "/dev/ttyAMA0") {echo 'selected="selected" ';}; ?>value="/dev/ttyAMA0">/dev/ttyAMA0</option>
	    <option <?php if ($configmmdvm['Nextion']['Port'] == "/dev/ttyUSB0") {echo 'selected="selected" ';}; ?>value="/dev/ttyUSB0">/dev/ttyUSB0</option>
	    <?php if (file_exists('/dev/ttyS2')) { ?>
	    	<option <?php if ($configmmdvm['Nextion']['Port'] == "/dev/ttyS2") {echo 'selected="selected" ';}; ?>value="/dev/ttyS2">/dev/ttyS2</option>
    	    <?php } ?>
	    <?php if (file_exists('/dev/ttyNextionDriver')) { ?>
	    	<option <?php if ($configmmdvm['Nextion']['Port'] == "/dev/ttyNextionDriver") {echo 'selected="selected" ';}; ?>value="/dev/ttyNextionDriver">/dev/ttyNextionDriver</option>
    	    <?php } ?>
	    </select>
	    Nextion Layout: <select name="mmdvmNextionDisplayType">
	    <option <?php if ($configmmdvm['Nextion']['ScreenLayout'] == "0") {echo 'selected="selected" ';}; ?>value="G4KLX">G4KLX</option>
	    <option <?php if ($configmmdvm['Nextion']['ScreenLayout'] == "2") {echo 'selected="selected" ';}; ?>value="ON7LDS">ON7LDS</option>
	    </select>
    </td></tr>
    </table>
	<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>" \ /></form><br /><br /></div>
    <?php } ?>








<div><b><?php echo $lang['general_config'];?></b></div>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <table>
    <tr>
    <th width="351"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
    <th><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from the<br />configuration files</span></a></th>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#">Hostname:<span><b>System Hostname</b>This is the system<br />hostname, used for access<br />to the dashboard etc.</span></a></td>
    <td align="left"><input type="text" name="confHostame" size="13" maxlength="15" value="<?php echo exec('cat /etc/hostname'); ?>" />Do not add suffixes such as .local</td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['node_call'];?>:<span><b>Gateway Callsign</b>This is your licenced callsign for use<br />on this gateway, do not append<br />the "G"</span></a></td>
    <td align="left"><input type="text" name="confCallsign" size="13" maxlength="7" value="<?php echo $configs['gatewayCallsign'] ?>" /></td>
    </tr>
    <?php if (file_exists('/etc/dstar-radio.mmdvmhost') && (($configmmdvm['DMR']['Enable'] == 1) || ($configmmdvm['P25']['Enable'] == 1 ))) { ?>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_id'];?>:<span><b>CCS7/DMR ID</b>Enter your CCS7 / DMR ID here</span></a></td>
    <td align="left"><input type="text" name="dmrId" size="13" maxlength="9" value="<?php if (isset($configmmdvm['General']['Id'])) { echo $configmmdvm['General']['Id']; } else { echo $configmmdvm['DMR']['Id']; } ?>" /></td>
    </tr><?php } ?>
    <?php if (file_exists('/etc/dstar-radio.mmdvmhost') && ($configmmdvm['NXDN']['Enable'] == 1)) { ?>
    <tr>
      <td align="left"><a class="tooltip2" href="#">NXDN ID:<span><b>NXDN ID</b>Enter your NXDN ID here</span></a></td>
      <td align="left"><input type="text" name="nxdnId" size="13" maxlength="5" value="<?php if (isset($configmmdvm['NXDN']['Id'])) { echo $configmmdvm['NXDN']['Id']; } ?>" /></td>
    </tr><?php } ?>
<?php if ($configmmdvm['Info']['TXFrequency'] === $configmmdvm['Info']['RXFrequency']) {
	echo "    <tr>\n";
	echo "    <td align=\"left\"><a class=\"tooltip2\" href=\"#\">".$lang['radio_freq'].":<span><b>Radio Frequency</b>This is the Frequency your<br />Pi-Star is on</span></a></td>\n";
	echo "    <td align=\"left\" colspan=\"2\"><input type=\"text\" name=\"confFREQ\" size=\"13\" maxlength=\"12\" value=\"".number_format($configmmdvm['Info']['RXFrequency'], 0, '.', '.')."\" />MHz</td>\n";
	echo "    </tr>\n";
	}
	else {
	echo "    <tr>\n";
	echo "    <td align=\"left\"><a class=\"tooltip2\" href=\"#\">".$lang['radio_freq']." RX:<span><b>Radio Frequency</b>This is the Frequency your<br />repeater will listen on</span></a></td>\n";
	echo "    <td align=\"left\" colspan=\"2\"><input type=\"text\" name=\"confFREQrx\" size=\"13\" maxlength=\"12\" value=\"".number_format($configmmdvm['Info']['RXFrequency'], 0, '.', '.')."\" />MHz</td>\n";
	echo "    </tr>\n";
	echo "    <tr>\n";
	echo "    <td align=\"left\"><a class=\"tooltip2\" href=\"#\">".$lang['radio_freq']." TX:<span><b>Radio Frequency</b>This is the Frequency your<br />repeater will transmit on</span></a></td>\n";
	echo "    <td align=\"left\" colspan=\"2\"><input type=\"text\" name=\"confFREQtx\" size=\"13\" maxlength=\"12\" value=\"".number_format($configmmdvm['Info']['TXFrequency'], 0, '.', '.')."\" />MHz</td>\n";
	echo "    </tr>\n";
	}
?>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['lattitude'];?>:<span><b>Gateway Latitude</b>This is the latitude where the<br />gateway is located (positive<br />number for North, negative<br />number for South)</span></a></td>
    <td align="left"><input type="text" name="confLatitude" size="13" maxlength="9" value="<?php echo $configs['latitude'] ?>" />degrees (+ East,- West)</td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['longitude'];?>:<span><b>Gateway Longitude</b>This is the longitude where the<br />gateway is located (positive<br />number for East, negative<br />number for West)</span></a></td>
    <td align="left"><input type="text" name="confLongitude" size="13" maxlength="9" value="<?php echo $configs['longitude'] ?>" />degrees (+ East,- West)</td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['town'];?>:<span><b>Gateway Town</b>The town where the gateway<br />is located</span></a></td>
    <td align="left"><input type="text" name="confDesc1" size="30" maxlength="30" value="<?php echo $configs['description1'] ?>" /></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['country'];?>:<span><b>Gateway Country</b>The country where the gateway<br />is located</span></a></td>
    <td align="left"><input type="text" name="confDesc2" size="30" maxlength="30" value="<?php echo $configs['description2'] ?>" /></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['url'];?>:<span><b>Gateway URL</b>The URL used to access<br />this dashboard</span></a></td>
    <td align="left"><input type="text" name="confURL" style="width: 260px;"value="<?php echo $configs['url'] ?>" />
      <br />
      <input type="radio" name="urlAuto" value="auto"<?php if (strpos($configs['url'], 'www.qrz.com/db/'.$configmmdvm['General']['Callsign']) !== FALSE) {echo ' checked="checked"';} ?> />
      Auto
      <input type="radio" name="urlAuto" value="man"<?php if (strpos($configs['url'], 'www.qrz.com/db/'.$configmmdvm['General']['Callsign']) == FALSE) {echo ' checked="checked"';} ?> />
      Manual</td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['radio_type'];?>:<span><b>Radio/Modem</b>What kind of radio or modem<br />hardware do you have ?</span></a></td>
    <td align="left"><select name="confHardware" style="width: 260px;">
		<option<?php if (!$configModem['Modem']['Hardware']) { echo ' selected="selected"';}?> value="">--</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvmpis') { echo ' selected="selected"';}?> value="dvmpis">DV-Mega Raspberry Pi Hat (GPIO) - Single Band (70cm)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvmpid') { echo ' selected="selected"';}?> value="dvmpid">DV-Mega Raspberry Pi Hat (GPIO) - Dual Band</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvmuadu') { echo ' selected="selected"';}?> value="dvmuadu">DV-Mega on Arduino (USB - /dev/ttyUSB0) - Dual Band</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'dvmuada') { echo ' selected="selected"';}?> value="dvmuada">DV-Mega on Arduino (USB - /dev/ttyACM0) - Dual Band</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvmuagmsku') { echo ' selected="selected"';}?> value="dvmuagmsku">DV-Mega on Arduino (USB - /dev/ttyUSB0) - GMSK Modem</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvmuagmska') { echo ' selected="selected"';}?> value="dvmuagmska">DV-Mega on Arduino (USB - /dev/ttyACM0) - GMSK Modem</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvmbss') { echo ' selected="selected"';}?> value="dvmbss">DV-Mega on Bluestack (USB) - Single Band (70cm)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvmbsd') { echo ' selected="selected"';}?> value="dvmbsd">DV-Mega on Bluestack (USB) - Dual Band</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'gmsk_modem') { echo ' selected="selected"';}?> value="gmsk_modem">GMSK Modem (USB DStarRepeater Only)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'dvrptr1') { echo ' selected="selected"';}?> value="dvrptr1">DV-RPTR V1 (USB)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvrptr2') { echo ' selected="selected"';}?> value="dvrptr2">DV-RPTR V2 (USB)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvrptr3') { echo ' selected="selected"';}?> value="dvrptr3">DV-RPTR V3 (USB)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'dvap') { echo ' selected="selected"';}?> value="dvap">DVAP (USB)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'zum') { echo ' selected="selected"';}?> value="zum">MMDVM / MMDVM_HS / Teensy / ZUM (USB)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'stm32dvm') { echo ' selected="selected"';}?> value="stm32dvm">STM32-DVM / MMDVM_HS - Raspberry Pi Hat (GPIO)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'stm32usb') { echo ' selected="selected"';}?> value="stm32usb">STM32-DVM (USB)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'zumspotlibre') { echo ' selected="selected"';}?> value="zumspotlibre">ZumSpot Libre (USB)</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'zumspotusb') { echo ' selected="selected"';}?> value="zumspotusb">ZumSpot - USB Stick</option>
		<option<?php if ($configModem['Modem']['Hardware'] === 'zumspotgpio') { echo ' selected="selected"';}?> value="zumspotgpio">ZumSpot - Raspberry Pi Hat (GPIO)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'zumradiopigpio') { echo ' selected="selected"';}?> value="zumradiopigpio">ZUM Radio-MMDVM for Pi (GPIO)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'mnnano-spot') { echo ' selected="selected"';}?> value="mnnano-spot">MicroNode Nano-Spot (Built In)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'mnnano-teensy') { echo ' selected="selected"';}?> value="mnnano-teensy">MicroNode Teensy (USB)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'f4mgpio') { echo ' selected="selected"';}?> value="f4mgpio">MMDVM F4M-GPIO (GPIO)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'mmdvmhshat') { echo ' selected="selected"';}?> value="mmdvmhshat">MMDVM_HS_Hat (DB9MAT & DF2ET) for Pi (GPIO)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'mmdvmhshatd') { echo ' selected="selected"';}?> value="mmdvmhshatd">MMDVM_HS_Hat Dual (DB9MAT, DF2ET & DO7EN) for Pi (GPIO)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'mmdvmmdohat') { echo ' selected="selected"';}?> value="mmdvmmdohat">MMDVM_HS_MDO Hat (BG3MDO) for Pi (GPIO)</option>
	        <option<?php if ($configModem['Modem']['Hardware'] === 'mmdvmvyehat') { echo ' selected="selected"';}?> value="mmdvmvyehat">MMDVM_HS_NPi Hat (VR2VYE) for Nano Pi (GPIO)</option>
    </select></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['node_type'];?>:<span><b>Node Lock</b>Set the public / private<br />node type. Public should<br />only be used with the correct<br />licence.</span></a></td>
    <td align="left">
    <input type="radio" name="nodeMode" value="prv"<?php if ($configmmdvm['DMR']['SelfOnly'] == 1) {echo ' checked="checked"';} ?> />Private
    <input type="radio" name="nodeMode" value="pub"<?php if ($configmmdvm['DMR']['SelfOnly'] == 0) {echo ' checked="checked"';} ?> />Public</td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['timezone'];?>:<span><b>System TimeZone</b>Set the system timezone</span></a></td>
    <td style="text-align: left;"><select name="systemTimezone" style="width: 260px;">
<?php
  exec('timedatectl list-timezones', $tzList);
  exec('cat /etc/timezone', $tzCurrent);
    foreach ($tzList as $timeZone) {
      if ($timeZone == $tzCurrent[0]) { echo "      <option selected=\"selected\" value=\"".$timeZone."\">".$timeZone."</option>\n"; }
      else { echo "      <option value=\"".$timeZone."\">".$timeZone."</option>\n"; }
    }
?>
    </select></td>
    </tr>
<?php
    $lang_dir = './lang';
    if (is_dir($lang_dir)) {
	echo '    <tr>'."\n";
	echo '    <td align="left"><a class="tooltip2" href="#">'.$lang['dash_lang'].':<span><b>Dashboard Language</b>Set the language for<br />the dashboard.</span></a></td>'."\n";
	echo '    <td align="left" colspan="2"><select name="dashboardLanguage">'."\n";

	if ($dh = opendir($lang_dir)) {
	while ($files[] = readdir($dh))
		sort($files); // Add sorting for the Language(s)
		foreach ($files as $file){
			if (($file != 'index.php') && ($file != '.') && ($file != '..') && ($file != '')) {
				$file = substr($file, 0, -4);
				if ($file == $pistarLanguage) { echo "      <option selected=\"selected\" value=\"".$file."\">".$file."</option>\n"; }
				else { echo "      <option value=\"".$file."\">".$file."</option>\n"; }
			}
		}
		closedir($dh);
	}
	echo '    </select></td></tr>'."\n";
    }
?>
    </table>
<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>"  /></form><br /><br /></div>




 <?php if (file_exists('/etc/dstar-radio.mmdvmhost') && $configmmdvm['DMR']['Enable'] == 1) {
    $dmrMasterFile = fopen("/usr/local/etc/DMR_Hosts.txt", "r"); ?>
	<div><b><?php echo $lang['dmr_config'];?></b></div>
    <input type="hidden" name="dmrEmbeddedLCOnly" value="OFF" />
    <input type="hidden" name="dmrDumpTAData" value="OFF" />
    <input type="hidden" name="dmrGatewayXlxEn" value="OFF" />
    <input type="hidden" name="dmrDMRnetJitterBufer" value="OFF" />
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <table>
    <tr>
    <th width="200"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
    <th><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from the<br />configuration files</span></a></th>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_master'];?>:<span><b>DMR Master (MMDVMHost)</b>Set your prefered DMR<br /> master here</span></a></td>
    <td style="text-align: left;"><select name="dmrMasterHost" style="width: 245px;">
<?php
        $testMMDVMdmrMaster = $configmmdvm['DMR Network']['Address'];
        while (!feof($dmrMasterFile)) {
                $dmrMasterLine = fgets($dmrMasterFile);
                $dmrMasterHost = preg_split('/\s+/', $dmrMasterLine);
                if ((strpos($dmrMasterHost[0], '#') === FALSE ) && (substr($dmrMasterHost[0], 0, 3) != "XLX") && ($dmrMasterHost[0] != '')) {
                        if ($testMMDVMdmrMaster == $dmrMasterHost[2]) { echo "      <option value=\"$dmrMasterHost[2],$dmrMasterHost[3],$dmrMasterHost[4],$dmrMasterHost[0]\" selected=\"selected\">$dmrMasterHost[0]</option>\n"; $dmrMasterNow = $dmrMasterHost[0]; }
                        else { echo "      <option value=\"$dmrMasterHost[2],$dmrMasterHost[3],$dmrMasterHost[4],$dmrMasterHost[0]\">$dmrMasterHost[0]</option>\n"; }
                }
        }
        fclose($dmrMasterFile);
        ?>
    </select></td>
    </tr>
<?php if ($dmrMasterNow == "DMRGateway") { ?>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['bm_master'];?>:<span><b>BrandMeister Master</b>Set your prefered DMR<br /> master here</span></a></td>
    <td style="text-align: left;"><select name="dmrMasterHost1" style="width: 245px;">
<?php
	$dmrMasterFile1 = fopen("/usr/local/etc/DMR_Hosts.txt", "r");
	$testMMDVMdmrMaster1 = $configdmrgateway['DMR Network 1']['Address'];
	while (!feof($dmrMasterFile1)) {
		$dmrMasterLine1 = fgets($dmrMasterFile1);
                $dmrMasterHost1 = preg_split('/\s+/', $dmrMasterLine1);
                if ((strpos($dmrMasterHost1[0], '#') === FALSE ) && (substr($dmrMasterHost1[0], 0, 2) == "BM") && ($dmrMasterHost1[0] != '')) {
                        if ($testMMDVMdmrMaster1 == $dmrMasterHost1[2]) { echo "      <option value=\"$dmrMasterHost1[2],$dmrMasterHost1[3],$dmrMasterHost1[4],$dmrMasterHost1[0]\" selected=\"selected\">$dmrMasterHost1[0]</option>\n"; }
                        else { echo "      <option value=\"$dmrMasterHost1[2],$dmrMasterHost1[3],$dmrMasterHost1[4],$dmrMasterHost1[0]\">$dmrMasterHost1[0]</option>\n"; }
                }
	}
	fclose($dmrMasterFile1);
?>
    </select></td></tr>
    <!-- <tr>
    <td align="left"><a class="tooltip2" href="#">BrandMeister Password:<span><b>BrandMeister Password</b>Override the Password<br />for BrandMeister</span></a></td>
    <td align="left"><input type="text" name="bmPasswordOverride" size="30" maxlength="30" value="<?php echo $configdmrgateway['DMR Network 1']['Password']; ?>"></input></td>
    </tr> -->
    
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_plus_master'];?>:<span><b>DMR+ Master</b>Set your prefered DMR<br /> master here</span></a></td>
    <td style="text-align: left;"><select name="dmrMasterHost2" style="width: 245px;">
<?php
	$dmrMasterFile2 = fopen("/usr/local/etc/DMR_Hosts.txt", "r");
	$testMMDVMdmrMaster2= $configdmrgateway['DMR Network 2']['Address'];
	while (!feof($dmrMasterFile2)) {
		$dmrMasterLine2 = fgets($dmrMasterFile2);
                $dmrMasterHost2 = preg_split('/\s+/', $dmrMasterLine2);
                if ((strpos($dmrMasterHost2[0], '#') === FALSE ) && (substr($dmrMasterHost2[0], 0, 4) == "DMR+") && ($dmrMasterHost2[0] != '')) {
                        if ($testMMDVMdmrMaster2 == $dmrMasterHost2[2]) { echo "      <option value=\"$dmrMasterHost2[2],$dmrMasterHost2[3],$dmrMasterHost2[4],$dmrMasterHost2[0]\" selected=\"selected\">$dmrMasterHost2[0]</option>\n"; }
                        else { echo "      <option value=\"$dmrMasterHost2[2],$dmrMasterHost2[3],$dmrMasterHost2[4],$dmrMasterHost2[0]\">$dmrMasterHost2[0]</option>\n"; }
                }
	}
	fclose($dmrMasterFile2);
?>
    </select></td></tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_plus_network'];?>:<span><b>DMR+ Network</b>Set your options=<br />for DMR+ here</span></a></td>
    <td align="left">
    Options=<input type="text" name="dmrNetworkOptions" size="65" maxlength="90" value="<?php if (isset($configdmrgateway['DMR Network 2']['Options'])) { echo $configdmrgateway['DMR Network 2']['Options']; } ?>" />
    </td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['xlx_master'];?>:<span><b>XLX Master</b>Set your prefered XLX<br /> master here</span></a></td>
    <td style="text-align: left;"><select name="dmrMasterHost3">
<?php
	$dmrMasterFile3 = fopen("/usr/local/etc/DMR_Hosts.txt", "r");
	if (isset($configdmrgateway['XLX Network 1']['Address'])) { $testMMDVMdmrMaster3= $configdmrgateway['XLX Network 1']['Address']; }
	if (isset($configdmrgateway['XLX Network']['Startup'])) { $testMMDVMdmrMaster3= $configdmrgateway['XLX Network']['Startup']; }
	while (!feof($dmrMasterFile3)) {
		$dmrMasterLine3 = fgets($dmrMasterFile3);
                $dmrMasterHost3 = preg_split('/\s+/', $dmrMasterLine3);
                if ((strpos($dmrMasterHost3[0], '#') === FALSE ) && (substr($dmrMasterHost3[0], 0, 3) == "XLX") && ($dmrMasterHost3[0] != '')) {
                        if ($testMMDVMdmrMaster3 == $dmrMasterHost3[2]) { echo "      <option value=\"$dmrMasterHost3[2],$dmrMasterHost3[3],$dmrMasterHost3[4],$dmrMasterHost3[0]\" selected=\"selected\">$dmrMasterHost3[0]</option>\n"; }
			if ('XLX_'.$testMMDVMdmrMaster3 == $dmrMasterHost3[0]) { echo "      <option value=\"$dmrMasterHost3[2],$dmrMasterHost3[3],$dmrMasterHost3[4],$dmrMasterHost3[0]\" selected=\"selected\">$dmrMasterHost3[0]</option>\n"; }
                        else { echo "      <option value=\"$dmrMasterHost3[2],$dmrMasterHost3[3],$dmrMasterHost3[4],$dmrMasterHost3[0]\">$dmrMasterHost3[0]</option>\n"; }
                }
	}
	fclose($dmrMasterFile3);
?>
    </select></td></tr>
    <?php if (isset($configdmrgateway['XLX Network 1']['Startup'])) { ?>
    <tr>
    <td align="left"><a class="tooltip2" href="#">XLX Startup TG:<span><b>XLX Startup TG</b></span></a></td>
    <td align="left"><select name="dmrMasterHost3Startup">
<?php
	if (isset($configdmrgateway['XLX Network 1']['Startup'])) {
		echo '      <option value="None">None</option>'."\n";
	}
	else {
		echo '      <option value="None" selected="selected">None</option>'."\n";
	}
	for ($xlxSu = 1; $xlxSu <= 26; $xlxSu++) {
		$xlxSuVal = '40'.sprintf('%02d', $xlxSu);
		if ((isset($configdmrgateway['XLX Network 1']['Startup'])) && ($configdmrgateway['XLX Network 1']['Startup'] == $xlxSuVal)) {
			echo '      <option value="'.$xlxSuVal.'" selected="selected">'.$xlxSuVal.'</option>'."\n";
		}
		else {
			echo '      <option value="'.$xlxSuVal.'">'.$xlxSuVal.'</option>'."\n";
		}
	}
?>
    </select></td></tr>
    <?php } ?>
     <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['xlx_enable'];?>:<span><b>XLX Master Enable</b></span></a></td>
    <td align="left">
    <?php
    if ((isset($configdmrgateway['XLX Network 1']['Enabled'])) && ($configdmrgateway['XLX Network 1']['Enabled'] == 1)) { echo "<div class=\"switch\"><input id=\"toggle-dmrGatewayXlxEn\"  type=\"checkbox\" name=\"dmrGatewayXlxEn\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-dmrGatewayXlxEn\"></label></div>\n"; }
    else if ((isset($configdmrgateway['XLX Network']['Enabled'])) && ($configdmrgateway['XLX Network']['Enabled'] == 1)) { echo "<div class=\"switch\"><input id=\"toggle-dmrGatewayXlxEn\"  type=\"checkbox\" name=\"dmrGatewayXlxEn\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-dmrGatewayXlxEn\"></label></div>\n"; }
    else { echo "<div class=\"switch\"><input id=\"toggle-dmrGatewayXlxEn\"  type=\"checkbox\" name=\"dmrGatewayXlxEn\" value=\"ON\" /><label for=\"toggle-dmrGatewayXlxEn\"></label></div>\n"; } ?>
    </td></tr>
<?php }
    if (substr($dmrMasterNow, 0, 2) == "BM") { echo '    <!-- <tr>
    <td align="left"><a class="tooltip2" href="#">BrandMeister Password:<span><b>BrandMeister Password</b>Override the Password<br />for BrandMeister</span></a></td>
    <td align="left"><input type="text" name="bmPasswordOverride" size="30" maxlength="30" value="'.$configmmdvm['DMR Network']['Password'].'"></input></td>
    </tr> -->
    <tr>
    <td align="left"><a class="tooltip2" href="#">'.$lang['bm_network'].':<span><b>BrandMeister Dashboards</b>Direct links to your<br />BrandMeister Dashboards</span></a></td>
    <td>
      <a href="https://brandmeister.network/?page=hotspot&amp;id='.$configmmdvm['General']['Id'].'" target="_new" style="color: #000;">Repeater Information</a> |
      <a href="https://brandmeister.network/?page=hotspot-edit&amp;id='.$configmmdvm['General']['Id'].'" target="_new" style="color: #000;">Edit Repeater (BrandMeister Selfcare)</a>
    </td>
    </tr>'."\n";}
    if (substr($dmrMasterNow, 0, 4) == "DMR+") {
      echo '    <tr>
    <td align="left"><a class="tooltip2" href="#">'.$lang['dmr_plus_network'].':<span><b>DMR+ Network</b>Set your options=<br />for DMR+ here</span></a></td>
    <td align="left">
    Options=<input type="text" name="dmrNetworkOptions" size="65" maxlength="98" value="';
	if (isset($configmmdvm['DMR Network']['Options'])) { echo $configmmdvm['DMR Network']['Options']; }
        echo '" />
    </td>
    </tr>'."\n";}
?>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_cc'];?>:<span><b>DMR Color Code</b>Set your DMR Color Code here</span></a></td>
    <td style="text-align: left;"><select name="dmrColorCode">
	<?php for ($dmrColorCodeInput = 0; $dmrColorCodeInput <= 15; $dmrColorCodeInput++) {
		if ($configmmdvm['DMR']['ColorCode'] == $dmrColorCodeInput) { echo "<option selected=\"selected\" value=\"$dmrColorCodeInput\">$dmrColorCodeInput</option>\n"; }
		else {echo "      <option value=\"$dmrColorCodeInput\">$dmrColorCodeInput</option>\n"; }
	} ?>
    </select></td>
    </tr>
   
    <!-- <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo "DMR JitterBuffer";?>:<span><b>DMR JitterBuffer</b>Turn on for improved<br />network resiliancy, in high<br />Latency networks.</span></a></td>
    <td align="left">
    <?php // if ((isset($configmmdvm['DMR Network']['JitterEnabled'])) && ($configmmdvm['DMR Network']['JitterEnabled'] == 0)) { echo "<div class=\"switch\"><input id=\"toggle-dmrJitterBufer\" class=\"toggle toggle-round-flat\" type=\"checkbox\" name=\"dmrDMRnetJitterBufer\" value=\"ON\"/><label for=\"toggle-dmrJitterBufer\"></label></div>\n"; }
    // else { echo "<div class=\"switch\"><input id=\"toggle-dmrJitterBufer\" class=\"toggle toggle-round-flat\" type=\"checkbox\" name=\"dmrDMRnetJitterBufer\" value=\"ON\" checked=\"checked\" /><label for=\"toggle-dmrJitterBufer\"></label></div>\n"; }
    ?>
    </td></tr>. -->
    </table>
	<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>" /><br /><br /></div>
<?php } ?></form>








<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
<?php if (file_exists('/etc/dstar-radio.dstarrepeater') || $configmmdvm['D-Star']['Enable'] == 1) { ?>
	<div><b><?php echo $lang['dstar_config'];?></b></div>
	<input type="hidden" name="confTimeAnnounce" value="OFF" />
	<input type="hidden" name="confHostFilesNoDExtra" value="OFF" />
    <table>
    <tr>
    <th width="160"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
    <th colspan="2"><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from the<br />configuration files</span></a></th>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dstar_rpt1'];?>:<span><b>RPT1 Callsign</b>This is the RPT1 field for your radio</span></a></td>
    <td align="left" colspan="2"><?php echo str_replace(' ', '&nbsp;', substr($configdstar['callsign'], 0, 7)) ?>
	<select name="confDStarModuleSuffix">
	<?php echo "  <option value=\"".substr($configdstar['callsign'], 7)."\" selected=\"selected\">".substr($configdstar['callsign'], 7)."</option>\n"; ?>
        <option>A</option>
        <option>B</option>
        <option>C</option>
        <option>D</option>
        <option>E</option>
        <option>F</option>
        <option>G</option>
        <option>H</option>
        <option>I</option>
        <option>J</option>
        <option>K</option>
        <option>L</option>
        <option>M</option>
        <option>N</option>
        <option>O</option>
        <option>P</option>
        <option>Q</option>
        <option>R</option>
        <option>S</option>
        <option>T</option>
        <option>U</option>
        <option>V</option>
        <option>W</option>
        <option>X</option>
        <option>Y</option>
        <option>Z</option>
    </select></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dstar_rpt2'];?>:<span><b>RPT2 Callsign</b>This is the RPT2 field for your radio</span></a></td>
    <td align="left" colspan="2"><?php echo str_replace(' ', '&nbsp;', $configdstar['gateway']) ?></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dstar_default_ref'];?>:<span><b>Default Refelctor</b>Used for setting the<br />default reflector.</span></a></td>
    <td width="241" colspan="1" align="left"><select name="confDefRef"
	onchange="if (this.options[this.selectedIndex].value == 'customOption') {
	  toggleField(this,this.nextSibling);
	  this.selectedIndex='0';
	  } ">
<?php
$dcsFile = fopen("/usr/local/etc/DCS_Hosts.txt", "r");
$dplusFile = fopen("/usr/local/etc/DPlus_Hosts.txt", "r");
$dextraFile = fopen("/usr/local/etc/DExtra_Hosts.txt", "r");

echo "    <option value=\"".substr($configs['reflector1'], 0, 6)."\" selected=\"selected\">".substr($configs['reflector1'], 0, 6)."</option>\n";
echo "    <option value=\"customOption\">Text Entry</option>\n";

while (!feof($dcsFile)) {
	$dcsLine = fgets($dcsFile);
	if (strpos($dcsLine, 'DCS') !== FALSE && strpos($dcsLine, '#') === FALSE)
		echo "	<option value=\"".substr($dcsLine, 0, 6)."\">".substr($dcsLine, 0, 6)."</option>\n";
}
fclose($dcsFile);
while (!feof($dplusFile)) {
	$dplusLine = fgets($dplusFile);
	if (strpos($dplusLine, 'REF') !== FALSE && strpos($dplusLine, '#') === FALSE) {
		echo "	<option value=\"".substr($dplusLine, 0, 6)."\">".substr($dplusLine, 0, 6)."</option>\n";
	}
	if (strpos($dplusLine, 'XRF') !== FALSE && strpos($dplusLine, '#') === FALSE) {
		echo "	<option value=\"".substr($dplusLine, 0, 6)."\">".substr($dplusLine, 0, 6)."</option>\n";
	}
}
fclose($dplusFile);
while (!feof($dextraFile)) {
	$dextraLine = fgets($dextraFile);
	if (strpos($dextraLine, 'XRF') !== FALSE && strpos($dextraLine, '#') === FALSE)
		echo "	<option value=\"".substr($dextraLine, 0, 6)."\">".substr($dextraLine, 0, 6)."</option>\n";
}
fclose($dextraFile);

?>
    </select><input name="confDefRef" style="display:none;" disabled="disabled" type="text" size="7" maxlength="7"
            onblur="if(this.value==''){toggleField(this,this.previousSibling);}" />
    <select name="confDefRefLtr">
	<?php echo "  <option value=\"".substr($configs['reflector1'], 7)."\" selected=\"selected\">".substr($configs['reflector1'], 7)."</option>\n"; ?>
        <option>A</option>
        <option>B</option>
        <option>C</option>
        <option>D</option>
        <option>E</option>
        <option>F</option>
        <option>G</option>
        <option>H</option>
        <option>I</option>
        <option>J</option>
        <option>K</option>
        <option>L</option>
        <option>M</option>
        <option>N</option>
        <option>O</option>
        <option>P</option>
        <option>Q</option>
        <option>R</option>
        <option>S</option>
        <option>T</option>
        <option>U</option>
        <option>V</option>
        <option>W</option>
        <option>X</option>
        <option>Y</option>
        <option>Z</option>
    </select>
    </td>
    <td width="154" align="left">
    <input type="radio" name="confDefRefAuto" value="ON"<?php if ($configs['atStartup1'] == '1') {echo ' checked="checked"';} ?> />Startup
    <br />
    <input type="radio" name="confDefRefAuto" value="OFF"<?php if ($configs['atStartup1'] == '0') {echo ' checked="checked"';} ?> />Manual</td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['aprs_host'];?>:<span><b>APRS Host</b>Set your prefered APRS host here</span></a></td>
    <td colspan="2" style="text-align: left;"><select name="selectedAPRSHost">
<?php
        $testAPSRHost = $configs['aprsHostname'];
    	$aprsHostFile = fopen("/usr/local/etc/APRSHosts.txt", "r");
        while (!feof($aprsHostFile)) {
                $aprsHostFileLine = fgets($aprsHostFile);
                $aprsHost = preg_split('/:/', $aprsHostFileLine);
                if ((strpos($aprsHost[0], ';') === FALSE ) && ($aprsHost[0] != '')) {
                        if ($testAPSRHost == $aprsHost[0]) { echo "      <option value=\"$aprsHost[0]\" selected=\"selected\">$aprsHost[0]</option>\n"; }
                        else { echo "      <option value=\"$aprsHost[0]\">$aprsHost[0]</option>\n"; }
                }
        }
        fclose($aprsHostFile);
        ?>
    </select></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dstar_irc_lang'];?>:<span><b>Language</b>Set your prefered<br /> language here</span></a></td>
    <td colspan="2" style="text-align: left;"><select name="ircDDBGatewayAnnounceLanguage">
<?php
        $testIrcLanguage = $configs['language'];
	if (is_readable("/var/www/dashboard/config/ircddbgateway_languages.inc")) {
	  $ircLanguageFile = fopen("/var/www/dashboard/config/ircddbgateway_languages.inc", "r");
        while (!feof($ircLanguageFile)) {
                $ircLanguageFileLine = fgets($ircLanguageFile);
                $ircLanguage = preg_split('/;/', $ircLanguageFileLine);
                if ((strpos($ircLanguage[0], '#') === FALSE ) && ($ircLanguage[0] != '')) {
			$ircLanguage[2] = rtrim($ircLanguage[2]);
                        if ($testIrcLanguage == $ircLanguage[1]) { echo "      <option value=\"$ircLanguage[1],$ircLanguage[2]\" selected=\"selected\">".htmlspecialchars($ircLanguage[0])."</option>\n"; }
                        else { echo "      <option value=\"$ircLanguage[1],$ircLanguage[2]\">".htmlspecialchars($ircLanguage[0])."</option>\n"; }
                }
        }
          fclose($ircLanguageFile);
	}
        ?>
    </select></td>
    </tr>
    
   
    </table>
	<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>"  /><br /><br /></div>
    <?php } ?>








<?php if (file_exists('/etc/dstar-radio.mmdvmhost') && $configmmdvm['System Fusion Network']['Enable'] == 1) {
$ysfHosts = fopen("/usr/local/etc/YSFHosts.txt", "r"); ?>
	<div><b><?php echo $lang['ysf_config'];?></b></div>
    <table>
    <tr>
    <th width="160"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
    <th colspan="2"><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from the<br />configuration files</span></a></th>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['ysf_startup_host'];?>:<span><b>YSF Host</b>Set your prefered<br /> YSF Host here</span></a></td>
    <td style="text-align: left;"><select name="ysfStartupHost">
<?php
        if (isset($configysfgateway['Network']['Startup'])) {
                $testYSFHost = $configysfgateway['Network']['Startup'];
                echo "      <option value=\"none\">None</option>\n";
		if ($testYSFHost == 00002) {
			echo "      <option value=\"00002\" selected=\"selected\">00002 - YSF2DMR - YSF2DMR Gateway</option>\n";
		}
		else {
			echo "      <option value=\"00002\">00002 - YSF2DMR - YSF2DMR Gateway</option>\n";
		}
                }
        else {
                $testYSFHost = "none";
                echo "      <option value=\"none\" selected=\"selected\">None</option>\n";
		echo "      <option value=\"00002\">00002 - YSF2DMR - YSF2DMR Gateway</option>\n";
                }
        while (!feof($ysfHosts)) {
                $ysfHostsLine = fgets($ysfHosts);
                $ysfHost = preg_split('/;/', $ysfHostsLine);
                if ((strpos($ysfHost[0], '#') === FALSE ) && ($ysfHost[0] != '')) {
                        if ($testYSFHost == $ysfHost[0]) { echo "      <option value=\"$ysfHost[0]\" selected=\"selected\">$ysfHost[0] - ".htmlspecialchars($ysfHost[1])." - ".htmlspecialchars($ysfHost[2])."</option>\n"; }
			else { echo "      <option value=\"$ysfHost[0]\">$ysfHost[0] - ".htmlspecialchars($ysfHost[1])." - ".htmlspecialchars($ysfHost[2])."</option>\n"; }
                }
        }
        fclose($ysfHosts);
        ?>
    </select></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['aprs_host'];?>:<span><b>APRS Host</b>Set your prefered APRS host here</span></a></td>
    <td colspan="2" style="text-align: left;"><select name="selectedAPRSHost">
<?php
        $testAPSRHost = $configs['aprsHostname'];
    	$aprsHostFile = fopen("/usr/local/etc/APRSHosts.txt", "r");
        while (!feof($aprsHostFile)) {
                $aprsHostFileLine = fgets($aprsHostFile);
                $aprsHost = preg_split('/:/', $aprsHostFileLine);
                if ((strpos($aprsHost[0], ';') === FALSE ) && ($aprsHost[0] != '')) {
                        if ($testAPSRHost == $aprsHost[0]) { echo "      <option value=\"$aprsHost[0]\" selected=\"selected\">$aprsHost[0]</option>\n"; }
                        else { echo "      <option value=\"$aprsHost[0]\">$aprsHost[0]</option>\n"; }
                }
        }
        fclose($aprsHostFile);
        ?>
    </select></td>
    </tr>
    <?php if (file_exists('/etc/dstar-radio.mmdvmhost') && $configysf2dmr['Enabled']['Enabled'] == 1) {
    $dmrMasterFile = fopen("/usr/local/etc/DMR_Hosts.txt", "r"); ?>
    <tr>
      <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_id'];?>:<span><b>CCS7/DMR ID</b>Enter your CCS7 / DMR ID here</span></a></td>
      <td align="left" colspan="2"><input type="text" name="ysf2dmrId" size="13" maxlength="9" value="<?php if (isset($configysf2dmr['DMR Network']['Id'])) { echo $configysf2dmr['DMR Network']['Id']; } ?>" /></td>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['dmr_master'];?>:<span><b>DMR Master (YSF2DMR)</b>Set your prefered DMR<br /> master here</span></a></td>
    <td style="text-align: left;"><select name="ysf2dmrMasterHost">
<?php
        $testMMDVMysf2dmrMaster = $configysf2dmr['DMR Network']['Address'];
        while (!feof($dmrMasterFile)) {
                $dmrMasterLine = fgets($dmrMasterFile);
                $dmrMasterHost = preg_split('/\s+/', $dmrMasterLine);
                if ((strpos($dmrMasterHost[0], '#') === FALSE ) && (substr($dmrMasterHost[0], 0, 3) != "XLX") && (substr($dmrMasterHost[0], 0, 4) != "DMRG") && ($dmrMasterHost[0] != '')) {
                        if ($testMMDVMysf2dmrMaster == $dmrMasterHost[2]) { echo "      <option value=\"$dmrMasterHost[2],$dmrMasterHost[3],$dmrMasterHost[4],$dmrMasterHost[0]\" selected=\"selected\">$dmrMasterHost[0]</option>\n"; $dmrMasterNow = $dmrMasterHost[0]; }
                        else { echo "      <option value=\"$dmrMasterHost[2],$dmrMasterHost[3],$dmrMasterHost[4],$dmrMasterHost[0]\">$dmrMasterHost[0]</option>\n"; }
                }
        }
        fclose($dmrMasterFile);
        ?>
    </select></td>
    </tr>
    <tr>
      <td align="left"><a class="tooltip2" href="#">DMR TG:<span><b>YSF2DMR TG</b>Enter your DMR TG here</span></a></td>
      <td align="left" colspan="2"><input type="text" name="ysf2dmrTg" size="13" maxlength="7" value="<?php if (isset($configysf2dmr['DMR Network']['StartupDstId'])) { echo $configysf2dmr['DMR Network']['StartupDstId']; } ?>" /></td>  
    </tr>
    <?php } ?>
    </table>
	<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>" onclick="submitform()" /><br /><br /></div>
<?php } ?>
<?php if (file_exists('/etc/dstar-radio.mmdvmhost') && $configmmdvm['P25 Network']['Enable'] == 1) {
$p25Hosts = fopen("/usr/local/etc/P25Hosts.txt", "r");
	?>
	<div><b><?php echo $lang['p25_config'];?></b></div>
    <table>
    <tr>
    <th width="160"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
    <th colspan="2"><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from the<br />configuration files</span></a></th>
    </tr>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['p25_startup_host'];?>:<span><b>P25 Host</b>Set your prefered<br /> P25 Host here</span></a></td>
    <td style="text-align: left;"><select name="p25StartupHost">
<?php
	$testP25Host = $configp25gateway['Network']['Startup'];
	if ($testP25Host == "") { echo "      <option value=\"none\" selected=\"selected\">None</option>\n"; }
        else { echo "      <option value=\"none\">None</option>\n"; }
	if ($testP25Host == "10") { echo "      <option value=\"10\" selected=\"selected\">10 - Parrot</option>\n"; }
        else { echo "      <option value=\"10\">10 - Parrot</option>\n"; }
        while (!feof($p25Hosts)) {
                $p25HostsLine = fgets($p25Hosts);
                $p25Host = preg_split('/\s+/', $p25HostsLine);
                if ((strpos($p25Host[0], '#') === FALSE ) && ($p25Host[0] != '')) {
                        if ($testP25Host == $p25Host[0]) { echo "      <option value=\"$p25Host[0]\" selected=\"selected\">$p25Host[0] - $p25Host[1]</option>\n"; }
                        else { echo "      <option value=\"$p25Host[0]\">$p25Host[0] - $p25Host[1]</option>\n"; }
                }
        }
        fclose($p25Hosts);
        if (file_exists('/usr/local/etc/P25HostsLocal.txt')) {
		$p25Hosts2 = fopen("/usr/local/etc/P25HostsLocal.txt", "r");
		while (!feof($p25Hosts2)) {
                	$p25HostsLine2 = fgets($p25Hosts2);
                	$p25Host2 = preg_split('/\s+/', $p25HostsLine2);
                	if ((strpos($p25Host2[0], '#') === FALSE ) && ($p25Host2[0] != '')) {
                        	if ($testP25Host == $p25Host2[0]) { echo "      <option value=\"$p25Host2[0]\" selected=\"selected\">$p25Host2[0] - $p25Host2[1]</option>\n"; }
                        	else { echo "      <option value=\"$p25Host2[0]\">$p25Host2[0] - $p25Host2[1]</option>\n"; }
                	}
		}
		fclose($p25Hosts2);
	}
        ?>
    </select></td>
    </tr>
<?php if ($configmmdvm['P25']['NAC']) { ?>
    <tr>
    <td align="left"><a class="tooltip2" href="#"><?php echo $lang['p25_nac'];?>:<span><b>P25 NAC</b>Set your NAC<br /> code here</span></a></td>
    <td align="left"><input type="text" name="p25nac" size="13" maxlength="3" value="<?php echo $configmmdvm['P25']['NAC'];?>" /></td>
    </tr>
<?php } ?>
    </table>
	<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>" onclick="submitform()" /><br /><br /></div>
<?php } ?>
	
<?php if (file_exists('/etc/dstar-radio.mmdvmhost') && $configmmdvm['NXDN Network']['Enable'] == 1) { ?>
	<div><b><?php echo $lang['nxdn_config'];?></b></div>
    <table>
      <tr>
        <th width="160"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
        <th colspan="2"><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from the<br />configuration files</span></a></th>
      </tr>
      <tr>
        <td align="left"><a class="tooltip2" href="#"><?php echo $lang['nxdn_startup_host'];?>:<span><b>NXDN Host</b>Set your prefered<br /> NXDN Host here</span></a></td>
        <td style="text-align: left;"><select name="nxdnStartupHost">
		<option value="176.9.1.168">D2FET Test Host - 176.9.1.168</option>
		</select></td>
      </tr>
    <?php if ($configmmdvm['NXDN']['RAN']) { ?>
      <tr>
        <td align="left"><a class="tooltip2" href="#"><?php echo $lang['nxdn_ran'];?>:<span><b>NXDN RAN</b>Set your RAN<br /> code here</span></a></td>
        <td align="left"><input type="text" name="nxdnran" size="13" maxlength="1" value="<?php echo $configmmdvm['NXDN']['RAN'];?>" /></td>
      </tr>
    <?php } ?>
    </table>
	<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>" onclick="submitform()" /><br /><br /></div>
<?php } ?>










<div><b><?php echo $lang['fw_config'];?></b></div>
<form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
    <table>
    <tr>
    <th width="200"><a class="tooltip" href="#"><?php echo $lang['setting'];?><span><b>Setting</b></span></a></th>
    <th colspan="2"><a class="tooltip" href="#"><?php echo $lang['value'];?><span><b>Value</b>The current value from the<br />configuration files</span></a></th>
    </tr>
    
    <?php if (file_exists('/etc/default/hostapd')) { ?>
    <tr>
      <td align="left"><a class="tooltip2" href="#">Auto AP:<span><b>Auto AP</b>Do you want your Pi<br />to create its own AP<br />if it cannot connect to<br />WiFi within 120 secs of boot</span></a></td>
      <?php
        if (file_exists('/etc/hostap.off')) {
	  echo "   <td align=\"left\"><input type=\"radio\" name=\"autoAP\" value=\"ON\" />On <input type=\"radio\" name=\"autoAP\" value=\"OFF\" checked=\"checked\" />Off</td>\n";
	}
        else {
	  echo "   <td align=\"left\"><input type=\"radio\" name=\"autoAP\" value=\"ON\" checked=\"checked\" />On <input type=\"radio\" name=\"autoAP\" value=\"OFF\" />Off</td>\n";
	}
      ?>
      <td> <em>Reboot Required<br />
      if changed</em></td>
    </tr>
    <?php } ?>
    </table>
	<div align="center"><input type="submit" value="<?php echo $lang['apply'];?>"  /></div>
    </form>
    <br />

<?php

	if ( file_exists('/sys/class/net/wlan0') || file_exists('/sys/class/net/wlan1') ) { ?>

<br />
    <b><?php $lang['wifi_config'];?></b>
    <table><tr><td>
    <iframe frameborder="0" scrolling="auto" name="wifi" src="wifi.php?page=wlan0_info" width="100%" onload="javascript:resizeIframe(this);">If you can see this message, your browser does not support iFrames, however if you would like to see the content please click <a href="wifi.php?page=wlan0_info">here</a>.</iframe>
    </td></tr></table><?php } ?>

<br />

<?php } ?>



</div></div></div>