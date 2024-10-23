<?php

//																	//
// *****************************************************************//
//          EcoFlow micro-inverter automatic baseload				//
// *****************************************************************//
//                                                                	//

// Debug?
	$debug					= 'yes';								// Waarde 'yes' of 'no'

// Tijd variables
	$invStartTime			= '00:00';								// Omvormer starttijd
	$invEndTime				= '13:30';								// Omvormer eindtijd
	$runInfinity			= 'auto';								// Waarde 'yes' of 'no' of 'auto'. Bij 'auto' word er 50% soc behouden voor de nacht, Bij yes zal de omvormer starten met opwekken als de zonpanelen niks meer opwekken en worden de begin en eindtijd variables genegeerd

	$latitude              = '00.00000';						    // Latitude is de afstand – noord of zuid – tot de evenaar
	$longitude             = '-0.00000';						    // Longitude is de afstand in graden oost of west tot de Meridiaan in Greenwich
	$zenitLat              = '89.5';							    // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$zenitLong             = '91.7';							    // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$gmt                   = '1';								    // GMT time '1' = true - '0'= false voor UTC
	$timezone              = 'Europe/Amsterdam';			        // Mijn php.ini slikt de timezone niet dus dan maar handmatig instelling
				
// Omvormer variables
	$ecoflowMaxOutput		= 600;									// Maximale aantal output Watts wat de omvormer kan leveren. 
	$ecoflowOutputOffSet	= 10;									// Trek deze value (watts) af van de nieuwe baseload, Deze value wordt alsnog van het net wordt getrokken om teruglevering te voorkomen
	$maxInvTemp             = 65;									// Maximale interne temperatuur, daarboven stopt de omvormer met terugleveren 

// Batterij variables
	$keepSomeCapacity 		= 'no'; 								// Waarde 'yes' of 'no'. Bij 'yes' blijft de accu 3 tot 5% gevuld, bij 'no' wordt de accu leeggetrokken, totdat BMS in werking treedt 
				
// Homewizard variables
	$hwP1IP					= '0.0.0.0';						    // IP Homewizard P1 Meter
	$hwKwhIP				= '0.0.0.0';						    // IP Homewizard Solar kwh Meter
	$hwEcoFlowIP		    = '0.0.0.0';						    // IP Homewizard EcoFlow socket
	$hwChargerOneIP 		= '0.0.0.0';     			            // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 		= '0.0.0.0';     			            // IP Homewizard Charger TWO 600w socket
		
// Ecoflow Powerstream API variables
	$ecoflowPath			= '/path/to/files/';	                // Path waar je scripts zich bevinden
	$ecoflowAccessKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	// Powerstream API access key
	$ecoflowSecretKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	// Powerstream API secret key
	$ecoflowSerialNumber	= ['HWXXXXXXXXXXXXXX',];				// Powerstream serie nummer

//																	//
// *****************************************************************//
//             EcoFlow micro-inverter start script                	//
// *****************************************************************//
//                                                                	//

	if ($debug == 'yes'){
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		echo ' --      EcoFlow Micro-Inverter      --'.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;
	}
	
// Path naar ecoflow API class file
	include(''.$ecoflowPath.'ecoflow-api-class.php');

// php.ini slikt de timezone niet
	date_default_timezone_set(''.$timezone.'');

// Tijd nu
	$timeNow = date('H:i');
	
// Get EcoFlow Status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$serial_number = file_get_contents(''.$ecoflowPath.'serialnumber.txt');	
	if ($serial_number === false) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan het serialnumber.txt bestand niet openen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
	exit(1);
	}

	if (empty(trim($serial_number))) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Accus is leeg!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
	exit(0);
	} else {
		$inv = $ecoflow->getDevice($serial_number);
		if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan gegevens van de omvormer niet ophalen!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
	exit(1);
	}
			
// Get batterij Voltage
	$pv1InputVolt		= ($inv['data']['20_1.pv1InputVolt']) / 10;
	$pv2InputVolt		= ($inv['data']['20_1.pv2InputVolt']) / 10;
	$pvAvInputVoltage	= ($pv1InputVolt + $pv2InputVolt) / 2;
	
