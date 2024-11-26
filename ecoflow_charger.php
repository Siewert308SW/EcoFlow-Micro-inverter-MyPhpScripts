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
	$hwP1IP				   = '0.0.0.0';					        // IP Homewizard P1 Meter
	$hwKwhIP			   = '0.0.0.0';					        // IP Homewizard Solar kwh Meter
	$hwEcoFlowIP		   = '0.0.0.0';					        // IP Homewizard EcoFlow socket
	$hwChargerOneIP 	   = '0.0.0.0';     			        // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 	   = '0.0.0.0';     			        // IP Homewizard Charger TWO 600w socket
	$hwChargerThreeIP 	   = '0.0.0.0';     			        // IP Homewizard Charger THREE 300w socket
	
// Lader/Batterij variables
	$chargerOneUsage	   = 340;								 // Verbruik van Lader 1 (Watt)
	$chargerTwoUsage	   = 590;								 // Verbruik van Lader 2 (Watt)
	$chargerThreeUsage     = 340;								 // Verbruik van Lader 3 (Watt)
	$chargerWattsIdle	   =  14;								 // Standby Watts van alle laders wanneer batterijen vol zijn
	
// Ecoflow Powerstream API variables
	$ecoflowPath		   = '/path/to/files/';	                 // Path waar je scripts zich bevinden
	$ecoflowAccessKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API access key
	$ecoflowSecretKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API secret key
	$ecoflowSerialNumber   = ['HWXXXXXXXXXXX',];				 // Powerstream serie nummer

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

// Function write batterState.txt
	function writeBattState($state)
	{
		global $ecoflowPath;
		$filePath = ''.$ecoflowPath.'batteryState.txt';
		$file = fopen($filePath, "w");
		if ($file === false) {
			die("Unable to open file!");
		}
		fwrite($file, $state);
		fclose($file);
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
	
	$chargerOneUsage  	  = -abs($chargerOneUsage);
	$chargerTwoUsage  	  = -abs($chargerTwoUsage);
	$chargerOneTwoUsage   = -abs($chargerOneUsage + $chargerTwoUsage); 
	$chargerThreeUsage    = -abs($chargerThreeUsage);
	$chargerTotalUsage    = ($chargerOneUsage + $chargerTwoUsage + $chargerThreeUsage);
	
//Write battery State	
	if ($pvAvInputVoltage <= 22.7) {
	writeBattState('leeg');
	} elseif ($pvAvInputVoltage > 22.7 && $pvAvInputVoltage <= 24.95) {
	writeBattState('half');
	} elseif ($pvAvInputVoltage >= 26.6) {
	writeBattState('geladen');
	}
	$batteryState = file_get_contents(''.$ecoflowPath.'batteryState.txt');
	
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
		echo '  -- Laders Totaal-Verbruik    : '.$chargerUsage.'w'.PHP_EOL;		
		echo ' '.PHP_EOL;
		
		echo ' -/- Batterij                 -\-'.PHP_EOL;
		echo '  -- Batterij Voltage          : '.$pvAvInputVoltage.'v'.PHP_EOL;
		echo '  -- Batterij State            : '.$batteryState.''.PHP_EOL;
		echo ' '.PHP_EOL;

		echo ' -/- EcoFlow Omvormer         -\-'.PHP_EOL;
		echo '  -- Temperatuur               : '.$invTemp.'c'.PHP_EOL;
		echo ' '.PHP_EOL;
		
		echo ' -/- Energie                  -\-'.PHP_EOL;
		echo '  -- P1-Meter                  : '.$hwP1Usage.'w'.PHP_EOL;
		echo '  -- Zonnepanelen opwek        : '.$hwSolarReturn.'w'.PHP_EOL;
		echo '  -- Batterij opwek            : '.$hwInvReturn.'w'.PHP_EOL;
		echo '  -- Echte Verbruik            : '.$realUsage.'w'.PHP_EOL;
		echo '  -- Stroomverbruik excl laders: '.$P1ChargerUsage.'w'.PHP_EOL;
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
		if ($chargerOneStatus == 'On' && $hwSolarReturn >= $chargerOneUsage){ switchHwSocket('one','Off'); sleep(2);}			
		if ($chargerTwoStatus == 'On' && $hwSolarReturn >= $chargerOneTwoUsage){ switchHwSocket('two','Off'); sleep(2);}
		if ($chargerThreeStatus == 'On' && $hwSolarReturn >= $chargerTotalUsage){ switchHwSocket('three','Off');}
	}

// Lader 1 AAN - Lader 2 & 3 UIT			
	if (($P1ChargerUsage > $chargerTwoUsage && $P1ChargerUsage <= $chargerOneUsage) && ($pvAvInputVoltage <= 26 && $pvAvInputWatts == 0 && $hwSolarReturn != 0)){
		if ($debug == 'yes'){echo '  -- Lader 1 AAN - Lader 2 & 3 UIT'.PHP_EOL;}
		if ($chargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(2);}
		if ($chargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(2);}
		if ($chargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 2 AAN - Lader 1 & 3 UIT
	if (($P1ChargerUsage > $chargerOneTwoUsage && $P1ChargerUsage <= $chargerTwoUsage) && ($pvAvInputVoltage <= 26 && $pvAvInputWatts == 0 && $hwSolarReturn != 0)){
		if ($debug == 'yes'){echo '  -- Lader 2 AAN - Lader 1 & 3 UIT'.PHP_EOL;}
		if ($chargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(2);}
		if ($chargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(2);}
		if ($chargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1 & 2 AAN - Lader 3 UIT
	if (($P1ChargerUsage > $chargerTotalUsage && $P1ChargerUsage <= $chargerOneTwoUsage) && ($pvAvInputVoltage <= 26 && $pvAvInputWatts == 0 && $hwSolarReturn != 0)){
		if ($debug == 'yes'){echo '  -- Lader 1 & 2 AAN - Lader 3 UIT'.PHP_EOL;}
		if ($chargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(2);}
		if ($chargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(2);}
		if ($chargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1, 2, & 3 AAN
	if ($P1ChargerUsage <= $chargerTotalUsage && $pvAvInputVoltage <= 26 && $pvAvInputWatts == 0 && $hwSolarReturn != 0){
		if ($debug == 'yes'){echo '  -- Lader 1, 2, & 3 AAN'.PHP_EOL;}
		if ($chargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(2);}
		if ($chargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(2);}
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