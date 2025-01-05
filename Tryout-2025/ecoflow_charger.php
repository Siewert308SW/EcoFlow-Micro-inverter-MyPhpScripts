<?php
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//         Calculate remaining Charge or Discharge time          //
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
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                 Calibrate Charge kWh values?                  //
// **************************************************************//
//                                                               //
		
	if (file_exists($batteryInputFile) && file_exists($batteryOutputFile)){
		if($pvAvInputVoltage > $batteryVolt && $chargerUsage >= 1 && $chargerUsage <= $chargerWattsIdle && $batteryInputkWh != $batteryInputCal){
		writeBattInputOutput(''.$batteryInputCal.'','Input');
		writeBattInputOutput(''.$hwInvTotal.'','Output');
		}
	} else {
		writeBattInputOutput(''.$batteryInputCal.'','Input');
		writeBattInputOutput(''.$hwInvTotal.'','Output');
	}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                  Fase powerusage protection                   //
// **************************************************************//
//                                                               //

	if ($hwP1Fase >= $maxFaseWatts){
		if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(10);}
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
		$faseProtect = 1;
	} else {
		$maxFaseWatts = ($maxFaseWatts - $chargerTotalUsage);
		if ($hwP1Fase <= $maxFaseWatts){
		$faseProtect = 0;
		} else {
		$faseProtect = 1;
		}
	}
	
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                       Keep BMS Awake                          //
// **************************************************************//
//                                                               //
	if ($keepBMSalive == 'yes'){$bmsAwake = 1;}
	if ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'Off' && $pvAvInputVoltage > 0 && $pvAvInputVoltage <= $bmsMinimumVoltage && $hwInvReturn == 0/*&& $hwSolarReturn != 0*/) {
	switchHwSocket('one','On');	
	$bmsAwake = 1;
	} elseif ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsMinimumVoltage && $pvAvInputVoltage <= $bmsMaximumVoltage && $hwInvReturn == 0/*&& $hwSolarReturn != 0*/) {
	$bmsAwake = 1;
	} else {
	$bmsAwake = 0;
	}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                             Print                             //
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
		echo ' --    LiFePo4 12/12/20a Chargers    --'.PHP_EOL;
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
		echo '  -- Batterij opwek            : '.$hwInvReturn.' Watt'.PHP_EOL;
		echo '  -- Echte verbruik            : '.$realUsage.' Watt'.PHP_EOL;
		if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') {
		echo '  -- Stroomverbruik excl laders: '.$P1ChargerUsage.' Watt'.PHP_EOL;
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

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                      Start/Stop Charging                      //
// **************************************************************//
//                                                               //

	if ($isDST == '1'){
		$chargerOne_Usage   = ($chargerOneUsage);
		$chargerTwo_Usage   = ($chargerTwoUsage);
		$chargerOneTwo_Usage= ($chargerOneTwoUsage);
		$chargerThree_Usage = ($chargerThreeUsage);
	} else {
		$chargerOne_Usage   = ($chargerOneUsage / 3.5);
		$chargerTwo_Usage   = ($chargerTwoUsage / 1.5);
		$chargerOneTwo_Usage= ($chargerOneTwoUsage / 1.5);
		$chargerThree_Usage = ($chargerThreeUsage / 3.5);
	}
	$chargerTotal_Usage = ($chargerOne_Usage + $chargerTwo_Usage + $chargerThree_Usage);
		
if ($faseProtect == 0 && $bmsAwake == 0){	
	if (($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') && ($P1ChargerUsage > $chargerOne_Usage || $chargerUsage <= $chargerWattsIdle || $hwInvReturn != 0)){

		if ($hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsMaximumVoltage){ switchHwSocket('one','Off'); sleep(10);}			
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1 AAN - Lader 2 & 3 UIT			
	if ($P1ChargerUsage > $chargerTwo_Usage && $P1ChargerUsage <= $chargerOne_Usage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn != 0){		

		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 2 AAN - Lader 1 & 3 UIT
	if ($P1ChargerUsage > $chargerOneTwo_Usage && $P1ChargerUsage <= $chargerTwo_Usage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn != 0){	
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1 & 2 AAN - Lader 3 UIT
	if ($P1ChargerUsage > $chargerTotal_Usage && $P1ChargerUsage <= $chargerOneTwo_Usage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn != 0){		
		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}

// Lader 1, 2, & 3 AAN
	if ($P1ChargerUsage <= $chargerTotal_Usage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn != 0){	
		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($hwChargerThreeStatus == 'Off'){ switchHwSocket('three','On');}
	}

}
	
?>