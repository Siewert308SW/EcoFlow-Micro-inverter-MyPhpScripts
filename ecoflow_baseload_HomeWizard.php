<?php

//															      //
// ***************************************************************//
//          EcoFlow micro-inverter automatic baseload             //
// ***************************************************************//
//                                                                //

// Debug?
	$debug				   = 'yes';			    				  // Waarde 'yes' of 'no'.

// Tijd variables
	$invStartTime 		   = '00:00';							  // Tijd dat de omvormer mag starten met terugleveren
	$invEndTime  		   = '13:00'; 							  // Tijd dat de omvormer moet stoppen met terugleveren
	$dynamicEndTime		   = 'yes';								  // Waarde 'yes' of 'no', bij yes is sunset de eindtijd

	$latitude              = '00.00000';						  // Latitude is de afstand – noord of zuid – tot de evenaar
	$longitude             = '-0.00000';						  // Longitude is de afstand in graden oost of west tot de Meridiaan in Greenwich
	$zenitLat              = '89.5';							  // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$zenitLong             = '91.7';							  // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$gmt                   = '1';								  // GMT time '1' = true - '0'= false voor UTC
	$timezone              = 'Europe/Amsterdam';			      // Mijn php.ini slikt de timezone niet dus dan maar handmatig instelling
	
// Omvormer variables
	$ecoflowMaxOutput 	   = 600; 				    			  // Maximale aantal output Watts wat de omvormer kan leveren. 
	$ecoflowOutputOffSet   = 10;  							      // Trek deze value (watts) af van de nieuwe baseload, Deze value wordt alsnog van het net wordt getrokken om teruglevering te voorkomen
	$minBatteryVoltage 	   = 23.0; 						          // Minimale Voltage wat in de accu moet blijven
			
// Homewizard variables
	$hwP1IP 			   = 'Homewizard_IP'; 					  // IP Homewizard P1 Meter
	$hwKwhIP 			   = 'Homewizard_IP';     				  // IP Homewizard Solar kwh Meter
	$hwChargerIP 		   = 'Homewizard_IP';                     // IP Homewizard Charger socket
	$hwSocketIP 		   = 'Homewizard_IP';     				  // IP Homewizard EcoFlow socket
		
// Ecoflow Powerstream API variables
	$ecoflowPath 		   = '/path/to/files/';                   // Path waar je scripts zich bevinden
	$ecoflowAccessKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';  // Powerstream API access key
	$ecoflowSecretKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';  // Powerstream API secret key
	$ecoflowSerialNumber   = ['HWXXXXXXXXXXXXXX',];			      // Powerstream serie nummer

//															      //
// ***************************************************************//
//             EcoFlow micro-inverter start script                //
// ***************************************************************//
//                                                                //

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
		echo ' ----------------------------------'.PHP_EOL;
		}
	exit(1);
	}

	if (empty(trim($serial_number))) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Accus is leeg!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		}
		foreach ($ecoflowSerialNumber as $sn) {
			$ecoflow->setDeviceFunction($sn, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
		}
	exit(0);
	} else {
		$inv = $ecoflow->getDevice($serial_number);
		if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan gegevens van de omvormer niet ophalen!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' ----------------------------------'.PHP_EOL;
			}
	exit(1);
	}
			
// Get batterij Voltage
	$pv1InputVolt 		  = ($inv['data']['20_1.pv1InputVolt']) / 10;
	$pv2InputVolt 		  = ($inv['data']['20_1.pv2InputVolt']) / 10;
	$pvAvInputVoltage     = ($pv1InputVolt + $pv2InputVolt) / 2;
	
// Get PV input Watts
	$pv1InputWatts        = ($inv['data']['20_1.pv1InputWatts']) / 10;
	$pv2InputWatts        = ($inv['data']['20_1.pv2InputWatts']) / 10;
	$pvAvInputWatts       = ($pv1InputWatts + $pv2InputWatts) / 2;

// Get EcoFlow temp
	$pvTemp               = ($inv['data']['20_1.llcTemp']) /10;
	
// Get Baseload
	$currentBaseload      = ($inv['data']['20_1.permanentWatts']) / 10;
	
// Batterij leeg?	
	if ($pvAvInputVoltage <= $minBatteryVoltage) {
	$batterijEmpty = 1;
	} elseif ($pvAvInputVoltage >= 0 && $pvAvInputVoltage <= 23.7 && $pvAvInputWatts == 0) {
	$batterijEmpty = 1;
	} else {
	$batterijEmpty = 0;
	}
	
