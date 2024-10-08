<?php

//															       //
// ****************************************************************//
//          EcoFlow micro-inverter automatic baseload              //
// ****************************************************************//
//                                                                 //

// Debug?
	$debug				  = 'yes';			    				   // Waarde 'yes' of 'no'.

// Tijd variables
	$invStartTime 		  = '00:00';							   // Tijd dat de omvormer mag starten met terugleveren
	$invEndTime  		  = '12:00'; 							   // Tijd dat de omvormer moet stoppen met terugleveren
	
// Omvormer variables
	$ecoflowMaxOutput 	  = 600; 				    			   // Maximale aantal output Watts wat de omvormer kan leveren. 
	$ecoflowOutputOffSet  = 10;									   // Trek deze value (watts) af van de nieuwe baseload, Deze value wordt alsnog van het net wordt getrokken om teruglevering te voorkomen
	$minBatteryVoltage 	  = 23.0; 								   // Minimale Voltage wat in de accu moet blijven
		
// Homewizard variables
	$hwP1IP 			  = 'homewizardP1IP'; 					   // IP Homewizard P1 Meter
	$hwKwhIP 			  = 'homewizardKwhMeter';     			   // IP Homewizard Solar kwh Meter
	$hwSocketIP 		  = 'homewizardEnergySocketMeter';         // IP Homewizard EnergySocket Meter (gekoppeld aand de omvormer)
	
// Domoticz variables
	$domoticzIP 		  = '192.168.178.4:8080'; 				   // IP + poort van Domoticz
	$chargerIDX 		  = '2918'; 							   // On/Off switch lader in Domoticz
	$chargerUsageIDX 	  = '2927'; 							   // Verbruik lader in Domoticz
	
// Ecoflow Powerstream API variables
	$ecoflowPath 		  = '/path/to/files/';                     // Path waar je scripts zich bevinden
	$ecoflowAccessKey	  = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	   // Powerstream API access key
	$ecoflowSecretKey	  = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	   // Powerstream API secret key
	$ecoflowSerialNumber  = ['HWXXXXXXXXXXXXXX',];				   // Powerstream serie nummer

//															       //
// ****************************************************************//
//             EcoFlow micro-inverter start script                 //
// ****************************************************************//
//                                                                 //

// Path naar ecoflow API class file
	include(''.$ecoflowPath.'ecoflow-api-class.php');

// php.ini slikt de timezone niet
	date_default_timezone_set('Europe/Amsterdam');

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
		
// Get Baseload
	$currentBaseload	  = ($inv['data']['20_1.permanentWatts']) / 10;
		
// Domoticz URLs
	$DomoticzJsonUrl 	  = 'http://'.$domoticzIP.'/json.htm?type=command&param=getdevices&rid=';
	$DomoticzJsonResult   = ['charger' => $DomoticzJsonUrl . $chargerIDX,'chargerUsage' => $DomoticzJsonUrl . $chargerUsageIDX];
		
// Get Lader status
	$charger 			  = json_decode(file_get_contents($DomoticzJsonResult['charger']), true)['result'][0]['Status'] ?? 'Off';
	
// Get huidige Lader verbruik in Domoticz
	$chargerUsage_data    = json_decode(file_get_contents($DomoticzJsonResult['chargerUsage']), true);
	$chargerUsage 		  = intval($chargerUsage_data['result'][0]['Data'] ?? 0);
		
// Get batterij Voltage in Domoticz
	$pv1InputVolt 		  = $inv['data']['20_1.pv1InputVolt'];
	$pv2InputVolt 		  = $inv['data']['20_1.pv2InputVolt'];
	$pvAvInputVoltAverage = ($pv1InputVolt + $pv2InputVolt) / 2;
	$pvAvInputVoltage 	  = ($pvAvInputVoltAverage) / 10;

// Get EcoFlow input Watts
	$pv1InputWatts 		  = $inv['data']['20_1.pv1InputWatts'];
	$pv2InputWatts 		  = $inv['data']['20_1.pv2InputWatts'];
	$pvAvInputWattsAverage= ($pv1InputWatts + $pv2InputWatts);
	$pvAvInputWatts		  = ($pvAvInputWattsAverage) / 10;
	
