<?php
//															     //
// **************************************************************//
//          EcoFlow LiFePo4 10/20A Thuisbatterij Laders          //
//                          Variables                            //
// **************************************************************//
//                                                               //

// Debug?
	$debug				   = 'yes';							     // Waarde 'yes' of 'no'

// Tijd variables
	$latitude              = '00.00000';						 // Latitude is de afstand – noord of zuid – tot de evenaar
	$longitude             = '-0.00000';						 // Longitude is de afstand in graden oost of west tot de Meridiaan in Greenwich
	$zenitLat              = '89.5';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$zenitLong             = '91.7';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$timezone              = 'Europe/Amsterdam';			     // Mijn php.ini slikt de timezone niet dus dan maar handmatig instelling
	
// Homewizard variables
	$hwP1IP				   = '0.0.0.0';					 	     // IP Homewizard P1 Meter
	$hwKwhIP			   = '0.0.0.0';					         // IP Homewizard Solar kwh Meter
	$hwEcoFlowIP		   = '0.0.0.0';					         // IP Homewizard EcoFlow socket
	$hwChargerOneIP 	   = '0.0.0.0';     			         // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 	   = '0.0.0.0';     			         // IP Homewizard Charger TWO 600w socket
	
// Lader/Batterij variables
	$minPowerOneReturn	   = -300;								 // Minimale teruglevering (Watt) wanneer de lader 1 mag starten
	$minPowerTwoReturn	   = -600;								 // Minimale teruglevering (Watt) wanneer de lader 2 mag starten
	$minSolarReturn		   = -1200;							     // Minimale teruglevering (Watt), hierboven zal het laadscript alle variablen volgen, hierboven zal ongeacht het verbruik alle laders ingeschakeld houden 
	
// Ecoflow Powerstream API variables
	$ecoflowPath		   = '/path/to/files/';	                 // Path waar je scripts zich bevinden
	$ecoflowAccessKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API access key
	$ecoflowSecretKey	   = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx'; // Powerstream API secret key
	$ecoflowSerialNumber   = ['HWXXXXXXXXXXX',];				 // Powerstream serie nummer

//															     //
// **************************************************************//
//          EcoFlow LiFePo4 10/20A Thuisbatterij opladen         //
//                  Functions & Get/Set Data                     //
// **************************************************************//
//                                                               //

// Print Header
	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' --   LiFePo4 10/20A Thuisbatterij   --'.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	
// Require ecoflow API class file	
	require_once(''.$ecoflowPath.'ecoflow-api-class.php');

// php.ini
	date_default_timezone_set(''.$timezone.'');

// Time/Date now
	$timeNow  = date('H:i');
	$dateNow  = date('Y-m-d H:i:s');
	$dateTime = new DateTime(''.$dateNow.'', new DateTimeZone(''.$timezone.''));
	
