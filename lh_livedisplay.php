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
			}elseif (floatval($listElem[7]) < 1) { $loss = "<span style=\"color: #000;\">".$listElem[7]."</span>";
			}elseif (floatval($listElem[7]) == 1) { $loss = "<span style=\"color: #006600;\">".$listElem[7]."</span>"; }
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
            { font-family: 'enhanced_led_board-7regular', Arial, sans-serif;

			font-size:55px ;
			text-align:left;
			vertical-align: middle;
			
			}
			.details
            { font-family: 'enhanced_led_board-7regular', Arial, sans-serif;
			font-size:20px ;
			text-align:left;
			
			}
			
/* DivTable.com */
.divTable{
	display: table;
	width: 100%;
	border: 2px solid #000;
	background-image: url("bluedv.png");
	background-size:cover;
}
.divTableRow {
	display: table-row;
}
.divTableHeading {
	background-color: #EEE;
	display: table-header-group;
}
.divTableCell, .divTableHead {
	border: 0px solid #999999;
	display: table-cell;
	padding: 0px 5px;
	width: 50%;
	vertical-align: middle;
	font-family: 'enhanced_led_board-7regular', Arial, sans-serif;
}
.divTableHeading {
	background-color: #EEE;
	display: table-header-group;
	font-weight: bold;
}
.divTableFoot {
	background-color: #EEE;
	display: table-footer-group;
	font-weight: bold;
}
.divTableBody {
	display: table-row-group;
}
}
}

</style>



<div class="divTable">
<div class="divTableBody">
<div class="divTableRow">
<div class="divTableCell"><div class="callsign"><?php echo $listElem[2]; ?></div></div>
<div class="divTableCell"><div class="details">
      <strong><?php  echo $name;  ?></strong>
      <br />
      <?php  echo $city;  ?>
      <br />
      <?php  echo $state;  ?>
      <br />
      <?php  echo $country;  ?>
    </div>
</div>
</div>
<div class="divTableRow">
<div class="divTableCell"><span style="color: #000; font-size: 18px; font-weight: bold;"><br /><br />Source: <?php echo $source; ?><br />
 <?php echo $mode; ?> - <?php echo $target; ?></span></div>
<div class="divTableCell"><span style="color: #000; font-size: 18px; font-weight: bold;"><br /><br />Duration: <?php echo $duration ?><br />
Loss: <?php echo $loss ?>  BER: <?php echo $ber ?></span></div>
</div>
</div>
</div>

