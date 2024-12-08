<?php
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Thuisbatterij Laders         //
//                          Variables                            //
// **************************************************************//
//                                                               //

// Debug?
	$debug				   = 'yes';							     // Waarde 'yes' of 'no'

// Homewizard variables
	$hwP1IP				   = '0.0.0.0';					         // IP Homewizard P1 Meter
	$hwKwhIP			   = '0.0.0.0';					         // IP Homewizard Solar kwh Meter
	$hwEcoFlowIP		   = '0.0.0.0';					         // IP Homewizard EcoFlow socket
	$hwChargerOneIP 	   = '0.0.0.0';					         // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 	   = '0.0.0.0';					         // IP Homewizard Charger TWO 600w socket
	$hwChargerThreeIP 	   = '0.0.0.0';					         // IP Homewizard Charger THREE 300w socket

// Domoticz variables
	$domoticzIP			    = '127.0.0.1:8080'; 	        	 // IP + poort van Domoticz
	$batteryPercentageIDX   = '64';

// Domoticz URLs
	$baseUrl = 'http://'.$domoticzIP.'/json.htm?type=command&param=getdevices&rid=';
	$urls    = ['batteryPercentageIDX' => $baseUrl . $batteryPercentageIDX,];
	
// Lader/Batterij variables
	$batteryVolt		   = 25.6;								 // Voltage van je batterij
	$batteryAh             = 200;                                // Totale Ah van alle batterijen
	$chargerOneUsage	   = 350;								 // Verbruik van Lader 1 (Watt)
	$chargerTwoUsage	   = 600;								 // Verbruik van Lader 2 (Watt)
	$chargerThreeUsage     = 350;								 // Verbruik van Lader 3 (Watt)
	$chargerWattsIdle	   =  14;								 // Standby Watts van alle laders wanneer batterijen vol zijn
	$chargerEfficiency     =  80;                                // Laad efficientie, om de daadwerkelijke beschikbare kWh uit te rekenen
	$keepChargerOn         = 'yes';                              // Waarde ýes' of 'no' Bij 'yes' Gaat en blijft de kleinste lader langer AAN ongeacht wel of geen overschot
	
// Ecoflow Powerstream API variables
	$ecoflowPath		   = '/path/2/files/';	                 // Path waar je scripts zich bevinden
	$ecoflowAccessKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API access key
	$ecoflowSecretKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API secret key
	$ecoflowSerialNumber   = ['HWXXXXXXXXXXXXXX',];				 // Powerstream serie nummer

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Thuisbatterij opladen        //
//                  Functions & Get/Set Data                     //
// **************************************************************//
//                                                               //

// Print Header
	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo '  _____          _____ _               '.PHP_EOL;
	echo ' | ____|___ ___ |  ___| | _____      __'.PHP_EOL;
	echo ' |  _| / __/ _ \| |_  | |/ _ \ \ /\ / /'.PHP_EOL;
	echo ' | |__| (_| (_) |  _| | | (_) \ V  V / '.PHP_EOL;
	echo ' |_____\___\___/|_|   |_|\___/ \_/\_/  '.PHP_EOL;
	echo ' '.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' --    LiFePo4 12/12/20a Chargers    --'.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	
// Require ecoflow API class file	
	require_once(''.$ecoflowPath.'ecoflow-api-class.php');

// Get Ecoflow status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$ecoflowSerialNumber = file_get_contents(''.$ecoflowPath.'serialnumber.txt');
		$batterijEmpty = 0;
	if ($ecoflowSerialNumber === false) {
		if ($debug == 'yes'){
		echo '  -- ERROR: Kan serialnumber.txt niet openen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '  --------------------------------------'.PHP_EOL;
		}
		exit(1);
	}

	if (empty(trim($ecoflowSerialNumber))) {
		if ($debug == 'yes'){
		echo '  -- ERROR: Batterij leeg!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '  --------------------------------------'.PHP_EOL;
		}

	} else {
		
		$inv = $ecoflow->getDevice($ecoflowSerialNumber);
		if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
		if ($debug == 'yes'){
		echo '  -- ERROR: Kan EcoFlow inverter gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '  --------------------------------------'.PHP_EOL;
		}
		exit(1);
		}

