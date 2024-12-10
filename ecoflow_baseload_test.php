<?php
//															     //
// **************************************************************//
//          EcoFlow micro-inverter automatische baseload         //
//                          Variables                            //
// **************************************************************//
//                                                               //

// Debug?
	$debug				   = 'yes';								 // Waarde 'yes' of 'no'

// Tijd variables
	$invStartTime		   = '00:00';							 // Omvormer starttijd (bij $runInfinity == 'no')
	$invEndTime			   = '13:30';							 // Omvormer eindtijd (bij $runInfinity == 'no')
	$runInfinity		   = 'yes';								 // Waarde 'yes' of 'no' bij 'yes' zal de omvormer afhankelijk van de instellingen (zomer/wintertijd) altijd blijven opwekken

	$latitude              = '00.00000';						 // Latitude is de afstand in graden 'Noord' of 'Zuid' tot de evenaar
	$longitude             = '-0.00000';						 // Longitude is de afstand in graden 'Oost' of 'West' tot de Meridiaan in Greenwich
	$zenitLat              = '89.5';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$zenitLong             = '91.7';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$timezone              = 'Europe/Amsterdam';			     // Mijn php.ini slikt de timezone niet dus dan maar handmatig instelling
				
// Omvormer variables
	$ecoflowMaxOutput	   = 600;								 // Maximale teruglevering (Watts) wat de omvormer kan/mag leveren. 
	$ecoflowOutputOffSet   = 15;								     // Trek deze value (watts) af van de nieuwe baseload, Deze value wordt alsnog van het net wordt getrokken om teruglevering te voorkomen
	$maxInvTemp            = 65;								 // Maximale interne temperatuur, daarboven stopt de omvormer met terugleveren 

// Lader/Batterij variables
	$batteryVolt		   = 25.6;								 // Voltage van je batterij
	$batteryAh             = 200;                                // Totale Ah van alle batterijen
	$chargerEfficiency     =83.4;                                // Laad efficientie, om de daadwerkelijke beschikbare kWh uit te rekenen
	$batteryMinimum		   =  10;                                // Minimale procenten die in de batterij moeten blijven zitten
				
// Homewizard variables
	$hwP1IP				   = '0.0.0.0';					         // IP Homewizard P1 Meter
	$hwKwhIP			   = '0.0.0.0';					         // IP Homewizard Solar kwh Meter
	$hwEcoFlowIP		   = '0.0.0.0';					         // IP Homewizard EcoFlow socket
	$hwChargerOneIP 	   = '0.0.0.0';					         // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 	   = '0.0.0.0';					         // IP Homewizard Charger TWO 600w socket
	$hwChargerThreeIP 	   = '0.0.0.0';					         // IP Homewizard Charger THREE 300w socket
	
// Ecoflow Powerstream API variables
	$ecoflowPath		   = '/home/siewert/pibattery/';	     // Path waar je scripts zich bevinden
	$ecoflowAccessKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API access key
	$ecoflowSecretKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API secret key
	$ecoflowSerialNumber   = ['HWXXXXXXXXXXXX',];				 // Powerstream serie nummer

//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                  Functions & Get/Set Data                     //
// **************************************************************//
//                                                               //

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
	echo ' --       Automatische baseload      --'.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	
// Include ecoflow API class file
	include(''.$ecoflowPath.'ecoflow-api-class.php');

// php.ini
	date_default_timezone_set(''.$timezone.'');

// Time/Date now
	$timeNow = date('H:i');
	$dateNow = date('Y-m-d H:i:s');
	$dateTime = new DateTime(''.$dateNow.'', new DateTimeZone(''.$timezone.''));

// Check DSt time
	$isDST = $dateTime->format("I");
	if ($isDST == '1'){
	$gmt = '1';
	} else {
	$gmt = '0';
	}
	
