<?php
//															     //
// **************************************************************//
//          LiFePo4 10/20A Charger automatic charging            //
// **************************************************************//
//                                                               //

// Debug?
	$debug					= 'yes';							 // Waarde 'yes' of 'no'.
	
// Homewizard variables
	$hwP1IP 				= '0.0.0.0'; 			             // IP Homewizard P1 Meter
	$hwKwhIP 			    = '0.0.0.0';     			         // IP Homewizard Solar kwh Meter
	$hwEcoFlowIP		    = '0.0.0.0';					     // IP Homewizard EcoFlow socket
	$hwChargerOneIP 		= '0.0.0.0';     			         // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 		= '0.0.0.0';     			         // IP Homewizard Charger TWO 600w socket
	
// Lader/Batterij variables
	$minPowerOneReturn		= -300;								 // Minimale Wattage teruglevering wanneer de lader 1 mag starten
	$minPowerTwoReturn		= -600;								 // Minimale Wattage teruglevering wanneer de lader 2 mag starten
	
// Ecoflow Powerstream API variables
	$ecoflowPath			= '/path/to/files/';	                // Path waar je scripts zich bevinden
	$ecoflowAccessKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	// Powerstream API access key
	$ecoflowSecretKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	// Powerstream API secret key
	$ecoflowSerialNumber	= ['HWXXXXXXXXXXXXXX',];				// Powerstream serie nummer

//															     //
// **************************************************************//
//                 LiFePo4 10/20A Charger Start                  //
// **************************************************************//
//                                                               //

	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' --   LiFePo4 10/20A Auto Charging   --'.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}

// Path naar ecoflow API class file	
	require_once(''.$ecoflowPath.'ecoflow-api-class.php');
	
// Get Ecoflow status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$ecoflowSerialNumber = file_get_contents(''.$ecoflowPath.'serialnumber.txt');
		$batterijEmpty = 0;
	if ($ecoflowSerialNumber === false) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan het serialnumber.txt bestand niet openen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(1);
	}

	if (empty(trim($ecoflowSerialNumber))) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Accus is leeg!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
			$batterijEmpty = 1;
	} else {
		
		$inv = $ecoflow->getDevice($ecoflowSerialNumber);
		if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan gegevens van de omvormer niet ophalen!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(1);
		}

// Function Toggle HW Charger socket
	function SwitchSocket($chargerSocket,$cmd) {
	  global $hwChargerOneIP;
	  global $hwChargerTwoIP;
		$socket = curl_init();
		if ($chargerSocket == '600w') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerTwoIP.'/api/v1/state');			
		} elseif ($chargerSocket == '300w') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerOneIP.'/api/v1/state');
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

// Get HomeWizard P1 verbruik
	$hwP1 = curl_init();
	curl_setopt($hwP1, CURLOPT_URL, "http://".$hwP1IP."/api/v1/data");
	curl_setopt($hwP1, CURLOPT_RETURNTRANSFER, true);
	$hwP1result = curl_exec($hwP1);

	if (curl_errno($hwP1)) { 
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard P1-meter gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(0);	

	} else {
	  $hwP1UsageDecode = json_decode($hwP1result);
	  $P1Usage         = round($hwP1UsageDecode->active_power_w);
	  curl_close($hwP1);
	}

// Get HomeWizard PV (kwh meter) opwek
	$hwSolar = curl_init();
	curl_setopt($hwSolar, CURLOPT_URL, "http://".$hwKwhIP."/api/v1/data");
	curl_setopt($hwSolar, CURLOPT_RETURNTRANSFER, true);
	$hwSolarresult = curl_exec($hwSolar);

	if (curl_errno($hwSolar)) { 
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard Kwh-meter gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(0);	

	} else {
	  $hwSolarDecode = json_decode($hwSolarresult);
	  $PVProduction  = round($hwSolarDecode->active_power_w);
	  curl_close($hwSolar);
	}
	
// Get HomeWizard Batterij (energy-socket) opwek
	$hwSocket = curl_init();
	curl_setopt($hwSocket, CURLOPT_URL, "http://".$hwEcoFlowIP."/api/v1/data");
	curl_setopt($hwSocket, CURLOPT_RETURNTRANSFER, true);
	$hwSocketResult = curl_exec($hwSocket);

	if (curl_errno($hwSocket)) { 
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard EcoFlow Socket gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(0);	

	} else {
    $hwSocketProductionDecode = json_decode($hwSocketResult);
	$hwSocketProduction = round($hwSocketProductionDecode->active_power_w);
	
	if ($hwSocketProduction > 0) {
	$SocketProduction = 0;
	} else {
	$SocketProduction = ($hwSocketProduction);
	}
	curl_close($hwSocket);
	}