// Check DST time
	$isDST = $dateTime->format("I");
	
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
		global $hwEcoFlowIP;
		
		$socket = curl_init();
		if ($energySocket == 'two') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerTwoIP.'/api/v1/state');			
		} elseif ($energySocket == 'one') {
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

// HomeWizard SET/GET Variables
	$hwP1Usage            = getHwData($hwP1IP);
	$hwSolarReturn        = getHwData($hwKwhIP);
	$hwInvReturn          = getHwData($hwEcoFlowIP);
	$hwChargerOneUsage    = getHwData($hwChargerOneIP);
	$hwChargerTwoUsage    = getHwData($hwChargerTwoIP);
	$ChargerOneStatus     = getHwStatus($hwChargerOneIP);
	$ChargerTwoStatus     = getHwStatus($hwChargerTwoIP);
	
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
	$chargerUsage         = ($hwChargerOneUsage + $hwChargerTwoUsage);
	$productionTotal      = ($hwSolarReturn + $hwInvReturn);
	$realUsage            = ($hwP1Usage - $productionTotal);
	$minPowerTotalReturn  = ($minPowerOneReturn + $minPowerTwoReturn);
	$maxPowerOneReturn	  = abs($minPowerOneReturn);
	$maxPowerTwoReturn	  = abs($minPowerTwoReturn);
	$P1ChargerUsage       = ($hwP1Usage - $chargerUsage);

//Write battery State	
	if ($pvAvInputVoltage <= 22.7) {
	writeBattState('ontladen');
	} elseif ($pvAvInputVoltage >= 26.0) {
	writeBattState('opgeladen');
	}
	$batteryState = file_get_contents(''.$ecoflowPath.'batteryState.txt');
	
//															     //
// **************************************************************//
//          EcoFlow LiFePo4 10/20A Thuisbatterij Laders          //
//                             Print                             //
// **************************************************************//
//                                                               //

	if ($debug == 'yes'){
		echo ' -/- Laders                   -\-'.PHP_EOL;
		echo '  -- Lader 1                   : '.$ChargerOneStatus.''.PHP_EOL;		
		echo '  -- Lader 2                   : '.$ChargerTwoStatus.''.PHP_EOL;
		echo '  -- Laders Totaal-Verbruik    : '.$chargerUsage.'w'.PHP_EOL;		
		echo ' '.PHP_EOL;
		
		echo ' -/- Batterij                 -\-'.PHP_EOL;
		echo '  -- Batterij Voltage          : '.$pvAvInputVoltage.'v'.PHP_EOL;
		echo '  -- Batterij State            : '.$batteryState.''.PHP_EOL;
		if ($batteryState != 'opgeladen' && $isDST == '0'){
		echo '  -- Geen ontlading vandaag...'.PHP_EOL;
		}
		echo ' '.PHP_EOL;

		echo ' -/- EcoFlow Omvormer         -\-'.PHP_EOL;
		echo '  -- Temperatuur               : '.$invTemp.'c'.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Schakeltijd
		echo ' -/- Schakeltijd              -\-'.PHP_EOL;
		if ($isDST == '1') {
		echo '  -- Zomertijd programma       : actief'.PHP_EOL;
		} else {
		echo '  -- Wintertijd programma      : actief'.PHP_EOL;	
		}
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
//          EcoFlow LiFePo4 10/20A Thuisbatterij Laders          //
//                        Start/Stop Laden                       //
// **************************************************************//
//                                                               //

	if ($debug == 'yes'){		
		echo ' '.PHP_EOL;
		echo ' --------------------------------------'.PHP_EOL;
	}

	if ($P1ChargerUsage > $minPowerOneReturn || $hwSolarReturn >= $minPowerOneReturn || $chargerUsage <= 14 || $pvAvInputWatts != 0){

		if ($ChargerOneStatus == 'On' && $hwSolarReturn >= $minSolarReturn){
		switchHwSocket('one','Off');
		if ($debug == 'yes'){ echo ' -- Lader 1 uitgeschakeld'.PHP_EOL; }
		sleep(5);
		}
		
		if ($ChargerTwoStatus == 'On' && $hwSolarReturn >= $minSolarReturn){
		switchHwSocket('two','Off');
		if ($debug == 'yes'){ echo ' -- Lader 2 uitgeschakeld'.PHP_EOL; }
		}
		
	}

	if (($P1ChargerUsage > $minPowerTwoReturn && $P1ChargerUsage <= $minPowerOneReturn) && ($pvAvInputVoltage < 26 && $pvAvInputWatts == 0 && $hwSolarReturn <= $minPowerOneReturn)){
	
		if ($ChargerOneStatus == 'Off'){
		switchHwSocket('one','On');
		if ($debug == 'yes'){ echo ' -- Lader 1 ingeschakeld'.PHP_EOL; }
		sleep(5);
		}
		
		if ($ChargerTwoStatus == 'On' && $hwSolarReturn >= $minSolarReturn){
		switchHwSocket('two','Off');
		if ($debug == 'yes'){ echo ' -- Lader 2 uitgeschakeld'.PHP_EOL; }
		}
		
	}

	if (($P1ChargerUsage > $minPowerTotalReturn && $P1ChargerUsage <= $minPowerTwoReturn) && ($pvAvInputVoltage < 26 && $pvAvInputWatts == 0 && $hwSolarReturn <= $minPowerOneReturn)){

		if ($ChargerTwoStatus == 'Off' && $hwSolarReturn >= $minSolarReturn){
		switchHwSocket('two','On');
		if ($debug == 'yes'){ echo ' -- Lader 2 ingeschakeld'.PHP_EOL; }
		sleep(5);
		}
		
		if ($ChargerOneStatus == 'On' && $hwSolarReturn >= $minSolarReturn){
		switchHwSocket('one','Off');
		if ($debug == 'yes'){ echo ' -- Lader 1 uitgeschakeld'.PHP_EOL; }
		}
		
	}

	if (($P1ChargerUsage <= $minPowerTotalReturn) && ($pvAvInputVoltage < 26 && $pvAvInputWatts == 0 && $hwSolarReturn <= $minPowerOneReturn)){
	
		if ($ChargerOneStatus == 'Off'){
		switchHwSocket('one','On');
		if ($debug == 'yes'){ echo ' -- Lader 1 ingeschakeld'.PHP_EOL; }
		sleep(5);
		}
		
		if ($ChargerTwoStatus == 'Off'){
		switchHwSocket('two','On');
		if ($debug == 'yes'){ echo ' -- Lader 2 ingeschakeld'.PHP_EOL; }
		}
		
	}


// Print Footer
	if ($debug == 'yes'){
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
}
?>