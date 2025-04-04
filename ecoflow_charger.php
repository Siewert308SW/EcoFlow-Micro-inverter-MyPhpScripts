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
		echo ' --     LiFePo4 12/12/20a Chargers   --'.PHP_EOL;
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
		echo '  -- Omvormer 1 Temperatuur    : '.$invOneTemp.'˚C'.PHP_EOL;
		echo '  -- Omvormer 2 Temperatuur    : '.$invTwoTemp.'˚C'.PHP_EOL;	
		echo '  -- Omvormer koeling          : '.$hwInvFanStatus.''.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Various
		echo ' -/- Various                  -\-'.PHP_EOL;
		echo '  -- Charge Control Switch     : '.$controlSwitch.''.PHP_EOL;
		echo '  -- L'.$fase.' bescherming            : '.$faseProtection.''.PHP_EOL;
		echo '  -- Houd BMS wakker           : '.$keepBMSalive.''.PHP_EOL;
		echo '  -- Lader standby             : '.$chargerStandby.''.PHP_EOL;
		echo ' '.PHP_EOL;

// Print Energie Status		
		echo ' -/- Energie                  -\-'.PHP_EOL;
		echo '  -- P1-Meter                  : '.$hwP1Usage.' Watt'.PHP_EOL;
		echo '  -- Zonnepanelen opwek        : '.$hwSolarReturn.' Watt'.PHP_EOL;
		echo '  -- Batterij opwek            : '.$hwInvReturn.' Watt'.PHP_EOL;
		echo '  -- Echte verbruik            : '.$realUsage.' Watt'.PHP_EOL;
		echo '  -- Verbruik trigger          : '.$P1ChargerUsage.' Watt'.PHP_EOL;
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
	if ($faseProtect == 0 && $bmsAwake == 0 && $chargerStandby == 'On' && $batterySOC < 100 && $pvAvOneInputVoltage < $battVoltAlmostCharged && $hwInvReturn == 0 && $chargeOverride == 0 && $hwSolarReturn < $chargerOneUsage){

// Laders 1, 2, of 3 UIT (0 > Watt)	
		if (($P1ChargerUsage > $chargerOneUsage && $shortOverride == 0) && ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On')) {

			if (($hwP1Usage > ($chargerOneWatts / 2)) && ($hwP1Usage <= $chargerOneTwoWatts)){
				if ($debug == 'yes'){echo ' --       .Laders 1, 2 en 3 UIT.     --'.PHP_EOL;}
				if ($hwChargerThreeStatus == 'On' && $hwP1Usage > $chargerOneWatts)  { switchHwSocket('three','Off'); sleep(10);}
				if ($hwChargerTwoStatus == 'On'   && $hwP1Usage > $chargerOneWatts)  { switchHwSocket('two','Off'); sleep(10);}
				if ($hwChargerOneStatus == 'On'   && $hwP1Usage > $chargerOneWatts && $hwChargerTwoStatus == 'Off' && $hwChargerThreeStatus == 'Off')  { switchHwSocket('one','Off');}				

			} elseif (($hwP1Usage > $chargerOneTwoWatts) && ($hwP1Usage <= $chargerTotalUsageABS)){
				if ($debug == 'yes'){echo ' --      ..Laders 1, 2 of 3 UIT..    --'.PHP_EOL;}
				if ($hwChargerThreeStatus == 'On' && $hwP1Usage > $chargerOneWatts && $hwChargerTwoStatus == 'Off')  { switchHwSocket('three','Off'); sleep(10);}
				if ($hwChargerTwoStatus == 'On'   && $hwP1Usage > $chargerOneWatts)  { switchHwSocket('two','Off'); sleep(10);}
				if ($hwChargerOneStatus == 'On'   && $hwP1Usage > $chargerOneWatts && $hwChargerTwoStatus == 'Off' && $hwChargerThreeStatus == 'Off')  { switchHwSocket('one','Off');}				
				
				
			} elseif ($hwP1Usage > $chargerTotalUsageABS){
				if ($debug == 'yes'){echo ' --        Laders 1, 2 en 3 UIT      --'.PHP_EOL;}
				if ($hwChargerTwoStatus == 'On')       { switchHwSocket('two','Off'); sleep(10);}
				if ($hwChargerThreeStatus == 'On')     { switchHwSocket('three','Off'); sleep(10);}
				if ($hwChargerOneStatus == 'On')       { switchHwSocket('one','Off');}
			}
			
// Lader 1 AAN (-300 > -600 Watt)	 		
		} elseif (($P1ChargerUsage > $chargerTwoUsage && $shortOverride == 0) && ($P1ChargerUsage <= $chargerOneUsage)) {

			if ($debug == 'yes'){echo ' --            Lader 1 AAN           --'.PHP_EOL;}		
			if ($hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On'){
				if ($hwP1Usage > $chargerOneWatts){
					if ($hwChargerOneStatus == 'Off')  { switchHwSocket('one','On'); sleep(10);}
					if ($hwChargerThreeStatus == 'On') { switchHwSocket('three','Off'); sleep(10);}
					if ($hwChargerTwoStatus == 'On')   { switchHwSocket('two','Off');}
				}
				
			} else {
					if ($hwChargerOneStatus == 'Off')  { switchHwSocket('one','On'); sleep(10);}
					if ($hwChargerThreeStatus == 'On') { switchHwSocket('three','Off'); sleep(10);}
					if ($hwChargerTwoStatus == 'On')   { switchHwSocket('two','Off');}						
			}

// Laders 1 & 3 AAN	(-600 > -900 Watt)		
		} elseif (($P1ChargerUsage > $chargerOneTwoUsage && $shortOverride == 0) && ($P1ChargerUsage <= $chargerTwoUsage)) {
					
			if ($debug == 'yes'){echo ' --          Laders 1 & 3 AAN        --'.PHP_EOL;}
			if ($hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On'){
				if ($hwP1Usage > $chargerOneWatts){
					if ($hwChargerOneStatus == 'Off')  { switchHwSocket('one','On'); sleep(10);}
					if ($hwChargerThreeStatus == 'Off'){ switchHwSocket('three','On'); sleep(10);}
					if ($hwChargerTwoStatus == 'On')   { switchHwSocket('two','Off');}
				}
				
			} else {
					if ($hwChargerOneStatus == 'Off')  { switchHwSocket('one','On'); sleep(10);}
					if ($hwChargerThreeStatus == 'Off'){ switchHwSocket('three','On'); sleep(10);}
					if ($hwChargerTwoStatus == 'On')   { switchHwSocket('two','Off');}							
			}

// Laders 1 & 2 AAN (-900 > -1300 Watt)		
		} elseif (($P1ChargerUsage > $chargerTotalUsage && $shortOverride == 0) && ($P1ChargerUsage <= $chargerOneTwoUsage)) {
					
			if ($debug == 'yes'){echo ' --          Laders 1 & 2 AAN        --'.PHP_EOL;}
			if ($hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On'){
				if ($hwP1Usage > $chargerOneWatts){
					if ($hwChargerOneStatus == 'Off')  { switchHwSocket('one','On'); sleep(10);}
					if ($hwChargerThreeStatus == 'On') { switchHwSocket('three','Off'); sleep(10);}
					if ($hwChargerTwoStatus == 'Off')  { switchHwSocket('two','On');}
				}
				
			} else {
					if ($hwChargerOneStatus == 'Off')  { switchHwSocket('one','On'); sleep(10);}
					if ($hwChargerThreeStatus == 'On') { switchHwSocket('three','Off'); sleep(10);}
					if ($hwChargerTwoStatus == 'Off')   { switchHwSocket('two','On');}							
			}
			
// Laders 1, 2 & 3 AAN (<= -1300 Watt)
	    } elseif (($P1ChargerUsage < $chargerTotalUsage)) {
				if ($debug == 'yes'){echo ' --        Laders 1, 2 & 3 AAN       --'.PHP_EOL;}
				if ($hwChargerOneStatus == 'Off')  	   { switchHwSocket('one','On'); sleep(10);}
				if ($hwChargerThreeStatus == 'Off')    { switchHwSocket('three','On'); sleep(10);}
				if ($hwChargerTwoStatus == 'Off')      { switchHwSocket('two','On');}
		}
	}