// Get HomeWizard Charger ONE (energy-socket) verbruik
	$hwChargerOne = curl_init();
	curl_setopt($hwChargerOne, CURLOPT_URL, "http://".$hwChargerOneIP."/api/v1/data");
	curl_setopt($hwChargerOne, CURLOPT_RETURNTRANSFER, true);
	$hwChargerOneResult = curl_exec($hwChargerOne);

	if (curl_errno($hwChargerOne)) { 
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard Charger ONE Socket gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(0);	

	} else {
	  $hwChargerOneDecode = json_decode($hwChargerOneResult);
	  $chargerOneUsage  = round($hwChargerOneDecode->active_power_w);
	  curl_close($hwChargerOne);	
	}

// Get HomeWizard Charger TWO (energy-socket) verbruik
	$hwChargerTwo = curl_init();
	curl_setopt($hwChargerTwo, CURLOPT_URL, "http://".$hwChargerTwoIP."/api/v1/data");
	curl_setopt($hwChargerTwo, CURLOPT_RETURNTRANSFER, true);
	$hwChargerTwoResult = curl_exec($hwChargerTwo);

	if (curl_errno($hwChargerTwo)) { 
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard Charger TWO Socket gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(0);	

	} else {
	  $hwChargerTwoDecode = json_decode($hwChargerTwoResult);
	  $chargerTwoUsage  = round($hwChargerTwoDecode->active_power_w);
	  curl_close($hwChargerTwo);	
	}

// Get HomeWizard Charger ONE (energy-socket) status
	$hwChargerOneStatus = curl_init();
	curl_setopt($hwChargerOneStatus, CURLOPT_URL, "http://".$hwChargerOneIP."/api/v1/state");
	curl_setopt($hwChargerOneStatus, CURLOPT_RETURNTRANSFER, true);
	$hwChargerOneStatusResult = curl_exec($hwChargerOneStatus);

	if (curl_errno($hwChargerOneStatus)) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard Charger ONE Socket gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(0);	

	} else {
	  $hwChargerOneStatusDecode = json_decode($hwChargerOneStatusResult);
	  $chargerOneStatus  = abs($hwChargerOneStatusDecode->power_on);
	  
	  if ($chargerOneStatus == 1){
		  $chargerOneStatus  = 'On';
	  } else {
		$chargerOneStatus  = 'Off';  
	  }
	  curl_close($hwChargerOneStatus);	  
	}

// Get HomeWizard Charger TWO (energy-socket) status
	$hwChargerTwoStatus = curl_init();
	curl_setopt($hwChargerTwoStatus, CURLOPT_URL, "http://".$hwChargerTwoIP."/api/v1/state");
	curl_setopt($hwChargerTwoStatus, CURLOPT_RETURNTRANSFER, true);
	$hwChargerTwoStatusResult = curl_exec($hwChargerTwoStatus);

	if (curl_errno($hwChargerTwoStatus)) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard Charger TWO Socket gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		exit(0);	

	} else {
	  $hwChargerTwoStatusDecode = json_decode($hwChargerTwoStatusResult);
	  $chargerTwoStatus  = abs($hwChargerTwoStatusDecode->power_on);
	  
	  if ($chargerTwoStatus == 1){
		  $chargerTwoStatus  = 'On';
	  } else {
		$chargerTwoStatus  = 'Off';  
	  }
	  curl_close($hwChargerTwoStatus);	  
	}
	
// Get batterij Voltage
	$pv1InputVolt 		  = ($inv['data']['20_1.pv1InputVolt']) / 10;
	$pv2InputVolt 		  = ($inv['data']['20_1.pv2InputVolt']) / 10;
	$pvAvInputVoltage     = ($pv1InputVolt + $pv2InputVolt) / 2;
	
// Get EcoFlow input Watts
	$pv1InputWatts        = ($inv['data']['20_1.pv1InputWatts']) / 10;
	$pv2InputWatts        = ($inv['data']['20_1.pv2InputWatts']) / 10;
	$pvAvInputWatts       = ($pv1InputWatts + $pv2InputWatts);
	
// Get EcoFlow temp
	$pvTemp               = ($inv['data']['20_1.llcTemp']) /10;
	
// Get Huidige Baseload
	$currentBaseload	  = ($inv['data']['20_1.permanentWatts']) / 10;

// Batterij leeg?	
	if ($pvAvInputVoltage <= 22.5) {
	$batterijEmpty = 1;
	} elseif ($pvAvInputVoltage >= 0 && $pvAvInputVoltage <= 22.8 && $pvAvInputWatts == 0) {
	$batterijEmpty = 1;
	} else {
	$batterijEmpty = 0;
	}
	
// Get EcoFlow temp
	$invTemp = ($inv['data']['20_1.llcTemp']) / 10;

