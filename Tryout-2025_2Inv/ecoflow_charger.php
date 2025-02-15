<?php
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
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
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                 Calibrate Charge kWh values?                  //
// **************************************************************//
//                                                               //
		
	if (file_exists($batteryInputFile) && file_exists($batteryOutputFile)){
		if(($batterySOC > 100 || $batterySOC >= $batteryVoltAlmostCharged) && ($chargerUsage >= 0 && $chargerUsage <= $chargerWattsIdle && $batteryInputkWh != $batteryInputCal)){
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
		if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(15);}
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(15);}
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
//  
                                                            //
	if ($keepBMSalive == 'yes'){$bmsAwake = 1;}
	if ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'Off' && $pvAvInputVoltage > 0 && $pvAvInputVoltage < $bmsMinimumVoltage) {
	switchHwSocket('one','On');
	
	if (file_exists($batteryInputFile) && file_exists($batteryOutputFile)){
	}
	$bmsAwake = 1;
	} elseif ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsMinimumVoltage && $pvAvInputVoltage < $bmsRestoreVoltage) {
	$bmsAwake = 1;
	} elseif ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsMinimumVoltage && $pvAvInputVoltage >= $bmsRestoreVoltage) {
	$bmsAwake = 0;
	} else {
	$bmsAwake = 0;
	}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                       Short Override                          //
// **************************************************************//
//                                                               //

	if ($heaterWatts <= 50 && $quookerWatts <= 50 && $aanrecht1Watts <= 100 && $aanrecht2Watts <= 100 && $natalyaWatts <= 500 && $afzuigkapWatts <= 50){	
	$shortOverride = 0;
	} elseif (($heaterWatts > 50 || $quookerWatts > 50 || $aanrecht1Watts > 100 || $aanrecht2Watts > 100 || $natalyaWatts > 500 || $afzuigkapWatts > 50) && ($hwSolarReturn <= $chargerOneUsage)) {
	$shortOverride = 1;
	} elseif (($heaterWatts > 50 || $quookerWatts > 50 || $aanrecht1Watts > 100 || $aanrecht2Watts > 100 || $natalyaWatts > 500 || $afzuigkapWatts > 50) && ($hwSolarReturn > $chargerOneUsage)) {
	$shortOverride = 0;
	}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                      Charge Override                          //
// **************************************************************//
//                                                               //

	if ($pvAvInputVoltage >= $batteryVoltAlmostCharged && $chargerUsage > $chargerWattsIdle) {
	$chargeOverride = 1;
	} elseif ($pvAvInputVoltage >= $batteryVoltAlmostCharged && $chargerUsage <= $chargerWattsIdle) {
	$chargeOverride = 0;
	} elseif ($pvAvInputVoltage < $batteryVoltAlmostCharged) {
	$chargeOverride = 0;	
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
		echo ' '.PHP_EOL;

// Print Inverter Status
		echo ' -/- EcoFlow Omvormers        -\-'.PHP_EOL;
		if ($ecoflowOneStatus == 1) {
		echo '  -- Omvormer 1                : Online'.PHP_EOL;
		} else {
		echo '  -- Omvormer 1                : Offline'.PHP_EOL;
		}
		if ($ecoflowTwoStatus == 1) {
		echo '  -- Omvormer 2                : Online'.PHP_EOL;
		} else {
		echo '  -- Omvormer 2                : Offline'.PHP_EOL;
		}
		echo '  -- Omvormer 1 Temperatuur    : '.$invOneTemp.'˚C'.PHP_EOL;
		echo '  -- Omvormer 2 Temperatuur    : '.$invTwoTemp.'˚C'.PHP_EOL;		
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
		echo '  -- Batterij opwek            : '.$hwInvReturn.' Watt'.PHP_EOL;
		echo '  -- Echte verbruik            : '.$realUsage.' Watt'.PHP_EOL;
		echo '  -- Verbruik excl laders      : '.$P1ChargerUsage.' Watt'.PHP_EOL;
	}