// Get EcoFlow input Watts
	$pv1InputWatts      = ($inv['data']['20_1.pv1InputWatts']) / 10;
	$pv2InputWatts      = ($inv['data']['20_1.pv2InputWatts']) / 10;
	$pvAvInputWatts     = ($pv1InputWatts + $pv2InputWatts);

// Get EcoFlow temp
	$invTemp			= ($inv['data']['20_1.llcTemp']) /10;
	
// Get Baseload
	$currentBaseload	= ($inv['data']['20_1.permanentWatts']) / 10;
	
// Batterij leeg?	
	if ($pvAvInputVoltage <= 22.5 && $keepSomeCapacity == 'no') {
	$batterijEmpty = 1;
	} elseif ($pvAvInputVoltage >= 0 && $pvAvInputVoltage <= 22.8 && $pvAvInputWatts == 0 && $keepSomeCapacity == 'no') {
	$batterijEmpty = 1;
	} elseif ($pvAvInputVoltage <= 23.5 && $keepSomeCapacity == 'yes') {
	$batterijEmpty = 1;
	} elseif ($pvAvInputVoltage >= 0 && $pvAvInputVoltage <= 24.0 && $pvAvInputWatts == 0 && $keepSomeCapacity == 'yes') {
	$batterijEmpty = 1;
	} else {
	$batterijEmpty = 0;
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
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
	exit(0);	

	} else {
	$hwP1UsageDecode = json_decode($hwP1result);
	$hwP1Usage = round($hwP1UsageDecode->active_power_w);
	curl_close($hwP1);	
	}