// Lader 2 AAN (Batterij Aftoppen)
			if (($chargeOverride == 1 && $faseProtect == 0)){
				if ($debug == 'yes'){echo ' --            Lader 2 AAN           --'.PHP_EOL;}
				if ($hwChargerTwoStatus == 'Off')      { switchHwSocket('two','On'); sleep(10);}
				if ($hwChargerOneStatus == 'Off')      { switchHwSocket('one','On'); sleep(10);}
				if ($hwChargerThreeStatus == 'Off')    { switchHwSocket('three','On');}
			}
			
// Laders 1, 2, & 3 UIT
			if (($chargerUsage <= $chargerWattsIdle || $hwInvReturn != 0 || $hwSolarReturn > $chargerOneUsage) && ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On')){			
				if ($debug == 'yes' && $shortOverride == 0 && $chargeOverride == 0){echo ' --       Laders 1, 2 en 3 UIT.      --'.PHP_EOL;}
				if ($hwChargerTwoStatus == 'On' && $chargeOverride == 0)  { switchHwSocket('two','Off'); sleep(10);}
				if ($hwChargerThreeStatus == 'On' && $chargeOverride == 0){ switchHwSocket('three','Off'); sleep(10);}
				if ($hwChargerOneStatus == 'On' && $chargeOverride == 0)  { switchHwSocket('one','Off');}	
			}

