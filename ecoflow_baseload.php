<?php
//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                                                               //
//                        No need to edit                        //
// **************************************************************//
//                                                               //

// Require Ecoflow Class files
	$files = glob(__DIR__ . '/config/*.php');
	foreach ($files as $file) {
		if ($file != __FILE__) {
			require_once($file);
		}
	}
	
//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                           Schedule                            //
// **************************************************************//
//                                                               //

// Schedule Manual 
	if ($runInfinity == 'no' && date('H:i') >= ( ''.$invStartTime.'' ) && date('H:i') <= ( ''.$invEndTime.'' )) {
		$schedule = 1;
		
// Schedule into infinity
	} elseif ($runInfinity == 'yes') {
		$schedule = 1;
		
	} else {
		$schedule = 0;	
	}

//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                      Calculate new baseload                   //
// **************************************************************//
//                                                               //
	
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

//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                       Baseload failsaves                      //
// **************************************************************//
//                                                               //
	
// Set baseload to max 	
	if ($newBaseload > $ecoflowMaxOutput) {
		$newBaseload = $ecoflowMaxOutput;
		$newInvBaseload = ($ecoflowMaxOutput) * 10;
	}
	
// Set baseload to null when charging
	if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') {
		$newBaseload    = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when SwitchTime is negative
	if ($schedule == 0) {
		$newBaseload    = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when inverter has to return less then it has to deliver
	if ($newBaseload <= $ecoflowMinOutput && $hwSolarReturn != 0) {
		$newBaseload    = 0;
		$newInvBaseload = 0;
	}

// Limit baseload when inverter is getting to hot
	if ($invTemp >= $ecoflowMaxInvTemp) {
		$newBaseload    = ($newBaseload) / 1.5;
		$newInvBaseload = ($newInvBaseload / 1.5) * 10;

	}

// Set baseload to null when battery empty
	if ($batterySOC < $batteryMinimum){
		$newBaseload    = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when battery has not been calibrated yet
	if ($batterySOC > 100.2){
		$newBaseload    = 0;
		$newInvBaseload = 0;
	}
	
// Set baseload to null when battery is empty #failsave if SOC is calculate wrong
	if ($pvAvInputVoltage <= $bmsMinimumVoltage && $hwInvReturn != 0) {
		$newBaseload    = 0;
		$newInvBaseload = 0;
		
	} elseif ($pvAvInputVoltage > 0 && $pvAvInputVoltage < $bmsRestoredVoltage && $hwInvReturn == 0) {
		$newBaseload    = 0;
		$newInvBaseload = 0;
	}
	
// Set baseload to null
	if ($controlSwitch == 'Off' || $controlSwitch == 'Stop' || $controlSwitch == 'Manual') {
		$newBaseload    = 0;
		$newInvBaseload = 0;
	}
	
//															     //
// **************************************************************//
//           EcoFlow micro-inverters automatic baseload          //
//                    Print & Update Baseload                    //
// **************************************************************//
//                                                               //

// Print Lader Status
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

// Print Charger Status	
		echo ' -/- Laders                   -\-'.PHP_EOL;
		echo '  -- Lader 1                   : '.$hwChargerOneStatus.''.PHP_EOL;		
		echo '  -- Lader 2                   : '.$hwChargerTwoStatus.''.PHP_EOL;
		echo '  -- Lader 3                   : '.$hwChargerThreeStatus.''.PHP_EOL;
		echo '  -- Laders verbruik           : '.$chargerUsage.' Watt'.PHP_EOL;		
		echo ' '.PHP_EOL;

// Print Battery Status
		echo ' -/- Batterij                 -\-'.PHP_EOL;
		echo '  -- Batterij Voltage          : '.$pvAvInputVoltage.' Volt'.PHP_EOL;
		echo '  -- Batterij SOC              : '.$batterySOC.'%'.PHP_EOL;		
		echo '  -- Opgeslagen energie        : '.$batteryAvailable.' kWh'.PHP_EOL;
		if ($chargerUsage >= $chargerWattsIdle){
		echo '  -- Oplaadtijd (resterend)    : '.$realChargeTime.' u(ren)'.PHP_EOL;
		}
		if ($hwInvReturn < 0){		
		echo '  -- Ontlaadtijd (resterend)   : '.$realDischargeTime.' u(ren)'.PHP_EOL;			
		}
		echo ' '.PHP_EOL;

// Print Inverter Status
		echo ' -/- EcoFlow Omvormers        -\-'.PHP_EOL;
		echo '  -- Omvormer 1 Temperatuur    : '.$invOneTemp.'˚C'.PHP_EOL;
		echo '  -- Omvormer 2 Temperatuur    : '.$invTwoTemp.'˚C'.PHP_EOL;	
		echo '  -- Omvormer koeling          : '.$hwInvFanStatus.''.PHP_EOL;
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
		echo ' '.PHP_EOL;

// Print Various
		echo ' -/- Various                  -\-'.PHP_EOL;
		echo '  -- Charge Control Switch     : '.$controlSwitch.''.PHP_EOL;
		echo '  -- L'.$fase.' bescherming            : '.$faseProtection.''.PHP_EOL;
		echo '  -- Houd BMS wakker           : '.$keepBMSalive.''.PHP_EOL;
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
		
		if ($hwP1Usage > 0){
			if (($newBaseload - $currentBaseload) < -abs($ecoflowOutputOffSet) || ($newBaseload - $currentBaseload) > $ecoflowOutputOffSet){

				if ($debug == 'yes'){
				echo '  -- Baseload update           : false'.PHP_EOL;	
				}
				$invBaseload = ($newInvBaseload) / 2;
				$ecoflow->setDeviceFunction($ecoflowOneSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $invBaseload]);
				sleep(1);
				$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $invBaseload]);

			} else {
				
				if ($debug == 'yes'){
				echo '  -- Baseload update           : false'.PHP_EOL;	
				}
			}
			
		} elseif ($hwP1Usage <= 0){
			
				if ($debug == 'yes'){
				echo '  -- Baseload update           : true'.PHP_EOL;	
				}
				$invBaseload = ($newInvBaseload) / 2;
				$ecoflow->setDeviceFunction($ecoflowOneSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $invBaseload]);
				sleep(1);
				$ecoflow->setDeviceFunction($ecoflowTwoSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $invBaseload]);
		
		}

	} else {
		if ($debug == 'yes'){
		echo '  -- Baseload update           : false.'.PHP_EOL;	
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

?>