// Get HomeWizard P1 verbruik
	$hwP1 = curl_init();
	curl_setopt($hwP1, CURLOPT_URL, "http://".$hwP1IP."/api/v1/data");
	curl_setopt($hwP1, CURLOPT_RETURNTRANSFER, true);
	$hwP1result = curl_exec($hwP1);

	if (curl_errno($hwP1)) { echo curl_error($hwP1); }
	else {
	$hwP1UsageDecode    = json_decode($hwP1result);
	$hwP1Usage 	      = round($hwP1UsageDecode->active_power_w);
	}

// Get HomeWizard PV (kwh meter) opwek
	$hwSolar = curl_init();
	curl_setopt($hwSolar, CURLOPT_URL, "http://".$hwKwhIP."/api/v1/data");
	curl_setopt($hwSolar, CURLOPT_RETURNTRANSFER, true);
	$hwSolarresult = curl_exec($hwSolar);

	if (curl_errno($hwSolar)) { echo curl_error($hwSolar); }
	else {
	$hwSolarProductionDecode = json_decode($hwSolarresult);
	$PVProduction = round($hwSolarProductionDecode->active_power_w);
	}
		
// Get HomeWizard Socket opwek
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
	}
	
	if (json_last_error() === JSON_ERROR_NONE) {

// Bepaal de nieuwe baseload
	$productionTotal	= ($PVProduction + $SocketProduction);
	$realUsage			= ($hwP1Usage - $productionTotal);
	
	if ($hwP1Usage <= $ecoflowMaxOutput){
		$newLoad = ($hwP1Usage + $currentBaseload) - $ecoflowOutputOffSet;
		
	} elseif ($hwP1Usage > $ecoflowMaxOutput){
		$newLoad = 600;
		
	}		

	if ($newLoad <= 0){
		$newBaseload = 0;
	} elseif ($newLoad > 0){
		$newBaseload = $newLoad;
	}
		
	$newInvBaseload 	= round($newBaseload) * 10;
	
// Schakeltijd
	$newInfPVProduction	= abs($PVProduction);
	if (date('H:i') >= ( ''.$invStartTime.'' ) && date('H:i') <= ( ''.$invEndTime.'' ) && $newInfPVProduction <= $realUsage) {
		$schedule = 1;
	} else {
		$schedule = 0;
	}
	
// Vermogen op 0 indien oplader actief is
	if ($charger == 'On') {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Vermogen op 0 als de batterij leeg is
	if ($pvAvInputVoltage <= $minBatteryVoltage) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Vermogen op 0 indien schakeltijd negatief is
	if ($schedule == 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Print Begin
	if ($debug == 'yes'){
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		echo ' --  Powerstream micro-inverter  --'.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Batterij Status
		echo '-/- Batterij          -\-'.PHP_EOL;
		echo ' -- Batterij Voltage   : '.$pvAvInputVoltage.'v'.PHP_EOL;
		echo ' '.PHP_EOL;
		
// Print Lader Status
		echo '-/- Lader             -\-'.PHP_EOL;
		echo ' -- Lader              : '.$charger.''.PHP_EOL;
		echo ' -- Lader Verbruik     : '.$chargerUsage.'w'.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Schakeltijd
	if ($debug == 'yes'){
		echo '-/- Schakeltijd       -\-'.PHP_EOL;
		echo ' -- Start Tijd         : '.$invStartTime.''.PHP_EOL;
		echo ' -- Eind Tijd          : '.$invEndTime.''.PHP_EOL;
		if ($schedule == 1) {
		echo ' -- Schakeltijd        : true'.PHP_EOL;
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
		echo ' '.PHP_EOL;
		if ($newBaseload != 0) {
		echo ' -- Stroom vraag       : true'.PHP_EOL;
		} else {
		echo ' -- Stroom vraag       : false'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
			
// Print Nieuwe Baseload
		
		echo '-/- EcoFlow Inverter  -\-'.PHP_EOL;
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