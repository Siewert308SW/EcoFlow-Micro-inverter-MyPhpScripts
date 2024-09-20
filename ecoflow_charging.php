<?php
//															 //
// **********************************************************//
//     PowerQueen LiFepo4 20A Charger automatic charging     //
// **********************************************************//
//                                                           //

// Debug?
$debug					= 'yes';								 // Waarde 'yes' of 'no'.

// Homewizard variables
$hwP1IP 				= 'homewizardP1IP'; 			     	 // IP Homewizard P1 Meter

// Domoticz variables
$domoticzIP			    = 'domoticzIP:port'; 	    		     // IP + poort van Domoticz
$chargerIDX 			= 'idx'; 								 // On/Off switch lader in Domoticz
$chargerWattsIDX 		= 'idx'; 								 // watts-meter tbv oplader in Domoticz
$quookerWattsIDX 		= 'idx'; 								 // watts-meter tbv Quooker in Domoticz
$counter_1WattsIDX 		= 'idx'; 								 // watts-meter tbv KoffieAutomaat in Domoticz
$counter_2WattsIDX 		= 'idx';
$bedroomWattsIDX 		= 'idx'; 								 // watts-meter tbv slaapkamer in Domoticz
$voltageIDX 			= 'idx'; 								 // Voltage-meter in Domoticz

// Lader/Batterij variables
$minBatteryVoltage 		= 23.1; 								 // Minimale Voltage wat in de accu moet blijven.
$maxPowerReturn			= -600;									 // Minimale Wattage teruglevering wanneer de lader moet starten
$maxPowerUsage			= 250;									 // Maximale Wattage verbruik wanneer de lader moet stoppen

// Ecoflow Powerstream API variables
$ecoflowPath 			= '/path/to/files/';                     // Path waar de scripts zich bevinden
$ecoflowAccessKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	 // Powerstream API access key
$ecoflowSecretKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	 // Powerstream API secret key
$ecoflowSerialNumber 	= ['HWXXXXXXXXXXXXXX',];				 // Powerstream serie nummer

//															 //
// **********************************************************//
//       PowerQueen LiFepo4 20A charging start script        //
// **********************************************************//
//                                                           //


	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
	echo ' --   PowerQueen auto charging   --'.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	
	require_once(''.$ecoflowPath.'ecoflow-api-class.php');

// Get Ecoflow status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$ecoflowSerialNumber = file_get_contents(''.$ecoflowPath.'serialnumber.txt');
		$batterijEmpty = 0;
	if ($ecoflowSerialNumber === false) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan het serialnumber.txt bestand niet openen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		}
		exit(1);
	}

	if (empty(trim($ecoflowSerialNumber))) {
		if ($debug == 'yes'){
		echo ' -- ERROR: Accus is leeg!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		}
			$batterijEmpty = 1;
	} else {
		
		$inv = $ecoflow->getDevice($ecoflowSerialNumber);
		if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan gegevens van de omvormer niet ophalen!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' ----------------------------------'.PHP_EOL;
			}
			exit(1);
		}


// Function switch device
	function SwitchDevice($idx,$cmd) {
	  global $domoticzIP;
	  $reply=json_decode(file_get_contents('http://'.$domoticzIP.'/json.htm?type=command&param=switchlight&idx='.$idx.'&switchcmd='.$cmd),true);
	  if($reply['status']=='OK') $reply='OK';else $reply='ERROR';
	  return $reply;
	}