// Function GET HomeWizard data
	function getHwData($ip) {
		global $debug;
		$hwData = curl_init();
		curl_setopt($hwData, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($hwData, CURLOPT_RETURNTRANSFER, true);
		$hwDataResult = curl_exec($hwData);

		if (curl_errno($hwData)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
			$hwDataDecode = json_decode($hwDataResult);
			$hwDataDecoded         = round($hwDataDecode->active_power_w);
			return $hwDataDecoded;
			curl_close($hwData);
		}
	}

// Function GET HomeWizard Total Input Data
	function getHwTotalData($ip) {
		global $debug;
		$hwData = curl_init();
		curl_setopt($hwData, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($hwData, CURLOPT_RETURNTRANSFER, true);
		$hwDataResult = curl_exec($hwData);

		if (curl_errno($hwData)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
			$hwDataDecode  = json_decode($hwDataResult);
			$hwDataDecoded = round($hwDataDecode->total_power_import_kwh, 3);
			return $hwDataDecoded;
			curl_close($hwData);
		}
	}
	
// Function GET HomeWizard Total Output Data
	function getHwTotalOutputData($ip) {
		global $debug;
		$hwData = curl_init();
		curl_setopt($hwData, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($hwData, CURLOPT_RETURNTRANSFER, true);
		$hwDataResult = curl_exec($hwData);

		if (curl_errno($hwData)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
			$hwDataDecode  = json_decode($hwDataResult);
			$hwDataDecoded = round($hwDataDecode->total_power_export_kwh, 3);
			return $hwDataDecoded;
			curl_close($hwData);
		}
	}
	
// Function GET HomeWizard (energy-socket) status
	function getHwStatus($ip) {
		global $debug;
		$hwChargerStatus = curl_init();
		curl_setopt($hwChargerStatus, CURLOPT_URL, "http://".$ip."/api/v1/state");
		curl_setopt($hwChargerStatus, CURLOPT_RETURNTRANSFER, true);
		$hwChargerStatusResult = curl_exec($hwChargerStatus);

		if (curl_errno($hwChargerStatus)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
		  $hwChargerStatusDecode = json_decode($hwChargerStatusResult);
		  $hwChargerStatus  = abs($hwChargerStatusDecode->power_on);
		  
		  if ($hwChargerStatus == 1){
			  $hwChargerStatus  = 'On';
		  } else {
			$hwChargerStatus  = 'Off';  
		  }
		  return $hwChargerStatus;
		  curl_close($hwChargerStatus);	  
		}
	}
	
// Function Switch HomeWizard (energy-socket) status
	function switchHwSocket($energySocket,$cmd) {
		global $debug;
		global $hwChargerOneIP;
		global $hwChargerTwoIP;
		global $hwChargerThreeIP;
		global $hwEcoFlowIP;
		
		$socket = curl_init();
		if ($energySocket == 'two') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerTwoIP.'/api/v1/state');			
		} elseif ($energySocket == 'one') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerOneIP.'/api/v1/state');
		} elseif ($energySocket == 'three') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerThreeIP.'/api/v1/state');
		}
		curl_setopt($socket, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($socket, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($socket, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
		]);
		
		if ($cmd == 'On') {
			$cmd = 'true';
		} elseif ($cmd == 'Off') {
			$cmd = 'false';
		}
		
		curl_setopt($socket, CURLOPT_POSTFIELDS, '{"power_on": '.$cmd.'}');
		
		$response = curl_exec($socket);

		curl_close($socket);
	}

// Function write batterInput_Start.txt
	function writeBattInputStart($state)
	{
		global $ecoflowPath;
		$filePath = ''.$ecoflowPath.'batteryInput_Start.txt';
		$file = fopen($filePath, "w");
		if ($file === false) {
			die("Unable to open file!");
		}
		fwrite($file, $state);
		fclose($file);
	}

// Function write batterInput_End.txt
	function writeBattInputEnd($state)
	{
		global $ecoflowPath;
		$filePath = ''.$ecoflowPath.'batteryInput_End.txt';
		$file = fopen($filePath, "w");
		if ($file === false) {
			die("Unable to open file!");
		}
		fwrite($file, $state);
		fclose($file);
	}
	
// Function write batterOutput.txt
	function writeBattOutputStart($state)
	{
		global $ecoflowPath;
		$filePath = ''.$ecoflowPath.'batteryOutput_Start.txt';
		$file = fopen($filePath, "w");
		if ($file === false) {
			die("Unable to open file!");
		}
		fwrite($file, $state);
		fclose($file);
	}