// Print Footer
	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	}
	
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                    Start/Stop Charging P1                     //
// **************************************************************//
//                                                               //

if ($controlSwitch == 'P1'){
	if ($faseProtect == 0 && $bmsAwake == 0 && $batterySOC < 100 && $batterySOC < $batteryVoltAlmostCharged && $hwInvReturn == 0 && $hwSolarReturn < $chargerOneUsage){
		
// Lader 1 AAN - Lader 2 & 3 UIT
	if ($P1ChargerUsage >= $chargerTwoUsage && $P1ChargerUsage < ($chargerOneUsage / 2) && $chargeOverride == 0){		
		if ($debug == 'yes'){echo ' --           Lader 1 AAN            --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off'/* && $hwP1Usage > $chargerOffSet*/ && $hwSolarReturn < $chargerOneUsage){ switchHwSocket('one','On'); sleep(15);}
		if ($hwChargerTwoStatus == 'On' && $hwP1Usage > $chargerOffSet){ switchHwSocket('two','Off'); sleep(15);}
		if ($hwChargerThreeStatus == 'On' && $hwP1Usage > $chargerOffSet){ switchHwSocket('three','Off');}
	}

// Lader 1 & 3 AAN - Lader 2 UIT
	if ($P1ChargerUsage >= $chargerOneTwoUsage && $P1ChargerUsage < $chargerTwoUsage && $chargeOverride == 0){	
		if ($debug == 'yes'){echo ' --          Lader 1 & 3 AAN         --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off' && $hwP1Usage < $chargerOffSet){ switchHwSocket('one','On'); sleep(15);}
		if ($hwChargerTwoStatus == 'On' && $hwP1Usage > $chargerOffSet){ switchHwSocket('two','Off'); sleep(15);}
		if ($hwChargerThreeStatus == 'Off' && $hwP1Usage < $chargerOffSet){ switchHwSocket('three','On');}
	}

// Lader 1 & 2 AAN - Lader 3 UIT
	if ($P1ChargerUsage >= $chargerTotalUsage && $P1ChargerUsage < $chargerOneTwoUsage && $chargeOverride == 0){		
		if ($debug == 'yes'){echo ' --          Lader 1 & 2 AAN         --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off' && $hwP1Usage < $chargerOffSet){ switchHwSocket('one','On'); sleep(15);}
		if ($hwChargerTwoStatus == 'Off' && $hwP1Usage < $chargerOffSet){ switchHwSocket('two','On'); sleep(15);}
		if ($hwChargerThreeStatus == 'On' && $hwP1Usage > $chargerOffSet){ switchHwSocket('three','Off');}
	}

// Lader 1, 2, & 3 AAN
	if ($P1ChargerUsage < $chargerTotalUsage || $chargeOverride == 1){	
		if ($debug == 'yes'){echo ' --        Lader 1, 2, & 3 AAN       --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off' && $chargeOverride == 0){ switchHwSocket('one','On'); sleep(15);}
		if ($hwChargerOneStatus == 'On' && $chargeOverride == 1){ switchHwSocket('one','Off'); sleep(15);}
		
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(15);}
		
		if ($hwChargerThreeStatus == 'Off' && $chargeOverride == 0){ switchHwSocket('three','On');}
		if ($hwChargerThreeStatus == 'On' && $chargeOverride == 1){ switchHwSocket('three','Off');}
	}
}