// Get EcoFlow Status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$serial_number = file_get_contents(''.$ecoflowPath.'serialnumber.txt');	
	if ($serial_number === false) {
		if ($debug == 'yes'){
		echo '  -- ERROR: Can`t open file serialnumber.txt!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '  --------------------------------------'.PHP_EOL;
		}
		exit(1);
	}

	if (empty(trim($serial_number))) {
		if ($debug == 'yes'){
		echo '  -- ERROR: Battery is empty!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '  --------------------------------------'.PHP_EOL;
		}
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => 0]);
		exit(0);
	} else {
		$inv = $ecoflow->getDevice($serial_number);
		if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
		if ($debug == 'yes'){
		echo '  -- ERROR: Can`t GET EcoFlow inverter data!'.PHP_EOL;
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

// Get battery Voltage
	$pv1InputVolt		  = ($inv['data']['20_1.pv1InputVolt']) / 10;
	$pv2InputVolt		  = ($inv['data']['20_1.pv2InputVolt']) / 10;
	$pvAvInputVoltage	  = ($pv1InputVolt + $pv2InputVolt) / 2;
	
// Get Inverter output Watts
	$pv1InputWatts        = ($inv['data']['20_1.pv1InputWatts']) / 10;
	$pv2InputWatts        = ($inv['data']['20_1.pv2InputWatts']) / 10;
	$pvAvInputWatts       = ($pv1InputWatts + $pv2InputWatts);

// Get Current Baseload
	$currentBaseload	  = ($inv['data']['20_1.permanentWatts']) / 10;

// Get Inverter Temperature
	$invTemp              = ($inv['data']['20_1.llcTemp']) / 10;

// Get Battery Input/Output Total
	$batteryInputStartFile  = ''.$ecoflowPath.'batteryInput_Start.txt';
	$batteryInputEndFile    = ''.$ecoflowPath.'batteryInput_End.txt';
	$batteryOutputStartFile = ''.$ecoflowPath.'batteryOutput_Start.txt';
	$batteryOutputEndFile   = ''.$ecoflowPath.'batteryOutput_End.txt';
	
// Calculate Battery Input/Output Total
	$batteryInputStartkWh   = file_get_contents(''.$batteryInputStartFile.'');
	$batteryInputStartkWh   = round($batteryInputStartkWh, 2);
	$batteryInputEndkWh     = file_get_contents(''.$batteryInputEndFile.'');
	$batteryInputEndkWh     = round($batteryInputEndkWh, 2);
	$batteryOutputStartkWh  = file_get_contents(''.$batteryOutputStartFile.'');
	$batteryOutputStartkWh  = round($batteryOutputStartkWh, 2);
	$batteryOutputEndkWh    = file_get_contents(''.$batteryOutputEndFile.'');
	$batteryOutputEndkWh    = round($batteryOutputEndkWh, 2);
	
	$batteryTotalCharged    = round($batteryInputEndkWh - $batteryInputStartkWh, 2);
	$batteryTotalDischarged = round($batteryOutputEndkWh - $batteryOutputStartkWh, 2);
	$batteryAvailRAW        = abs($batteryTotalCharged - $batteryTotalDischarged);
	$batteryAvail           = round($batteryAvailRAW / 100 * $chargerEfficiency, 2);
	$batteryAh				= ($batteryAh / 1000);
	$batteryCapacity		= round($batteryVolt * $batteryAh, 2);
	$batterySOC				= round($batteryAvail / $batteryCapacity * 100, 1);
	
// HomeWizard GET Variables
	$hwP1Usage            = getHwData($hwP1IP);
	$hwSolarReturn        = getHwData($hwKwhIP);
	$hwInvReturn          = getHwData($hwEcoFlowIP);
	$hwChargerOneUsage    = getHwData($hwChargerOneIP);
	$hwChargerTwoUsage    = getHwData($hwChargerTwoIP);
	$hwChargerThreeUsage  = getHwData($hwChargerThreeIP);
	$ChargerOneStatus     = getHwStatus($hwChargerOneIP);
	$ChargerTwoStatus     = getHwStatus($hwChargerTwoIP);
	$ChargerThreeStatus   = getHwStatus($hwChargerThreeIP);
	
// SET/GET Usage Variables	
	$productionTotal      = ($hwSolarReturn + $hwInvReturn);
	$realUsage            = ($hwP1Usage - $productionTotal);
	$newInfPVProduction   = abs($hwSolarReturn);
	
// Schakeltijd
	$sunrise              = (date_sunrise(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLat,$gmt));
	$sunset               = (date_sunset(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLong,$gmt));

	if ($runInfinity == 'no' && date('H:i') >= ( ''.$invStartTime.'' ) && date('H:i') <= ( ''.$invEndTime.'' ) && $batterySOC >= $batteryMinimum) {
		$schedule = 1;

	} elseif ($runInfinity == 'yes' && $isDST == '1' && date('H:i') >= ( '00:00' ) && date('H:i') <= ( '23:59' ) && $batterySOC >= $batteryMinimum) {
		$schedule = 1;	
		
	} elseif ($runInfinity == 'yes' && $isDST == '0' && date('H:i') >= ( '00:00' ) && date('H:i') <= ( '12:00' ) && $batterySOC >= $batteryMinimum) {
		$schedule = 1;

	} elseif ($runInfinity == 'yes' && $isDST == '0' && date('H:i') >= ( '12:00' ) && date('H:i') <= ( ''.$sunset.'' ) && $batterySOC >= 50) {
		$schedule = 1;
		$ecoflowMaxOutput = ($ecoflowMaxOutput) / 1.8;
		
	} elseif ($runInfinity == 'yes' && $isDST == '0' && date('H:i') > ( ''.$sunset.'' ) && date('H:i') <= ( '23:59' ) && $batterySOC >= 50) {
		$schedule = 1;
		$ecoflowMaxOutput = ($ecoflowMaxOutput) / 1.8;
		
	} else {
		$schedule = 0;	
	}

// Determine total charger usage
	$chargerUsage = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage);

// Calculate remaining charge time
	if ($ChargerOneStatus == 'On' || $ChargerTwoStatus == 'On' || $ChargerThreeStatus == 'On'){
	$batteryCapacityRAW  = round($batteryCapacity / 80 * 100, 1);	
	$chargeTimeRemaining = round(($batteryCapacityRAW - $batteryAvailRAW) * 1000 / 80 * 100 / $chargerUsage, 1);		
	//$chargeTimeRemaining = round(($batteryCapacity - $batteryAvailRAW) * 1000 / 80 * 100 / $chargerUsage, 1);
	}

// Calculate remaining discharge time	
	if ($hwInvReturn < 0){
	$hwInvReturnABS = abs($hwInvReturn) / 1000 ;	
	$disChargeTimeRemaining = round($batteryAvail / $hwInvReturnABS, 1);
	}
	
// determine new baseload	
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

// Set baseload to max 	
	if ($newBaseload > $ecoflowMaxOutput) {
		$newBaseload = $ecoflowMaxOutput;
		$newInvBaseload = ($ecoflowMaxOutput) * 10;
	}
	
// Set baseload to null when charging
	if ($ChargerOneStatus == 'On' || $ChargerTwoStatus == 'On' || $ChargerThreeStatus == 'On') {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Set baseload to null when SwitchTime is negative
	if ($schedule == 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when inverter has to return less then
	if ($newBaseload <= 25 && $hwSolarReturn != 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when inverter is getting to hot
	if ($invTemp >= $maxInvTemp) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when battery empty
	if ($batterySOC < $batteryMinimum) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                    Print & Update Baseload                    //
// **************************************************************//
//                                                               //

// Print Lader Status
	if ($debug == 'yes'){
		echo ' -/- Laders                   -\-'.PHP_EOL;
		echo '  -- Lader 1                   : '.$ChargerOneStatus.''.PHP_EOL;		
		echo '  -- Lader 2                   : '.$ChargerTwoStatus.''.PHP_EOL;
		echo '  -- Lader 3                   : '.$ChargerThreeStatus.''.PHP_EOL;
		echo '  -- Laders verbruik           : '.$chargerUsage.' Watt'.PHP_EOL;
		if ($ChargerOneStatus == 'On' || $ChargerTwoStatus == 'On' || $ChargerThreeStatus == 'On'){
		echo '  -- Laad tijd tot 100%        : '.$chargeTimeRemaining.' uur'.PHP_EOL;
		}		
		echo ' '.PHP_EOL;

// Print Battery Status
		echo ' -/- Batterij @ '.$batteryCapacity.' kWh      -\-'.PHP_EOL;
		echo '  -- Batterij voltage          : '.$pvAvInputVoltage.' Volt'.PHP_EOL;
		echo '  -- Batterij SOC              : '.$batterySOC.'%'.PHP_EOL;

// Print Input/Output Total
		echo ' '.PHP_EOL;
		echo ' -/- Input/Output             -\-'.PHP_EOL;
		echo '  -- Totaal geladen            : '.$batteryTotalCharged.' kWh'.PHP_EOL;
		echo '  -- Totaal ontladen           : '.$batteryTotalDischarged.' kWh'.PHP_EOL;
		echo '  -- Opgelagen energie         : '.$batteryAvail.' kWh'.PHP_EOL;
		if ($hwInvReturn < 0){	
		echo '  -- Ontlaad tijd tot 0%       : '.$disChargeTimeRemaining.' uur @ '.$hwInvReturn.' Watt'.PHP_EOL;	
		}
		echo ' '.PHP_EOL;
		
// Print Inverter Status
		echo ' -/- EcoFlow Omvormer         -\-'.PHP_EOL;
		echo '  -- Temperatuur               : '.$invTemp.'˚C'.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Schakeltijd
		echo ' -/- Schakeltijd              -\-'.PHP_EOL;
		if ($runInfinity == 'no'){
		echo '  -- Start Tijd                : '.$invStartTime.''.PHP_EOL;
		echo '  -- Eind Tijd                 : '.$invEndTime.''.PHP_EOL;			
		}
		
		if ($schedule == 1) {
		echo '  -- Schakeltijd               : true'.PHP_EOL;
		} else {
		echo '  -- Schakeltijd               : false'.PHP_EOL;
		}
		echo '  -- $runInfinity              : '.$runInfinity.''.PHP_EOL;
		if ($isDST == '1') {
		echo '  -- Zomertijd programma       : actief'.PHP_EOL;
		} else {
		echo '  -- Wintertijd programma      : actief'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		
// Print Energie Status
		echo ' -/- Energie                  -\-'.PHP_EOL;
		echo '  -- P1-Meter                  : '.$hwP1Usage.' Watt'.PHP_EOL;
		echo '  -- Zonnepanelen opwek        : '.$hwSolarReturn.' Watt'.PHP_EOL;
		echo '  -- Batterij Opwek            : '.$hwInvReturn.' Watt'.PHP_EOL;
		echo '  -- Echte Verbruik            : '.$realUsage.' Watt'.PHP_EOL;
		if ($newBaseload != 0) {
		echo '  -- Stroom vraag              : true'.PHP_EOL;
		} else {
		echo '  -- Stroom vraag              : false'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		
// Print Nieuwe Baseload
		echo ' -/- Baseload                 -\-'.PHP_EOL;
		echo '  -- Huidige Baseload          : '.$currentBaseload.' Watt'.PHP_EOL;
		echo '  -- Nieuwe  Baseload          : '.$newBaseload.' Watt'.PHP_EOL;
	}
	
// Update Baseload
	if ($newBaseload != $currentBaseload) {
		if ($debug == 'yes'){
		echo '  -- Baseload update           : true'.PHP_EOL;
		}
		$ecoflow->setDeviceFunction($serial_number, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $newInvBaseload]);
	} else {
		if ($debug == 'yes'){
		echo '  -- Baseload update           : false'.PHP_EOL;	
		}
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