// Get HomeWizard P1 verbruik
	$hwP1 = curl_init();
	curl_setopt($hwP1, CURLOPT_URL, "http://".$hwP1IP."/api/v1/data");
	curl_setopt($hwP1, CURLOPT_RETURNTRANSFER, true);
	$hwP1result = curl_exec($hwP1);

	if (curl_errno($hwP1)) { echo curl_error($hwP1); }
	else {
	$hwP1UsageDecode = json_decode($hwP1result);
	$hwP1Usage = round($hwP1UsageDecode->active_power_w);
	curl_close($hwP1);	
	}

// Get HomeWizard PV (kwh meter) opwek
	$hwSolar = curl_init();
	curl_setopt($hwSolar, CURLOPT_URL, "http://".$hwKwhIP."/api/v1/data");
	curl_setopt($hwSolar, CURLOPT_RETURNTRANSFER, true);
	$hwSolarresult        = curl_exec($hwSolar);

	if (curl_errno($hwSolar)) { echo curl_error($hwSolar); }
	else {
	$hwSolarProductionDecode = json_decode($hwSolarresult);
	$PVProduction = round($hwSolarProductionDecode->active_power_w);
	curl_close($hwSolar);	
	}
		
// Get HomeWizard Batterij (energy-socket) opwek
	$hwSocket = curl_init();
	curl_setopt($hwSocket, CURLOPT_URL, "http://".$hwSocketIP."/api/v1/data");
	curl_setopt($hwSocket, CURLOPT_RETURNTRANSFER, true);
	$hwSocketresult = curl_exec($hwSocket);

	if (curl_errno($hwSocket)) { echo curl_error($hwSocket); }
	else {
    $hwSocketProductionDecode = json_decode($hwSocketresult);
	$SocketInvProduction = round($hwSocketProductionDecode->active_power_w);
	
	if ($SocketInvProduction > 0) {
	$SocketProduction = 0;
	} else {
	$SocketProduction = ($SocketInvProduction);
	}
	curl_close($hwSocket);
	}
	
// Get HomeWizard Charger (energy-socket) status
	$hwChargerStatus = curl_init();
	curl_setopt($hwChargerStatus, CURLOPT_URL, "http://".$hwChargerIP."/api/v1/state");
	curl_setopt($hwChargerStatus, CURLOPT_RETURNTRANSFER, true);
	$hwChargerStatusResult = curl_exec($hwChargerStatus);

	if (curl_errno($hwChargerStatus)) { echo curl_error($hwChargerStatus); }
	else {
	$hwChargerStatusDecode = json_decode($hwChargerStatusResult);
	$chargerStatus = abs($hwChargerStatusDecode->power_on);
	  
	if ($chargerStatus == 1){
	$chargerStatus = 'On';
	} else {
	$chargerStatus = 'Off';  
	}
	curl_close($hwChargerStatus);	  
	}
	