// Laders 1, 2 en 3 UIT
	//if (($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') && ($chargerUsage <= $chargerWattsIdle || $batterySOC >= $batteryVoltAlmostCharged)){
			
	//	if ($debug == 'yes' && $chargeOverride == 0){echo ' --        Laders 1, 2 en 3 UIT      --'.PHP_EOL;}
	//	if ($hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsRestoreVoltage && $chargeOverride == 0 && $bmsAwake == 0){ switchHwSocket('one','Off'); sleep(15);}						
	//	if ($hwChargerTwoStatus == 'On' && $pvAvInputVoltage > $bmsRestoreVoltage && $chargeOverride == 0 && $bmsAwake == 0){ switchHwSocket('two','Off'); sleep(15);}
	//	if ($hwChargerThreeStatus == 'On' && $pvAvInputVoltage > $bmsRestoreVoltage && $chargeOverride == 0 && $bmsAwake == 0){ switchHwSocket('three','Off');}
	//}

	if (($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') && ($hwP1Usage > $chargerOffSet || $hwInvReturn != 0 || $hwSolarReturn > $chargerOneUsage || $chargerUsage <= $chargerWattsIdle)){
			
		if ($debug == 'yes' && $chargeOverride == 0){echo ' --        Laders 1, 2 of 3 UIT      --'.PHP_EOL;}
		if (($hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsRestoreVoltage && $chargeOverride == 0 && $shortOverride == 0 && $faseProtect == 0 && $bmsAwake == 0) && ($hwSolarReturn > ($chargerOneUsage / 2) || $chargerUsage <= $chargerWattsIdle)){ switchHwSocket('one','Off'); sleep(15);}						
		if ($hwChargerTwoStatus == 'On' && $pvAvInputVoltage > $bmsRestoreVoltage && $chargeOverride == 0 && $faseProtect == 0 && $bmsAwake == 0){ switchHwSocket('two','Off'); sleep(15);}
		if ($hwChargerThreeStatus == 'On' && $pvAvInputVoltage > $bmsRestoreVoltage && $chargeOverride == 0 && $shortOverride == 0 && $faseProtect == 0 && $bmsAwake == 0){ switchHwSocket('three','Off');}
	}
	
	if ($debug == 'yes'){
		if ($shortOverride == 1) {echo ' --      (Short Override actief)     --'.PHP_EOL;}
		if ($chargeOverride == 1) {echo ' --    (Batterij aan het aftoppen)   --'.PHP_EOL;}
		if ($bmsAwake == 1){echo ' --        (BMS wakker maken)      --'.PHP_EOL;}
		echo ' --         P1 Based Charging        --'.PHP_EOL;
	}
	
}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                  Start/Stop Charging Manual                   //
// **************************************************************//
//                                                               //

if ($controlSwitch == 'Manual'){
	if ($faseProtect == 0 && $bmsAwake == 0 && $batterySOC < 100 && $hwInvReturn == 0){
		
// Lader 1, 2, & 3 AAN
	if (($hwChargerOneStatus == 'Off' || $hwChargerTwoStatus == 'Off' || $hwChargerThreeStatus == 'Off') && ($hwInvReturn == 0)){	
		if ($debug == 'yes'){echo ' --        Lader 1, 2, & 3 AAN       --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(15);}
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(15);}
		if ($hwChargerThreeStatus == 'Off'){ switchHwSocket('three','On');}
	}

// Laders 1, 2 en 3 UIT
	if (($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') && ($chargerUsage <= $chargerWattsIdle || $hwInvReturn != 0)){
		if ($debug == 'yes'){echo ' --        Laders 1, 2 en 3 UIT      --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(15);}						
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(15);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}	
	}
			
		if ($debug == 'yes'){echo ' --          Manual Charging         --'.PHP_EOL;}	
	}
}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                    Start/Stop Charging Off                    //
// **************************************************************//
//                                                               //

if ($controlSwitch == 'Off'){
	
// Laders 1, 2 en 3 UIT
	if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On'){
		if ($debug == 'yes'){echo ' --        Laders 1, 2 en 3 UIT      --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(15);}						
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(15);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
		//if ($hwInvStatus == 'On'){ switchHwSocket('inv','Off');}	
	}
}

// Laders 1, 2, 3 en Omvormer
if ($controlSwitch == 'Stop' && $hwInvStatus == 'On'){
	if ($debug == 'yes'){echo ' --     Laders 1, 2, 3 en Omvormer   --'.PHP_EOL;}
	//if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(15);}						
	//if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(15);}
	//if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	//if ($hwInvStatus == 'On'){ switchHwSocket('inv','Off');}	
}
	
// Print Footer
	if ($debug == 'yes'){
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	
?>