// Get HomeWizard PV (kwh meter) opwek
	$hwSolar = curl_init();
	curl_setopt($hwSolar, CURLOPT_URL, "http://".$hwKwhIP."/api/v1/data");
	curl_setopt($hwSolar, CURLOPT_RETURNTRANSFER, true);
	$hwSolarResult        = curl_exec($hwSolar);

	if (curl_errno($hwSolar)) { 
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan HomeWizard Kwh-meter gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		}
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
	exit(0);	

	} else {
	$hwSolarProductionDecode = json_decode($hwSolarResult);
	$hwSolarProduction = round($hwSolarProductionDecode->active_power_w);
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
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
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
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
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
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
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
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
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
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
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

// Schakeltijd && $hwSolarProduction == 0
	$sunrise = (date_sunrise(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLat,$gmt));
	$sunset = (date_sunset(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLong,$gmt));
	
	if ($runInfinity == 'no' && date('H:i') >= ( ''.$invStartTime.'' ) && date('H:i') <= ( ''.$invEndTime.'' )) {
		$schedule	= 1;
	} elseif ($runInfinity == 'yes' && date('H:i') >= ( ''.$sunset.'' ) && date('H:i') <= ( '23:59' )) {
		$schedule	= 1;
	} elseif ($runInfinity == 'yes' && date('H:i') >= ( '00:00' ) && date('H:i') <= ( ''.$sunset.'' )) {
		$schedule	= 1;
	} elseif ($runInfinity == 'auto' && $pvAvInputVoltage >= 24.5 && date('H:i') >= ( ''.$sunset.'' ) && date('H:i') <= ( '23:59' )) {
		$schedule	= 1;
	} elseif ($runInfinity == 'auto' && date('H:i') >= ( '00:00' ) && date('H:i') <= ( ''.$sunrise.'' )) {
		$schedule	= 1;
	} elseif ($runInfinity == 'auto' && $pvAvInputVoltage >= 24.5 && date('H:i') >= ( ''.$sunrise.'' ) && date('H:i') <= ( ''.$sunset.'' )) {
		$schedule	= 1;
	} else {
		$schedule	= 0;	
	}

// Bepaal totale oplader verbruik
	$chargerUsage = ($chargerOneUsage + $chargerTwoUsage);
	
// Bepaal de nieuwe baseload
	$productionTotal = ($hwSolarProduction + $SocketProduction);
	$realUsage = ($hwP1Usage - $productionTotal);
	
	if ($hwP1Usage < $ecoflowMaxOutput){
		$newLoad = ($hwP1Usage + $currentBaseload) - $ecoflowOutputOffSet;
		
	} elseif ($hwP1Usage >= $ecoflowMaxOutput){
		$newLoad = $ecoflowMaxOutput;
		
	}		

	if ($newLoad <= 0){
		$newBaseload = 0;
	} elseif ($newLoad > 0){
		$newBaseload = $newLoad;
	}
		
	$newInvBaseload = round($newBaseload) * 10;
	$newInfPVProduction = abs($hwSolarProduction);
	
// Vermogen op 0 indien een oplader actief is
	if ($chargerOneStatus == 'On' || $chargerTwoStatus == 'On') {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Vermogen op 0 als de batterij leeg is
	if ($batterijEmpty == 1) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Vermogen op 0 indien schakeltijd negatief is
	if ($schedule == 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Vermogen op 0 indien PV meer opwekt dan nodig	
	//if ($newInfPVProduction > $realUsage) {
	//if ($hwSolarProduction != 0) {		
	//	$newBaseload = 0;
	//	$newInvBaseload = 0;
	//}
	
// Vermogen op max indien meer nodig dan toegestaan	
	if ($newBaseload > $ecoflowMaxOutput) {
		$newBaseload = $ecoflowMaxOutput;
		$newInvBaseload = ($ecoflowMaxOutput) * 10;
	}

// Vermogen op 0 indien baseload te weinig is om de inverter te triggeren teruglevering uit de batterij te voorkomen	
	if ($newBaseload <= 25 && $hwSolarProduction != 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Vermogen op 0 als de omvormer te warm is
	if ($invTemp >= $maxInvTemp) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Print Lader Status
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

// Print Battery Status
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
		
// Print Inverter Status
		echo '-/- EcoFlow Inverter         -\-'.PHP_EOL;
		echo ' -- Temperatuur               : '.$invTemp.'c'.PHP_EOL;
		echo ' -- Output                    : '.$pvAvInputWatts.'w'.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Schakeltijd
		if ($runInfinity == 'no'){
		echo '-/- Schakeltijd              -\-'.PHP_EOL;
		echo ' -- Start Tijd                : '.$invStartTime.''.PHP_EOL;
		echo ' -- Eind Tijd                 : '.$invEndTime.''.PHP_EOL;			

		if ($schedule == 1) {
		echo ' -- Schakeltijd               : true'.PHP_EOL;
		} else {
		echo ' -- Schakeltijd               : false'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		}
		
// Print Energie Status
		echo '-/- Energie                  -\-'.PHP_EOL;
		echo ' -- P1 Verbruik               : '.$hwP1Usage.'w'.PHP_EOL;
		echo ' -- Zonnepanelen              : '.$hwSolarProduction.'w'.PHP_EOL;
		echo ' -- Batterij Opwek            : '.$SocketProduction.'w'.PHP_EOL;
		echo ' -- Echte Verbruik            : '.$realUsage.'w'.PHP_EOL;
		if ($newBaseload != 0) {
		echo ' -- Stroom vraag              : true'.PHP_EOL;
		} else {
		echo ' -- Stroom vraag              : false'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		
// Print Nieuwe Baseload
		echo '-/- Baseload                 -\-'.PHP_EOL;
		echo ' -- Huidige Baseload          : '.$currentBaseload.'w'.PHP_EOL;
		echo ' -- Nieuwe  Baseload          : '.$newBaseload.'w'.PHP_EOL;
	}
	
// Update Baseload
	if ($newBaseload != $currentBaseload) {
		if ($debug == 'yes'){
		echo ' -- Baseload update           : true'.PHP_EOL;
		}
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $newInvBaseload]);
	} else {
		if ($debug == 'yes'){
		echo ' -- Baseload update           : false'.PHP_EOL;	
		}
	}
	
// Print Einde
		if ($debug == 'yes'){
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		echo ' --              The End             --'.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;
		}
	}

?>