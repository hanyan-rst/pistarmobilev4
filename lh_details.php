<?php
include_once $_SERVER['DOCUMENT_ROOT'].'/config/config.php';          // MMDVMDash Config
include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/tools.php';        // MMDVMDash Tools
include_once $_SERVER['DOCUMENT_ROOT'].'/mmdvmhost/functions.php';    // MMDVMDash Functions
include_once $_SERVER['DOCUMENT_ROOT'].'/config/language.php';	      // Translation Code

function search($array, $key, $value)
{
    $results = array();

    if (is_array($array)) {
        if (isset($array[$key]) && $array[$key] == $value) {
            $results[] = $array;
        }

        foreach ($array as $subarray) {
            $results = array_merge($results, search($subarray, $key, $value));
        }
    }

    return $results;
}




$i = 0;
for ($i = 0;  ($i <= 0); $i++) { //Last 20 calls
	if (isset($lastHeard[$i])) {
		$listElem = $lastHeard[$i];
		if ( $listElem[2] ) {
			$utc_time = $listElem[0];
                        $utc_tz =  new DateTimeZone('UTC');
                        $local_tz = new DateTimeZone(date_default_timezone_get ());
                        $dt = new DateTime($utc_time, $utc_tz);
                        $dt->setTimeZone($local_tz);
                        $local_time = $dt->format('H:i:s M jS');
						$local_time = $dt->format('H:i:s M-j');
}}}

if ( substr($listElem[4], 0, 6) === 'CQCQCQ' ) {
			$target = $listElem[4];
		} else {
			$target = str_replace(" ","&nbsp;", $listElem[4]);
		}
		
$TGnumber = preg_replace('/\D/', '', $listElem[4]);
		
if ($listElem[5] == "RF"){
			$source = "RF";
		}else{
			$source = $listElem[5];
		}
		
if ($listElem[6] == null) {
			$duration = "<span style=\"color: #f33;\">TX</span>";
			} else if ($listElem[6] == "SMS") {
			$duration = "<span style=\"color: #1d1;\">SMS</span>";
			} else {
			$duration = $listElem[6]."s";
			}
			
if ($listElem[7] == null) { $loss = "&nbsp;&nbsp;&nbsp;";
			}elseif (floatval($listElem[7]) < 1) { $loss = "<span style=\"color: #FFF;\">".$listElem[7]."</span>";
			}elseif (floatval($listElem[7]) == 1) { $loss = "<span style=\"color: #1d1;\">".$listElem[7]."</span>"; }
			elseif (floatval($listElem[7]) > 1 && floatval($listElem[7]) <= 3) { $loss = "<span style=\"color: #fa0;\">".$listElem[7]."</span>"; }
			else { $loss = "<span style=\"color: #f33;\">".$listElem[7]."</span>"; }
			
if ($listElem[8] == null) {
			$ber = "&nbsp;&nbsp;&nbsp;&nbsp;";
			} else {
			$mode = $listElem[8];
			}

if ($listElem[1] == null) {
			$ber = "&nbsp;&nbsp;&nbsp;&nbsp;";
			} else {
			$mode = $listElem[1];
			}
			
// Colour the BER Field
			if (floatval($listElem[8]) == 0) { $ber = $listElem[8]; }
			elseif (floatval($listElem[8]) >= 0.0 && floatval($listElem[8]) <= 1.9) { $ber = "<span style=\"color: #ldl;\">".$listElem[8]."</span>"; }
			elseif (floatval($listElem[8]) >= 2.0 && floatval($listElem[8]) <= 4.9) { $ber = "<span style=\"color: #FA0;\">".$listElem[8]."</span>"; }
			else { $ber = "<span style=\"color: #F33;\">".$listElem[8]."</span>"; }


$name = shell_exec("grep -w \"$listElem[2]\" /var/www/dashboard/mobile/data.csv | awk -F, '{print $3}' | head -1 | tr -d '\"' ");
$city = shell_exec("grep -w \"$listElem[2]\" /var/www/dashboard/mobile/data.csv | awk -F, '{print $4}' | head -1 | tr -d '\"' ");
$state = shell_exec("grep -w \"$listElem[2]\" /var/www/dashboard/mobile/data.csv | awk -F, '{print $5}' | head -1 | tr -d '\"' ");
$country = shell_exec("grep -w \"$listElem[2]\" /var/www/dashboard/mobile/data.csv | awk -F, '{print $6 }' | head -1 | tr -d '\"' ");

if($listElem[2] == "4000" || $listElem[2] == "9990"){
$name = "";
$city = "";
$state = "";
$country = "";
}

?>
<link rel="stylesheet" href="css/stylesheet.css" type="text/css" charset="utf-8" />
<style>
.callsign
{ font-family: 'enhanced_led_board-7regular', Arial, sans-serif; }

#macrosectiontext
{
    position:relative;
    font:Arial, sans-serif;
    background-color:transparent;
	font-size: 15px;
}

#macrosectiontext A:link {text-decoration: none; color: #FFFFFF;}
#macrosectiontext A:visited {text-decoration: none; color: #FFFFFF;}
#macrosectiontext A:hover {text-decoration: none; color: #FFFFFF;}
#macrosectiontext A:active {text-decoration: none; color: #FFFFFF;}
</style>

<b><?php //echo $lang['last_heard_list'];?>Last Heard Details</b>
 <table border="0">
  <tr>
    <th width="122" align="left"><div id="macrosectiontext"><a href="/livedisplay/" class="item-content item-link">Live Caller</a></div></th>
    <th width="167" align="right">
    
    <?php 
	if (strpos($mode, 'DMR') !== false && $TGnumber > "90") { ?>
  
    <div id="macrosectiontext"><a href="https://hose.brandmeister.network/<?php echo $TGnumber; ?>" target="_blank" class="link external"><i class="f7-icons size-10">volume</i></a></div>
	
	<?php
    }
	?>

    </th>
   </tr>
  <tr>
    <td border=0 bgcolor="#FFCC00" class="callsign" ><p style="color: #000; font-size: 22px; font-weight: bold;"><?php echo $listElem[2]; ?></p></td>
    <td class="callsign"  align="left" bgcolor="#FFCC00" ><span style="color: #000; font-size: 10px; ">
      <strong><?php  echo $name;  ?></strong>
      <br />
      <?php  echo $city;  ?>
      <br />
      <?php  echo $state;  ?>
      <br />
      <?php  echo $country;  ?>
    </span></td>
   </tr>
  <tr>
    <td align="left" border=0 bgcolor="#000" ><span style="color: #FFF; font-size: 12px; font-weight: bold;">Source: <?php echo $source; ?><br />
 <?php echo $mode; ?> - <?php echo $target; ?></span></td>
    <td  align="left" bgcolor="#000" ><span style="color: #FFF; font-size: 12px; font-weight: bold;">Duration: <?php echo $duration ?><br />
&nbsp;&nbsp Loss: <?php echo $loss ?> &nbsp;&nbsp; BER: <?php echo $ber ?></span></td>
  </tr>
</table>

