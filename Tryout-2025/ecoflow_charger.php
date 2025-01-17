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

	if (file_exists($batteryCycleFile)){
		
		if(($pvAvInputVoltage <= $bmsMinimumVoltage || $batterySOC <= $batteryMinimum) && ($batterySaveUp == 'yes' && $batteryCycle != 'Leeg')){
		writeBattInputOutput('Leeg','Cycle');
		}
		
		if(($pvAvInputVoltage > $batteryVoltCharged || $batterySOC >= 100) && ($batterySaveUp == 'yes' && $batteryCycle != 'Vol')){
		writeBattInputOutput('Vol','Cycle');
		}
		
	} else {
		if($batterySaveUp == 'yes'){
		writeBattInputOutput('Standby','Cycle');
		}
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
//  
//exit(0);                                                             //
	if ($keepBMSalive == 'yes'){$bmsAwake = 1;}
	if ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'Off' && $pvAvInputVoltage > 0 && $pvAvInputVoltage <= $bmsMinimumVoltage && $hwInvReturn == 0) {
	switchHwSocket('one','On');
	if (file_exists($batteryInputFile) && file_exists($batteryOutputFile)){
	writeBattInputOutput(''.$hwChargersTotalInput.'','Input');
	writeBattInputOutput(''.$hwInvTotal.'','Output');
	}
	$bmsAwake = 1;
	} elseif ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsMinimumVoltage && $pvAvInputVoltage >= $bmsRestoreVoltage && $hwInvReturn == 0) {
	$bmsAwake = 1;
	switchHwSocket('one','Off');
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
		echo '  -- Batterij cycle            : '.$batteryCycle.''.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Inverter Status
		echo ' -/- EcoFlow Omvormer         -\-'.PHP_EOL;
		if ($ecoflowStatus == 1) {
		echo '  -- EcoFlow Status            : Online'.PHP_EOL;
		} else {
		echo '  -- EcoFlow Status            : Offline'.PHP_EOL;
		}
		echo '  -- Temperatuur               : '.$invTemp.'˚C'.PHP_EOL;		
		echo ' '.PHP_EOL;

// Print Various
		echo ' -/- Various                  -\-'.PHP_EOL;
		echo '  -- Batterij opsparen         : '.$batterySaveUp.''.PHP_EOL;
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
//                      Start/Stop Charging                      //
// **************************************************************//
//                                                               //

if ($faseProtect == 0 && $bmsAwake == 0){

// Lader 1 AAN - Lader 2 & 3 UIT			
	if ($P1ChargerUsage > $chargerTwoUsage && $P1ChargerUsage <= $chargerOneUsage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn <= $chargerOneUsage && $pvAvInputVoltage < $batteryVoltAlmostCharged && $batteryCycle != 'Vol'){		
		if ($debug == 'yes'){echo ' --   Lader 1 AAN - Lader 2 & 3 UIT  --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($hwChargerTwoStatus == 'On' && $override == 'no'){ switchHwSocket('two','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On' && $override == 'no'){ switchHwSocket('three','Off');}
	}

// Lader 2 AAN - Lader 1 & 3 UIT
	if ($P1ChargerUsage > $chargerOneTwoUsage && $P1ChargerUsage <= $chargerTwoUsage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn <= $chargerOneUsage && $pvAvInputVoltage > $bmsRestoreVoltage && $pvAvInputVoltage < $batteryVoltAlmostCharged && $batteryCycle != 'Vol'){	
		if ($debug == 'yes'){echo ' --   Lader 2 AAN - Lader 1 & 3 UIT  --'.PHP_EOL;}
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($hwChargerOneStatus == 'On' && $override == 'no'){ switchHwSocket('one','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On' && $override == 'no'){ switchHwSocket('three','Off');}
	}

// Lader 1 & 2 AAN - Lader 3 UIT
	if ($P1ChargerUsage > $chargerTotalUsage && $P1ChargerUsage <= $chargerOneTwoUsage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn <= $chargerOneUsage && $pvAvInputVoltage > $bmsRestoreVoltage && $pvAvInputVoltage < $batteryVoltAlmostCharged && $batteryCycle != 'Vol'){		
		if ($debug == 'yes'){echo ' --   Lader 1 & 2 AAN - Lader 3 UIT  --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($hwChargerThreeStatus == 'On' && $override == 'no'){ switchHwSocket('three','Off');}
	}

// Lader 1, 2, & 3 AAN
	if ($P1ChargerUsage <= $chargerTotalUsage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn <= $chargerOneUsage && $pvAvInputVoltage > $bmsRestoreVoltage && $pvAvInputVoltage < $batteryVoltAlmostCharged && $batteryCycle != 'Vol'){	
		if ($debug == 'yes'){echo ' --        Lader 1, 2, & 3 AAN       --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(10);}
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($hwChargerThreeStatus == 'Off'){ switchHwSocket('three','On');}
	}
	
// Laders 1 en 3 UIT - Lader 2 AAN (Batterijen bijna vol)
	if ($pvAvInputVoltage >= $batteryVoltAlmostCharged && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn <= $chargerOneUsage && $batteryCycle != 'Vol'){	
		if ($debug == 'yes'){echo ' --  Laders 1 en 3 UIT - Lader 2 AAN --'.PHP_EOL;}
		if ($hwChargerTwoStatus == 'Off'){ switchHwSocket('two','On'); sleep(10);}
		if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}
	}
	
// Laders 1 AAN/UIT, 2 en 3 UIT
	if (($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') && ($P1ChargerUsage > $chargerOneUsage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn <= $chargerOneUsage && $pvAvInputVoltage > $bmsRestoreVoltage && $pvAvInputVoltage < $batteryVoltAlmostCharged)){	
		if ($debug == 'yes'){echo ' --   Laders 1 AAN/UIT, 2 en 3 UIT   --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'Off' && $override == 'no' && $P1ChargerUsage > $chargerTwoUsage && $P1ChargerUsage <= $chargerOneUsage && $batterySOC < 100 && $hwInvReturn == 0 && $hwSolarReturn <= $chargerOneUsage && $pvAvInputVoltage < $batteryVoltAlmostCharged && $batteryCycle != 'Vol'){ switchHwSocket('one','On'); sleep(10);}
		if ($hwChargerOneStatus == 'On' && $override == 'no' && $pvAvInputVoltage > $bmsRestoreVoltage && $hwSolarReturn > $chargerOneUsage){ switchHwSocket('one','Off'); sleep(10);}
		if ($hwChargerTwoStatus == 'On' && $override == 'no'){ switchHwSocket('two','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On' && $override == 'no'){ switchHwSocket('three','Off');}
	}

// Laders 1, 2 en 3 UIT
	if (($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On') && ($chargerUsage <= $chargerWattsIdle || $hwInvReturn != 0 || $hwSolarReturn > $chargerOneUsage)){
		if ($debug == 'yes'){echo ' --        Laders 1, 2 en 3 UIT.     --'.PHP_EOL;}
		if ($hwChargerOneStatus == 'On' && $pvAvInputVoltage > $bmsRestoreVoltage && $hwSolarReturn > $chargerOneUsage){ switchHwSocket('one','Off'); sleep(10);}						
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(10);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}	
	}
	
}

// Print Footer
	if ($debug == 'yes'){
	echo ' --              The End             --'.PHP_EOL;
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	
?>