// Get HomeWizard Charger (energy-socket) verbruik
	$hwCharger = curl_init();
	curl_setopt($hwCharger, CURLOPT_URL, "http://".$hwChargerIP."/api/v1/data");
	curl_setopt($hwCharger, CURLOPT_RETURNTRANSFER, true);
	$hwChargerResult = curl_exec($hwCharger);

	if (curl_errno($hwCharger)) { echo curl_error($hwCharger); }
	else {
	$hwChargerDecode = json_decode($hwChargerResult);
	$chargerUsage = round($hwChargerDecode->active_power_w);
	curl_close($hwCharger);	
	}
	
	if (json_last_error() === JSON_ERROR_NONE) {

// Bepaal de nieuwe baseload
	$productionTotal = ($PVProduction + $SocketProduction);
	$realUsage = ($hwP1Usage - $productionTotal);
	
	if ($hwP1Usage < $ecoflowMaxOutput){
		$newLoad = ($hwP1Usage + $currentBaseload) - $ecoflowOutputOffSet;
		
	} elseif ($hwP1Usage >= $ecoflowMaxOutput){
		$newLoad = ($ecoflowMaxOutput) - $ecoflowOutputOffSet;
	}		

	if ($newLoad <= 0){
		$newBaseload = 0;
	} elseif ($newLoad > 0){
		$newBaseload = $newLoad;
	}
		
	$newInvBaseload = round($newBaseload) * 10;
	$newInfPVProduction = abs($PVProduction) + $ecoflowOutputOffSet;
	
// Schakeltijd
	$sunrise = (date_sunrise(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLat,$gmt));
	$sunset = (date_sunset(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLong,$gmt));
		
	if (date('H:i') >= ( ''.$invStartTime.'' ) && date('H:i') < ( ''.$sunrise.'' ) && $dynamicEndTime == 'yes') {
		$schedule = 1;
		$startTime = ($invStartTime);
		$endTime = ($sunrise);
		
	} elseif (date('H:i') >= ( ''.$sunrise.'' ) && date('H:i') <= ( ''.$invEndTime.'' ) && $dynamicEndTime == 'yes') {
		$schedule = 1;
		$startTime= ($invStartTime);
		$endTime = ($invEndTime);
		
	} elseif (date('H:i') >= ( ''.$invStartTime.'' ) && date('H:i') <= ( ''.$invEndTime.'' ) && $dynamicEndTime == 'no') {
		$schedule = 1;
		$startTime = ($invStartTime);
		$endTime = ($invEndTime);
		
	} else {
		$schedule = 0;
		$startTime= ($invStartTime);
		$endTime = ($invEndTime);	
	}

// Vermogen op 0 indien oplader actief is
	if ($chargerStatus == 'On') {
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
	if ($newInfPVProduction > $realUsage) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Vermogen op max indien meer nodig dan toegestaan	
	if ($newBaseload > $ecoflowMaxOutput) {
		$newBaseload = $ecoflowMaxOutput;
		$newInvBaseload = ($ecoflowMaxOutput) * 10;
	}
	
// Print Begin
	if ($debug == 'yes'){
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		echo ' --    EcoFlow Micro-Inverter    --'.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Inverter Status
		echo '-/- EcoFlow Inverter  -\-'.PHP_EOL;
		echo ' -- Temperatuur        : '.$pvTemp.'c'.PHP_EOL;
		echo ' '.PHP_EOL;
		
// Print Battery Status
		echo '-/- Batterij Status   -\-'.PHP_EOL;
		echo ' -- Voltage            : '.$pvAvInputVoltage.'v'.PHP_EOL;
		if ($batterijEmpty == 1) {
		echo ' -- Batterij leeg!'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		
// Print Lader Status
		echo '-/- Lader Status      -\-'.PHP_EOL;
		echo ' -- Lader              : '.$chargerStatus.''.PHP_EOL;
		echo ' -- Lader Verbruik     : '.$chargerUsage.'w'.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Schakeltijd
	if ($debug == 'yes'){
		echo '-/- Schakeltijd       -\-'.PHP_EOL;

		if ($dynamicEndTime == 'yes') {
		echo ' -- Start Tijd         : '.$startTime.''.PHP_EOL;
		echo ' -- Eind Tijd          : '.$endTime.''.PHP_EOL;			
		} else {
		echo ' -- Start Tijd         : '.$startTime.''.PHP_EOL;
		echo ' -- Eind Tijd          : '.$endTime.''.PHP_EOL;			
		}

		if ($schedule == 1) {
			if ($dynamicEndTime == 'yes') {
			echo ' -- Schakeltijd        : true (Dynamisch)'.PHP_EOL;
			} elseif ($dynamicEndTime == 'no') {
			echo ' -- Schakeltijd        : true'.PHP_EOL;
			}
			
		} else {
		echo ' -- Schakeltijd        : false'.PHP_EOL;
		}
		echo ' '.PHP_EOL;		
		}
		
// Print Energie Status
		echo '-/- Energie           -\-'.PHP_EOL;
		echo ' -- P1 Verbruik        : '.$hwP1Usage.'w'.PHP_EOL;
		echo ' -- PV Opwek           : '.$PVProduction.'w'.PHP_EOL;
		echo ' -- Batterij Opwek     : '.$SocketProduction.'w'.PHP_EOL;
		echo ' -- Echte Verbruik     : '.$realUsage.'w'.PHP_EOL;
		if ($newBaseload != 0) {
		echo ' -- Stroom vraag       : true'.PHP_EOL;
		} else {
		echo ' -- Stroom vraag       : false'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		
// Print Nieuwe Baseload
		echo '-/- Baseload          -\-'.PHP_EOL;
		echo ' -- Huidige Baseload   : '.$currentBaseload.'w'.PHP_EOL;
		echo ' -- Nieuwe  Baseload   : '.$newBaseload.'w'.PHP_EOL;
	}
	
// Update Baseload
	if ($newBaseload != $currentBaseload) {
		if ($debug == 'yes'){
		echo ' -- Baseload update    : true'.PHP_EOL;
		}
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $newInvBaseload]);
	} else {
		if ($debug == 'yes'){
		echo ' -- Baseload update    : false'.PHP_EOL;	
		}
	}
	
// Print Einde
		if ($debug == 'yes'){
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		echo ' --           The End            --'.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;
		}
	}
}
?>