// Bepaal totale oplader verbruik
	$chargerUsage = ($chargerOneUsage + $chargerTwoUsage);

// Bepaal de echte verbruik
	$productionTotal = ($PVProduction + $SocketProduction);
	$realUsage = ($P1Usage - $productionTotal);
	
// Print 
	if ($debug == 'yes'){
		echo '-/- Laders                   -\-'.PHP_EOL;
	if ($chargerOneStatus == 'On' && $chargerTwoStatus == 'Off') {
		echo ' -- Lader 1                   : '.$chargerOneStatus.''.PHP_EOL;
		echo ' -- Lader 1 Verbruik          : '.$chargerOneUsage.'w'.PHP_EOL;
	} elseif ($chargerOneStatus == 'On' && $chargerTwoStatus == 'On') {
		echo ' -- Lader 1 & 2               : '.$chargerTwoStatus.''.PHP_EOL;
		echo ' -- Lader 1 & 2 Verbruik      : '.$chargerUsage.'w'.PHP_EOL;
	} elseif ($chargerOneStatus == 'Off' && $chargerTwoStatus == 'Off') {
		echo ' -- Lader 1 & 2               : '.$chargerOneStatus.''.PHP_EOL;
		echo ' -- Lader 1 & 2 Verbruik      : '.$chargerUsage.'w'.PHP_EOL;
	}
		echo ' '.PHP_EOL;
		echo '-/- Batterij                 -\-'.PHP_EOL;
		echo ' -- Batterij Voltage          : '.$pvAvInputVoltage.'v'.PHP_EOL;
		if (($chargerOneStatus == 'On' || $chargerTwoStatus == 'On') && ($chargerUsage > 6)) {	
		echo ' -- Batterij wordt opgeladen'.PHP_EOL;
		} elseif ($chargerOneStatus == 'On' && $chargerTwoStatus == 'On' && $chargerUsage <= 12) {	
		echo ' -- Batterij oplader standby'.PHP_EOL;	
		} elseif ($chargerOneStatus == 'Off' && $chargerTwoStatus == 'Off') {
		echo ' -- Batterij wordt niet opgeladen'.PHP_EOL;
		}
		if ($batterijEmpty == 1) {
		echo ' -- Batterij leeg!'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		echo '-/- EcoFlow Inverter         -\-'.PHP_EOL;
		echo ' -- Temperatuur               : '.$pvTemp.'c'.PHP_EOL;
		echo ' -- Output                    : '.$pvAvInputWatts.'w'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '-/- Energie                  -\-'.PHP_EOL;
		echo ' -- P1 Verbruik               : '.$P1Usage.'w'.PHP_EOL;
		echo ' -- Zonnepanelen              : '.$PVProduction.'w'.PHP_EOL;
		echo ' -- Batterij Opwek            : '.$SocketProduction.'w'.PHP_EOL;
		echo ' -- Echte Verbruik            : '.$realUsage.'w'.PHP_EOL;
	}
	
//															     //
// **************************************************************//
//                LiFePo4  10/20A Charging Start                  //
// **************************************************************//
//                                                               //

// Laders AAN
	if ($P1Usage <= $minPowerOneReturn && $PVProduction <= $minPowerOneReturn && $pvAvInputWatts == 0){
		if ($chargerOneStatus == 'Off' && $chargerTwoStatus == 'Off'){
			if ($debug == 'yes'){
			echo ' '.PHP_EOL;
			echo ' -- Lader 1 ingeschakeld'.PHP_EOL;
			}
			switchSocket('300w','On');
			
	} elseif ($chargerOneStatus == 'On' && $chargerTwoStatus == 'Off' && $P1Usage <= $minPowerTwoReturn && $chargerUsage > 6){
			if ($debug == 'yes'){
			echo ' '.PHP_EOL;
			echo ' -- Lader 2 ingeschakeld'.PHP_EOL;
			}
			switchSocket('600w','On');
		}
	}
	
// Laders UIT
	if (($chargerOneStatus == 'On' && $chargerTwoStatus == 'On') && ($P1Usage > 0 || $chargerUsage <= 12 || $pvAvInputWatts != 0 )){
		if ($debug == 'yes'){
			echo ' '.PHP_EOL;
			echo ' -- Lader 2 uitgeschakeld'.PHP_EOL;
		}
			switchSocket('600w','Off');
	}
	
	if (($chargerOneStatus == 'On' && $chargerTwoStatus == 'Off') && ($PVProduction > $minPowerOneReturn || $pvAvInputWatts != 0 )){
		if ($debug == 'yes'){
			echo ' '.PHP_EOL;
			echo ' -- Lader 1 uitgeschakeld'.PHP_EOL;
		}
			switchSocket('300w','Off');
	}
	
		if ($debug == 'yes') {		
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		echo ' --              The End             --'.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;
		}
}
?>