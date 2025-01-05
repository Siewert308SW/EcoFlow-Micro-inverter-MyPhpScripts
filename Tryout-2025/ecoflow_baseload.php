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
			require($file);
		}
	}
	
//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                         Keep BMS Awake                        //
// **************************************************************//
//                                                               //
	if ($keepBMSalive == 'yes'){$bmsAwake = 1;}
	if ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'Off' && $pvAvInputVoltage > 0 && $pvAvInputVoltage <= $bmsMinimumVoltage && $hwInvReturn == 0/*&& $hwSolarReturn != 0*/) {
	$bmsAwake = 1;
	} elseif ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsMinimumVoltage && $pvAvInputVoltage <= $bmsMaximumVoltage && $hwInvReturn == 0/*&& $hwSolarReturn != 0*/) {
	$bmsAwake = 1;
	} else {
	$bmsAwake = 0;
	}

//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//         Calculate remaining Charge or Discharge time          //
// **************************************************************//
//                                                               //
	
// Calculate remaining charge time
	if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On'){
		if ($batterySOC <= 100){	
		$chargeTimeRemaining    = round(abs(($batteryCapacity - $batteryAvailable) * 1000 / $chargerEfficiency * 100 / $chargerUsage), 2);				
		} else {
		$chargeTimeRemaining    = 0;	
		}
	} elseif ($hwChargerOneStatus == 'Off' && $hwChargerTwoStatus == 'Off' && $hwChargerThreeStatus == 'Off'){
	$chargeTimeRemaining    = 0;
	}
	
// Calculate remaining discharge time	
	if ($hwInvReturn < 0){
	$hwInvReturnABS = abs($hwInvReturn) / 1000 ;	
	$disChargeTimeRemaining = round(($batteryAvailable - $batteryMinimumLeft) / $hwInvReturnABS, 3);
	} elseif ($hwInvReturn >= 0){
	$disChargeTimeRemaining = 0;	
	}
	
	$realChargeTime    = convertTime($chargeTimeRemaining);
	$realDischargeTime = convertTime($disChargeTimeRemaining);
	
//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                           Schedule                            //
// **************************************************************//
//                                                               //

// Schedule Manual 
	if ($runInfinity == 'no' && date('H:i') >= ( ''.$invStartTime.'' ) && date('H:i') <= ( ''.$invEndTime.'' )) {
		$schedule = 1;
		
// Schedule Summertime into infinity
	} elseif ($runInfinity == 'yes' && $isDST == '1') {
		$schedule = 1;

// Schedule Wintertime		
	} elseif ($runInfinity == 'yes' && $isDST == '0' && $runInfinityNight == 'yes' && date('H:i') >= ( '00:00' ) && date('H:i') < ( '12:30' )) {
		$schedule = 1;

	} elseif ($runInfinity == 'yes' && $isDST == '0' && $runInfinityMidday == 'yes' && date('H:i') >= ( '12:30' ) && date('H:i') < ( ''.$sunset.'' )) {
		$schedule = 1;
		
	} elseif ($runInfinity == 'yes' && $isDST == '0' && $runInfinityEvening == 'yes' && date('H:i') >= ( ''.$sunset.'' ) && date('H:i') < ( '23:59' )) {
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
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Set baseload to null when SwitchTime is negative
	if ($schedule == 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when inverter has to return less then it can deliver
	if ($newBaseload <= 100 && $hwSolarReturn != 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when inverter is getting to hot
	if ($invTemp >= $maxInvTemp) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when battery has not been fully charged during wintertime
	if ($batterySOC <= $batteryMinimum){
		$newBaseload = 0;
		$newInvBaseload = 0;
	}

// Set baseload to null when battery has not been calibrated yet
	if ($batterySOC > 100){
		$newBaseload = 0;
		$newInvBaseload = 0;
	}
	
// Set baseload to null when battery is empty #failsave if SOC is calculate wrong
	if ($pvAvInputVoltage <= $bmsMinimumVoltage && $hwInvReturn != 0) {
		$newBaseload = 0;
		$newInvBaseload = 0;
	} elseif ($pvAvInputVoltage > 0 && $pvAvInputVoltage <= $bmsMaximumVoltage && $hwInvReturn == 0) {
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
		echo '  -- Batterij voltage          : '.$pvAvInputVoltage.' Volt'.PHP_EOL;
		echo '  -- Batterij SOC              : '.$batterySOC.'%'.PHP_EOL;		
		echo '  -- Opgeslagen energie        : '.$batteryAvailable.' kWh'.PHP_EOL;
		if ($chargerUsage >= $chargerWattsIdle){
		echo '  -- Oplaadtijd (resterend)    : '.$realChargeTime.' u(ren)'.PHP_EOL;
		}
		if ($hwInvReturn < 0){		
		echo '  -- Ontlaadtijd (resterend)   : '.$realDischargeTime.' u(ren)'.PHP_EOL;			
		}
		if ($bmsAwake == 1) {
		echo '  -- BMS Awake laden           : actief'.PHP_EOL;	
		}
		echo ' '.PHP_EOL;

// Print Schakeltijd
		echo ' -/- Schakeltijd              -\-'.PHP_EOL;
		if ($runInfinity == 'no'){
		echo '  -- Start Tijd                : '.$invStartTime.''.PHP_EOL;
		echo '  -- Eind Tijd                 : '.$invEndTime.''.PHP_EOL;			
		}
		echo '  -- $runInfinity              : '.$runInfinity.''.PHP_EOL;
		echo '  -- $runInfinityMidday        : '.$runInfinityMidday.''.PHP_EOL;
		echo '  -- $runInfinityEvening       : '.$runInfinityEvening.''.PHP_EOL;	
		echo '  -- $runInfinityNight         : '.$runInfinityNight.''.PHP_EOL;			
		if ($schedule == 1) {
		echo '  -- Schakeltijd               : true'.PHP_EOL;
		} else {
		echo '  -- Schakeltijd               : false'.PHP_EOL;
		}
		echo ' '.PHP_EOL;
		
// Print Inverter Status
		echo ' -/- EcoFlow Omvormer         -\-'.PHP_EOL;
		echo '  -- SN Nummer                 : '.$ecoflowSerialNumber.''.PHP_EOL;
		if ($ecoflowStatus == 1) {
		echo '  -- EcoFlow Status            : Online'.PHP_EOL;
		} else {
		echo '  -- EcoFlow Status            : Offline'.PHP_EOL;
		}
		echo '  -- Temperatuur               : '.$invTemp.'˚C'.PHP_EOL;		
		echo ' '.PHP_EOL;

// Print Various
		echo ' -/- Various                  -\-'.PHP_EOL;
		echo '  -- L'.$fase.' bescherming            : '.$faseProtection.''.PHP_EOL;
		echo '  -- Houd BMS wakker           : '.$keepBMSalive.''.PHP_EOL;
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
		if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') {
		echo '  -- Stroomverbruik excl laders: '.$P1ChargerUsage.' Watt'.PHP_EOL;
		}
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
		$ecoflow->setDeviceFunction($ecoflowSerialNumber, 'WN511_SET_PERMANENT_WATTS_PACK', ['permanent_watts' => $newInvBaseload]);
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

?>