// Function Update Domoticz device
	function UpdateDevice($idx,$cmd) {
	  global $domoticzIP;
	  $reply=json_decode(file_get_contents('http://'.$domoticzIP.'/json.htm?type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$cmd),true);
	  if($reply['status']=='OK') $reply='OK';else $reply='ERROR';
	  return $reply;
	}
	
// URLs
	$baseUrl = 'http://'.$domoticzIP.'/json.htm?type=command&param=getdevices&rid=';
	$urls = [
		'charger'  		     => $baseUrl . $chargerIDX,
		'chargerWatts'       => $baseUrl . $chargerWattsIDX,
		'quookerWatts'       => $baseUrl . $quookerWattsIDX,
		'counter_1Watts'     => $baseUrl . $counter_1WattsIDX,
		'counter_2Watts'     => $baseUrl . $counter_2WattsIDX,
		'bedroomWatts'       => $baseUrl . $bedroomWattsIDX,
		'voltage'  		     => $baseUrl . $voltageIDX
	];
	
// Get Lader status
	$charger = json_decode(file_get_contents($urls['charger']), true)['result'][0]['Status'] ?? 'ERROR';
	if ($charger == 'ERROR'){
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan gegevens van Domoticz niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		}
		exit(1);
	}
	
// Get batterij Voltage
	$pv1InputVolt 		  = $inv['data']['20_1.pv1InputVolt'];
	$pv2InputVolt 		  = $inv['data']['20_1.pv2InputVolt'];
	$pvAvInputVoltAverage = ($pv1InputVolt + $pv2InputVolt) / 2;
	$pvAvInputVoltRounded = ($pvAvInputVoltAverage) / 10;
	$pvAvInputVoltage     = $pvAvInputVoltRounded;


// Batterij leeg?	
	if ($pvAvInputVoltage <= $minBatteryVoltage) {
	$batterijEmpty = 1;
	} else {
	$batterijEmpty = 0;
	}

// Get huidige Domoticz voltage dummy
	$voltage_data 	      = json_decode(file_get_contents($urls['voltage']), true);
	$voltage 			  = $voltage_data['result'][0]['Voltage'];

// Update Domoticz voltage device
	if ($voltage != $pvAvInputVoltage) {
		UpdateDevice($voltageIDX, ''.$pvAvInputVoltage.'');
	}
	
// Get PV input Watts
	$pv1InputWatts 		  = ($inv['data']['20_1.pv1InputWatts']) / 10;
	$pv2InputWatts 		  = ($inv['data']['20_1.pv2InputWatts']) / 10;
	$pvInputTotalWatts    = ($pv1InputWatts + $pv2InputWatts);

// Get P1 verbruik en teruglevering
	$hwP1 = curl_init();
	curl_setopt($hwP1, CURLOPT_URL, "http://".$hwP1IP."/api/v1/data");
	curl_setopt($hwP1, CURLOPT_RETURNTRANSFER, true);
	$hwP1result = curl_exec($hwP1);

	if (curl_errno($hwP1)) { echo curl_error($hwP1); }
	else {
	  $hwP1UsageDecode = json_decode($hwP1result);
	  $hwP1UsageProp = round($hwP1UsageDecode->active_power_w);
	  $P1Usage = round($hwP1UsageProp);
	}
	
// Get huidige Lader verbruik in Domoticz
	$chargerWatts_data    = json_decode(file_get_contents($urls['chargerWatts']), true);
	$chargerWatts 		  = intval($chargerWatts_data['result'][0]['Data'] ?? 0);

// Get Huidige Baseload
	$currentBaseload = ($inv['data']['20_1.permanentWatts']) / 10;

// Bepaal verbruik grootverbruiker
	$quookerWatts_data    = json_decode(file_get_contents($urls['quookerWatts']), true);
	$quookerWatts 		  = intval($quookerWatts_data['result'][0]['Data'] ?? 0);

	$counter_1Watts_data  = json_decode(file_get_contents($urls['counter_1Watts']), true);
	$counter_1Watts 	  = intval($counter_1Watts_data['result'][0]['Data'] ?? 0);

	$counter_2Watts_data  = json_decode(file_get_contents($urls['counter_2Watts']), true);
	$counter_2Watts 	  = intval($counter_2Watts_data['result'][0]['Data'] ?? 0);
	
	$bedroomWatts_data 	  = json_decode(file_get_contents($urls['bedroomWatts']), true);
	$bedroomWatts 	  	  = intval($bedroomWatts_data['result'][0]['Data'] ?? 0);

// Bepaal powerBreach
	if ($P1Usage >= $maxPowerUsage && $quookerWatts <= 5 && $counter_1Watts <= 50 && $counter_2Watts <= 50 && $bedroomWatts <= 1000) {
		$powerBreach = 1;
	} elseif ($P1Usage >= $maxPowerUsage || $quookerWatts > 5 || $counter_1Watts > 50 || $counter_2Watts > 50 || $bedroomWatts > 1000) {
		$powerBreach = 0;
	} elseif ($P1Usage < $maxPowerUsage) {
		$powerBreach = 0;
	}

////////////////////////////////////////////////////////////////////////


// Print 
	if ($debug == 'yes'){
		echo '-/- Lader                    -\-'.PHP_EOL;
		echo ' -- Lader Status              : '.$charger.''.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '-/- Batterij                 -\-'.PHP_EOL;
		echo ' -- Batterij Voltage          : '.$pvAvInputVoltage.'v'.PHP_EOL;
		if ($batterijEmpty == 1) {
		echo ' -- Batterij leeg!'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		echo '-/- Power Breach             -\-'.PHP_EOL;
		echo ' -- Breached                  : '.$powerBreach.''.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '-/- EcoFlow Inverter         -\-'.PHP_EOL;
		echo ' -- Baseload                  : '.$currentBaseload.'w'.PHP_EOL;
		echo ' -- Output                    : '.$pvInputTotalWatts.'w'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '-/- P1 Meter                 -\-'.PHP_EOL;
		if ($P1Usage >= 0){
		echo ' -- Verbruik                  : '.$P1Usage.'w'.PHP_EOL;
		} else {
		echo ' -- Teruglevering             : '.$P1Usage.'w'.PHP_EOL;		
		}
	}
	
//
// **********************************************************
// Control PowerQueen LiFepo4 20A automatic charging
// **********************************************************
//

// Lader AAN bij genoeg zonnestroom en Batt% onder x.x%
	if ($charger == 'Off' && $P1Usage <= $maxPowerReturn && $currentBaseload == 0 && $pvAvInputVoltage <= 26.1 && $pvInputTotalWatts == 0 && $powerBreach == 0) {
		if ($debug == 'yes'){
		echo ' -- Lader ingeschakeld'.PHP_EOL;
		}
		switchDevice($chargerIDX, 'On');

// Lader UIT wanneer er niet genoeg stroom word opgewekt door de PV en lader is aan het laden
	} elseif ($charger == 'On' && $P1Usage >= $maxPowerUsage && $powerBreach == 1) {
		if ($debug == 'yes'){
		echo ' -- Lader uitgeschakeld, geen zonnestroom genoeg!'.PHP_EOL;
		}
		switchDevice($chargerIDX, 'Off');
		
// Lader UIT wanneer batterij vol is
	//} elseif ($charger == 'On' && $pvAvInputVoltage >= 26.65 && $chargerWatts <= 5.6) {
	//	if ($debug == 'yes'){
	//	echo ' -- Lader uitgeschakeld, Batterij vol!'.PHP_EOL;
	//	}
	//	switchDevice($chargerIDX, 'Off');

	} else {
		
		if ($charger == 'On') {
		if ($debug == 'yes'){		
			echo ' -- Batterij wordt opgeladen'.PHP_EOL;
		}	

		} elseif ($charger == 'Off') {
			if ($debug == 'yes'){
			echo ' -- Batterij wordt niet opgeladen'.PHP_EOL;
			}
		}
	}

// Print
	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
	echo ' --           The End            --'.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	}
?>