// Print Footer			
			if ($debug == 'yes'){
				if ($shortOverride == 1)  {echo ' --      (Short Override actief)     --'.PHP_EOL;}
				if ($chargeOverride != 0) {echo ' --    (Batterij aan het aftoppen)   --'.PHP_EOL;}
				if ($bmsAwake == 1)       {echo ' --         (BMS wakker maken)       --'.PHP_EOL;}
				if ($faseProtect == 1)    {echo ' --      (Fase bescherming actief)   --'.PHP_EOL;}
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
		
// Lader 1, 2, & 3 AAN
	if ($hwInvReturn == 0 /*&& $batterySOC < 100 */&& $faseProtect == 0 && $bmsAwake == 0 && $chargeOverride == 0){	
		if ($hwChargerOneStatus == 'Off')  { switchHwSocket('one','On'); sleep(15);}
		if ($hwChargerTwoStatus == 'Off')  { switchHwSocket('two','On'); sleep(15);}
		if ($hwChargerThreeStatus == 'Off'){ switchHwSocket('three','On');}
	}
		
// Laders 1, 2 en 3 UIT
	if ($chargerUsage <= $chargerWattsIdle || $hwInvReturn != 0){
		if ($hwChargerOneStatus == 'On')  { switchHwSocket('one','Off'); sleep(1);}						
		if ($hwChargerTwoStatus == 'On')  { switchHwSocket('two','Off'); sleep(1);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off');}	
	}
	
	if ($debug == 'yes'){
		if ($shortOverride == 1)  {echo ' --      (Short Override actief)     --'.PHP_EOL;}
		if ($chargeOverride == 1) {echo ' --    (Batterij aan het aftoppen)   --'.PHP_EOL;}
		if ($bmsAwake == 1)       {echo ' --         (BMS wakker maken)       --'.PHP_EOL;}
	    if ($faseProtect == 1)    {echo ' --      (Fase bescherming actief)   --'.PHP_EOL;}
		echo ' --          Manual Charging         --'.PHP_EOL;
	}
}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                    Start/Stop Charging Off                    //
// **************************************************************//
//                                                               //

	if ($controlSwitch == 'Stop' || $controlSwitch == 'Off'){
		
// Laders 1, 2 en 3 UIT
		if ($hwChargerOneStatus == 'On' || $hwChargerTwoStatus == 'On' || $hwChargerThreeStatus == 'On'){
			if ($hwChargerOneStatus == 'On' && $controlSwitch == 'Stop')   { switchHwSocket('one','Off'); sleep(5);}
			if ($hwChargerTwoStatus == 'On' && $controlSwitch == 'Stop')   { switchHwSocket('two','Off'); sleep(5);}
			if ($hwChargerThreeStatus == 'On' && $controlSwitch == 'Stop') { switchHwSocket('three','Off'); sleep(5);}
		}
	}
	
// Print Footer
	if ($debug == 'yes'){
	echo ' --------------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
	
?>