// Function write batterOutputDiff.txt
	function writeBattOutputEnd($state)
	{
		global $ecoflowPath;
		$filePath = ''.$ecoflowPath.'batteryOutput_End.txt';
		$file = fopen($filePath, "w");
		if ($file === false) {
			die("Unable to open file!");
		}
		fwrite($file, $state);
		fclose($file);
	}
	
	function UpdatePercentageDevice($idx,$cmd) {
	  global $domoticzIP;
	  $reply=json_decode(file_get_contents('http://'.$domoticzIP.'/json.htm?type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$cmd.';0'),true);
	  if($reply['status']=='OK') $reply='OK';else $reply='ERROR';
	  return $reply;
	}
	
// HomeWizard GET Variables
	$hwP1Usage            = getHwData($hwP1IP);
	$hwSolarReturn        = getHwData($hwKwhIP);
	$hwInvReturn          = getHwData($hwEcoFlowIP);
	$hwchargerOneUsage    = getHwData($hwChargerOneIP);
	$hwchargerTwoUsage    = getHwData($hwChargerTwoIP);
	$hwchargerThreeUsage  = getHwData($hwChargerThreeIP);
	$chargerOneStatus     = getHwStatus($hwChargerOneIP);
	$chargerTwoStatus     = getHwStatus($hwChargerTwoIP);
	$chargerThreeStatus   = getHwStatus($hwChargerThreeIP);

	$hwInvTotal           = getHwTotalOutputData($hwEcoFlowIP);
	$hwchargerOneTotal    = getHwTotalData($hwChargerOneIP);
	$hwchargerTwoTotal    = getHwTotalData($hwChargerTwoIP);
	$hwchargerThreeTotal  = getHwTotalData($hwChargerThreeIP);
	$hwchargerTotal       =	($hwchargerOneTotal + $hwchargerTwoTotal + $hwchargerThreeTotal);
	
// Get battery Voltage
	$pv1InputVolt 		  = ($inv['data']['20_1.pv1InputVolt']) / 10;
	$pv2InputVolt 		  = ($inv['data']['20_1.pv2InputVolt']) / 10;
	$pvAvInputVoltage     = ($pv1InputVolt + $pv2InputVolt) / 2;
	
// Get Inverter output Watts
	$pv1InputWatts        = ($inv['data']['20_1.pv1InputWatts']) / 10;
	$pv2InputWatts        = ($inv['data']['20_1.pv2InputWatts']) / 10;
	$pvAvInputWatts       = ($pv1InputWatts + $pv2InputWatts);
	
// Get Current Baseload
	$currentBaseload	  = ($inv['data']['20_1.permanentWatts']) / 10;

// Get Inverter Temperature
	$invTemp              = ($inv['data']['20_1.llcTemp']) / 10;

// Determine Power Usage
	$chargerUsage         = ($hwchargerOneUsage + $hwchargerTwoUsage + $hwchargerThreeUsage);
	$productionTotal      = ($hwSolarReturn + $hwInvReturn);
	$realUsage            = ($hwP1Usage - $productionTotal);
	$P1ChargerUsage       = ($hwP1Usage - $chargerUsage);

	if ($keepChargerOn == 'no'){
	$chargerOneUsage  	  = -abs($chargerOneUsage);
	} else {
	$chargerOneUsage  	  = -abs($chargerOneUsage) / 1.5;
	}

	$chargerTwoUsage  	  = -abs($chargerTwoUsage);
	$chargerOneTwoUsage   = -abs($chargerOneUsage + $chargerTwoUsage); 
	$chargerThreeUsage    = -abs($chargerThreeUsage);
	$chargerTotalUsage    = ($chargerOneUsage + $chargerTwoUsage + $chargerThreeUsage);
	
// Write Battery Input Total
	$batteryInputStartFile  = ''.$ecoflowPath.'batteryInput_Start.txt';
	$batteryInputEndFile    = ''.$ecoflowPath.'batteryInput_End.txt';
	$batteryOutputStartFile = ''.$ecoflowPath.'batteryOutput_Start.txt';
	$batteryOutputEndFile   = ''.$ecoflowPath.'batteryOutput_End.txt';

	if (file_exists($batteryInputStartFile) && file_exists($batteryInputEndFile)) {
	$batteryInputStartkWh   = file_get_contents(''.$batteryInputStartFile.'');
	$batteryInputStartkWh   = round($batteryInputStartkWh, 2);
	$batteryInputEndkWh     = file_get_contents(''.$batteryInputEndFile.'');
	$batteryInputEndkWh     = round($batteryInputEndkWh, 2);
	
		if ($chargerUsage >= $chargerWattsIdle){
			writeBattInputEnd(''.$hwchargerTotal.'');	
		}
		
		if ($pvAvInputVoltage >= 0 && $pvAvInputVoltage <= 23.5 && $pvAvInputWatts == 0) {
			writeBattInputStart(''.$hwchargerTotal.'');
			writeBattInputEnd(''.$hwchargerTotal.'');
		}
		
	} else {
			writeBattInputStart(''.$hwchargerTotal.'');
			writeBattInputEnd(''.$hwchargerTotal.'');
	}

// Write Battery Output Total
	if (file_exists($batteryOutputStartFile) && file_exists($batteryOutputEndFile)) {
	$batteryOutputStartkWh  = file_get_contents(''.$batteryOutputStartFile.'');
	$batteryOutputStartkWh  = round($batteryOutputStartkWh, 2);
	$batteryOutputEndkWh    = file_get_contents(''.$batteryOutputEndFile.'');
	$batteryOutputEndkWh    = round($batteryOutputEndkWh, 2);
	
		if ($hwInvTotal <= -1){
			writeBattOutputEnd(''.$hwInvTotal.'');	
		}
		
		if ($pvAvInputVoltage >= 0 && $pvAvInputVoltage <= 23.5 && $pvAvInputWatts == 0) {
			writeBattOutputStart(''.$hwInvTotal.'');
			writeBattOutputEnd(''.$hwInvTotal.'');
		}
		
	} else {
			writeBattOutputStart(''.$hwInvTotal.'');
			writeBattOutputEnd(''.$hwInvTotal.'');
	}
	
// Calculate Battery Input/Output Total	
	$batteryTotalCharged    = round($batteryInputEndkWh - $batteryInputStartkWh, 2);
	$batteryTotalDischarged = round($batteryOutputEndkWh - $batteryOutputStartkWh, 2);
	$batteryAvail           = abs($batteryTotalCharged - $batteryTotalDischarged);
	$batteryAvail           = round($batteryAvail / 100 * $chargerEfficiency, 2);
	$batteryAh				= ($batteryAh / 1000);
	$batteryCapacity		= round($batteryVolt * $batteryAh, 2);
	$batterySOC				= round($batteryAvail / $batteryCapacity * 100, 1);

// Write Battery SOC to Domoticz
	UpdatePercentageDevice($batteryPercentageIDX, ''.$batterySOC.'');
	
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Thuisbatterij Laders         //
//                             Print                             //
// **************************************************************//
//                                                               //

	if ($debug == 'yes'){
		echo ' -/- Laders                   -\-'.PHP_EOL;
		echo '  -- Lader 1                   : '.$chargerOneStatus.''.PHP_EOL;	
		echo '  -- Lader 2                   : '.$chargerTwoStatus.''.PHP_EOL;
		echo '  -- Lader 3                   : '.$chargerThreeStatus.''.PHP_EOL;
		echo '  -- Laders verbruik           : '.$chargerUsage.' Watt'.PHP_EOL;		
		echo ' '.PHP_EOL;
		
		echo ' -/- Batterij @ '.$batteryCapacity.' kWh      -\-'.PHP_EOL;
		echo '  -- Batterij voltage          : '.$pvAvInputVoltage.' Volt'.PHP_EOL;
		echo '  -- Batterij SOC              : '.$batterySOC.'%'.PHP_EOL;			
			
		echo ' '.PHP_EOL;
		echo ' -/- Input/Output             -\-'.PHP_EOL;
		echo '  -- Totaal geladen            : '.$batteryTotalCharged.' kWh'.PHP_EOL;
		echo '  -- Totaal ontladen           : '.$batteryTotalDischarged.' kWh'.PHP_EOL;
		echo '  -- kWh Beschikbaar (EF '.$chargerEfficiency.'%)  : '.$batteryAvail.' kWh'.PHP_EOL;
		echo ' '.PHP_EOL;
		
		echo ' -/- EcoFlow Omvormer         -\-'.PHP_EOL;
		echo '  -- Temperatuur               : '.$invTemp.'˚C'.PHP_EOL;
		echo ' '.PHP_EOL;
		
		echo ' -/- Energie                  -\-'.PHP_EOL;
		echo '  -- P1-Meter                  : '.$hwP1Usage.' Watt'.PHP_EOL;
		echo '  -- Zonnepanelen opwek        : '.$hwSolarReturn.' Watt'.PHP_EOL;
		echo '  -- Batterij opwek            : '.$hwInvReturn.' Watt'.PHP_EOL;
		echo '  -- Echte verbruik            : '.$realUsage.' Watt'.PHP_EOL;
		echo '  -- Verbruik excl laders      : '.$P1ChargerUsage.' Watt'.PHP_EOL;
	}
	
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Thuisbatterij Laders         //
//                        Start/Stop Laden                       //
// **************************************************************//
//                                                               //

	if ($debug == 'yes'){	
	echo ' '.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}

// Lader 1 of 2 of 3 UIT	
	if ($P1ChargerUsage > $chargerOneUsage || $chargerUsage <= $chargerWattsIdle || $pvAvInputWatts != 0 || $hwSolarReturn == 0){
		if ($debug == 'yes'){echo '  -- Laders 1 of 2 of 3 UIT'.PHP_EOL;}	

		if (($chargerOneStatus == 'On' && $keepChargerOn == 'no') && ($hwSolarReturn >= $chargerOneUsage || $pvAvInputVoltage > 26.3)){ switchHwSocket('one','Off'); sleep(10);}			
		if (($chargerOneStatus == 'On' && $keepChargerOn == 'yes') && ($hwSolarReturn >= $chargerOneUsage || $pvAvInputVoltage > 26.3)){ switchHwSocket('one','Off'); sleep(10);}			
		if ($chargerOneStatus == 'Off' && $keepChargerOn == 'yes' && $hwSolarReturn < $chargerOneUsage && $pvAvInputWatts == 0 && $pvAvInputVoltage < 26.3){ switchHwSocket('one','On'); sleep(5);}	

		if ($chargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(10);}
		if ($chargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1 AAN - Lader 2 & 3 UIT			
	if ($P1ChargerUsage > $chargerTwoUsage && $P1ChargerUsage <= $chargerOneUsage && $pvAvInputVoltage < 26.3 && $pvAvInputWatts == 0 && $hwSolarReturn != 0){
		if ($debug == 'yes'){echo '  -- Lader 1 AAN - Lader 2 & 3 UIT'.PHP_EOL;}
		if ($chargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($chargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(10);}
		if ($chargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 2 AAN - Lader 1 & 3 UIT
	if ($P1ChargerUsage > $chargerOneTwoUsage && $P1ChargerUsage <= $chargerTwoUsage && $pvAvInputVoltage < 26.3 && $pvAvInputWatts == 0 && $hwSolarReturn != 0){
		if ($debug == 'yes'){echo '  -- Lader 2 AAN - Lader 1 & 3 UIT'.PHP_EOL;}
		if ($chargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($chargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(10);}
		if ($chargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1 & 2 AAN - Lader 3 UIT
	if ($P1ChargerUsage > $chargerTotalUsage && $P1ChargerUsage <= $chargerOneTwoUsage && $pvAvInputVoltage < 26.3 && $pvAvInputWatts == 0 && $hwSolarReturn != 0){
		if ($debug == 'yes'){echo '  -- Lader 1 & 2 AAN - Lader 3 UIT'.PHP_EOL;}
		if ($chargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($chargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($chargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1, 2, & 3 AAN
	if ($P1ChargerUsage <= $chargerTotalUsage && $pvAvInputVoltage < 26.3 && $pvAvInputWatts == 0 && $hwSolarReturn != 0){
		if ($debug == 'yes'){echo '  -- Lader 1, 2, & 3 AAN'.PHP_EOL;}
		if ($chargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($chargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($chargerThreeStatus == 'Off'){ switchHwSocket('three','On');}
	}
	
// Print Footer
	if ($debug == 'yes'){	
	echo ' '.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' --              The End             --'.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
}
?>