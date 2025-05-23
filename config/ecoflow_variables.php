<?php

//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                           Variables                           //
//                        No need to edit                        //
// **************************************************************//
//                                                               //
	
// Get Ecoflow status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$invOne = $ecoflow->getDevice($ecoflowOneSerialNumber);
	$invTwo = $ecoflow->getDevice($ecoflowTwoSerialNumber);
	
	if (!$invOne || !isset($invOne['data']['20_1.permanentWatts'])) {
		$ecoflowOneStatus = 0;
	} else {
		$ecoflowOneStatus = 1;
	}

	if (!$invTwo || !isset($invTwo['data']['20_1.permanentWatts'])) {
		$ecoflowTwoStatus = 0;
	} else {
		$ecoflowTwoStatus = 1;
	}
	
// HomeWizard GET Variables
	$hwP1Usage              = getHwData($hwP1IP);
	$hwP1Fase               = getHwP1FaseData($hwP1IP, $fase);
	$hwSolarReturn          = getHwData($hwKwhIP);
	$hwInvOneReturn         = getHwData($hwEcoFlowOneIP);
	$hwInvTwoReturn         = getHwData($hwEcoFlowTwoIP);
	$hwInvReturn            = ($hwInvOneReturn + $hwInvTwoReturn);

	$hwChargerOneUsage      = getHwData($hwChargerOneIP);
	$hwChargerTwoUsage      = getHwData($hwChargerTwoIP);
	$hwChargerThreeUsage    = getHwData($hwChargerThreeIP);
	
	$hwChargerOneStatus     = getHwStatus($hwChargerOneIP);
	$hwChargerTwoStatus     = getHwStatus($hwChargerTwoIP);
	$hwChargerThreeStatus   = getHwStatus($hwChargerThreeIP);
	
	$hwInvOneStatus         = getHwStatus($hwEcoFlowOneIP);
	$hwInvTwoStatus         = getHwStatus($hwEcoFlowTwoIP);

	$hwInvFanStatus         = getHwStatus($hwEcoFlowFanIP);
	
	$hwInvOneTotal          = getHwTotalOutputData($hwEcoFlowOneIP);
	$hwInvTwoTotal          = getHwTotalOutputData($hwEcoFlowTwoIP);
	$hwInvTotal             = ($hwInvOneTotal + $hwInvTwoTotal);
	$hwChargerOneTotal      = getHwTotalInputData($hwChargerOneIP);
	$hwChargerTwoTotal      = getHwTotalInputData($hwChargerTwoIP);
	$hwChargerThreeTotal    = getHwTotalInputData($hwChargerThreeIP);
	
	$hwChargersTotalInput   = ($hwChargerOneTotal + $hwChargerTwoTotal + $hwChargerThreeTotal);
	
// Get battery Voltage via inverter
	$pv1OneInputVolt 		= ($invOne['data']['20_1.pv1InputVolt']) / 10;
	$pv2OneInputVolt 		= ($invOne['data']['20_1.pv2InputVolt']) / 10;
	$pvAvOneInputVoltage    = round(($pv1OneInputVolt + $pv2OneInputVolt) / 2, 2);

	$pv1TwoInputVolt 		= ($invTwo['data']['20_1.pv1InputVolt']) / 10;
	$pv2TwoInputVolt 		= ($invTwo['data']['20_1.pv2InputVolt']) / 10;
	$pvAvTwoInputVoltage    = round(($pv1TwoInputVolt + $pv2TwoInputVolt) / 2, 2);

	//$pvAvInputVoltage       = round(($pvAvOneInputVoltage + $pvAvTwoInputVoltage) / 2, 2);
	$pvAvInputVoltage       = round(($pvAvOneInputVoltage), 2);
	
// Get Inverter output Watts
	$pv1OneInputWatts       = ($invOne['data']['20_1.pv1InputWatts']) / 10;
	$pv2OneInputWatts       = ($invOne['data']['20_1.pv2InputWatts']) / 10;
	$pvAvOneInputWatts      = ($pv1OneInputWatts + $pv2OneInputWatts);

	$pv1TwoInputWatts       = ($invTwo['data']['20_1.pv1InputWatts']) / 10;
	$pv2TwoInputWatts       = ($invTwo['data']['20_1.pv2InputWatts']) / 10;
	$pvAvTwoInputWatts      = ($pv1TwoInputWatts + $pv2TwoInputWatts);

	$pvAvInputWatts         = ($pvAvOneInputWatts + $pvAvTwoInputWatts);
	
// Get Current Baseload
	$currentOneBaseload	    = ($invOne['data']['20_1.permanentWatts']) / 10;
	$currentTwoBaseload	    = ($invTwo['data']['20_1.permanentWatts']) / 10;
	$currentBaseload	    = ($currentOneBaseload + $currentTwoBaseload);

// Set bmsRestoreVoltage
	$bmsRestoreVoltage      = ($bmsMinimumVoltage + 1.2);
	$bmsRestoredVoltage     = ($bmsMinimumVoltage + 1.1);
	
// Get Inverter Temperature
	$invOneTemp             = ($invOne['data']['20_1.llcTemp']) / 10;
	$invTwoTemp             = ($invTwo['data']['20_1.llcTemp']) / 10;
	$invTemp                = ($invOneTemp + $invTwoTemp) / 2;
	
// Determine Power Usage
	$chargerUsage           = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage);
	$productionTotal        = ($hwSolarReturn + $hwInvReturn);	
	$realUsage              = ($hwP1Usage - $productionTotal);
	$P1ChargerUsage         = ($hwP1Usage - $chargerUsage);
	$newInfPVProduction     = abs($hwSolarReturn);
	
	$chargerOneUsage  	    = -abs($chargerOneWatts);
	$chargerTwoUsage  	    = -abs($chargerTwoWatts);	
	$chargerOneTwoUsage  	= -abs($chargerOneWatts + $chargerTwoWatts);
	$chargerThreeUsage  	= -abs($chargerThreeWatts);
	$chargerTotalUsage      = ($chargerOneUsage + $chargerTwoUsage + $chargerThreeUsage);
	$chargerTotalUsageABS   = abs($chargerOneUsage + $chargerTwoUsage + $chargerThreeUsage);
	
// Get Battery Input/Output Total Files
	$batteryInputFile  		= ''.$ecoflowPath.'files/batteryInput.txt';
	$batteryOutputFile      = ''.$ecoflowPath.'files/batteryOutput.txt';
	$chargerStandbyFile     = ''.$ecoflowPath.'files/chargerStandby.txt';
	
	if (file_exists($batteryInputFile)) {
	$batteryInputkWh        = file_get_contents(''.$batteryInputFile.'');
	$batteryInputkWh        = round($batteryInputkWh, 2);
	} else {
	$batteryInputkWh        = 0;
	}
	
	if (file_exists($batteryOutputFile)) {
	$batteryOutputkWh       = file_get_contents(''.$batteryOutputFile.'');
	$batteryOutputkWh       = round($batteryOutputkWh, 2);
	} else {
	$batteryOutputkWh       = 0;
	}

	if (file_exists($chargerStandbyFile)) {
	$chargerStandby         = file_get_contents(''.$chargerStandbyFile.'');
	} else {
	writeFile('Off','Standby');	
	}
	
// Get/Set Battery Charge/Discharge/SOC values
	$batteryCapacity        = round($batteryVolt * $batteryAh / 1000, 2);
	$battVoltAlmostCharged  = round($batteryVolt + 1.0, 2);
	$battVoltCharged        = round($batteryVolt + 1.3, 2);
	$batteryCharged         = round($hwChargersTotalInput - $batteryInputkWh,2);
	$batteryDischarged      = round($hwInvTotal - $batteryOutputkWh,2);
	$batteryAvailable       = round(($batteryCharged / 100 * $chargerEfficiency) - $batteryDischarged,2);
	$batteryMinimumLeft     = round($batteryCapacity / 100 * $batteryMinimum,2);
	$batterySOC		        = round($batteryAvailable / $batteryCapacity * 100, 1);
	$hwInvReturnABS         = round(abs(($hwInvReturn) / 1000), 2);
	$batteryInputCal        = round(($hwChargersTotalInput) - $batteryCapacity / $chargerEfficiency * 100,2);
	$batteryOutputCal       = round($batteryOutputkWh - $batteryAvailable,2);

// php.ini
	date_default_timezone_set(''.$timezone.'');

// Time/Date now
	$timeNow = date('H:i');
	$dateNow = date('Y-m-d H:i:s');
	$dateTime = new DateTime(''.$dateNow.'', new DateTimeZone(''.$timezone.''));
	
// URLs
	$baseUrl = 'http://'.$domoticzIP.'/json.htm?type=command&param=getdevices&rid=';
	$urls    = [
	'heaterWcdIDX'    => $baseUrl . $heaterWcdIDX,
	'quookerWcdIDX'   => $baseUrl . $quookerWcdIDX,
	'aanrecht1WcdIDX' => $baseUrl . $aanrecht1WcdIDX,
	'aanrecht2WcdIDX' => $baseUrl . $aanrecht2WcdIDX,
	'natalyaWcdIDX'   => $baseUrl . $natalyaWcdIDX,
	];

	$baseLocalUrl = 'http://127.0.0.1:8080/json.htm?type=command&param=getdevices&rid=';
	$urlsLocal    = ['controlSwitch' => $baseLocalUrl . $controlSwitchIDX];
	
// Get Domoticz devices Usage
	$heaterWatts_data 	  = json_decode(file_get_contents($urls['heaterWcdIDX']), true);
	$heaterWatts 	  	  = intval($heaterWatts_data['result'][0]['Data'] ?? 0);

	$quookerWatts_data 	  = json_decode(file_get_contents($urls['quookerWcdIDX']), true);
	$quookerWatts 	  	  = intval($quookerWatts_data['result'][0]['Data'] ?? 0);
	
	$aanrecht1Watts_data  = json_decode(file_get_contents($urls['aanrecht1WcdIDX']), true);
	$aanrecht1Watts 	  = intval($aanrecht1Watts_data['result'][0]['Data'] ?? 0);
	
	$aanrecht2Watts_data  = json_decode(file_get_contents($urls['aanrecht2WcdIDX']), true);
	$aanrecht2Watts 	  = intval($aanrecht2Watts_data['result'][0]['Data'] ?? 0);

	$natalyaWatts_data 	  = json_decode(file_get_contents($urls['natalyaWcdIDX']), true);
	$natalyaWatts 	  	  = intval($natalyaWatts_data['result'][0]['Data'] ?? 0);
	
	$control_data         = json_decode(file_get_contents($urlsLocal['controlSwitch']), true);
	$controlSwitch        = $control_data['result'][0]['Data'];
	
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
	if ($hwInvReturn < 0 && $batterySOC >= $batteryMinimum){
	$hwInvReturnABS = abs($hwInvReturn) / 1000 ;	
	$disChargeTimeRemaining = round(($batteryAvailable - $batteryMinimumLeft) / $hwInvReturnABS, 3);
	} elseif ($hwInvReturn >= 0){
	$disChargeTimeRemaining = 0;	
	}
	
	$realChargeTime    = convertTime($chargeTimeRemaining);
	$realDischargeTime = convertTime($disChargeTimeRemaining);
	
// Calibrate Charge kWh values?		
	if (file_exists($batteryInputFile) && file_exists($batteryOutputFile)){
		
		if(($batterySOC > 100 || $pvAvOneInputVoltage >= $battVoltCharged) && ($chargerUsage == 0 && $batteryInputkWh != $batteryInputCal)){
			writeBattInputOutput(''.$batteryInputCal.'','Input');
		sleep(1);
			writeBattInputOutput(''.$hwInvTotal.'','Output');
		sleep(1);
			writeFile('Off','Standby');	
		}

		if($batterySOC > $batteryMinimum && $pvAvOneInputVoltage <= ($bmsMinimumVoltage + 0.5) && $chargerUsage == 0 && $hwInvReturn == 0 && $batteryOutputkWh != $batteryOutputCal){
			writeBattInputOutput(''.$batteryOutputCal.'','Output');
		}
	}

// Chargers Standby
	if(($batterySOC <= 90 && $chargerStandby == 'Off')){
		writeFile('On','Standby');	
	}
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                  Fase powerusage protection                   //
// **************************************************************//
//                                                               //

	if ($hwP1Fase >= $maxFaseWatts || $heaterWatts > 1500){
		if ($hwChargerOneStatus == 'On'){ switchHwSocket('one','Off'); sleep(1);}
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(1);}
		if ($hwChargerThreeStatus == 'On'){ switchHwSocket('three','Off'); sleep(1);}
		$faseProtect = 1;
	
	} else {
		
		if ($hwP1Fase <= $maxFaseWatts && $heaterWatts < 1500){
		$faseProtect = 0;
		} else {
		$faseProtect = 0;
		}
	}
	
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                       Keep BMS Awake                          //
// **************************************************************//
//                                                               //  

	if ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'Off' && $hwChargerThreeStatus == 'Off' && $pvAvInputVoltage > 0 && $pvAvInputVoltage <= $bmsMinimumVoltage && $hwInvReturn == 0) {
		$bmsAwake = 1;
		if ($hwChargerOneStatus == 'Off'){ switchHwSocket('one','On'); sleep(5);}
		if ($hwChargerTwoStatus == 'On'){ switchHwSocket('two','Off'); sleep(5);}
		if ($hwChargerThreeStatus == 'Off'){ switchHwSocket('three','On'); sleep(5);}
		
	} elseif ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'On' && $hwChargerThreeStatus == 'On' && $pvAvInputVoltage > $bmsMinimumVoltage && $pvAvInputVoltage < $bmsRestoreVoltage) {
		$bmsAwake = 1;
	} elseif ($keepBMSalive == 'yes' && $hwChargerOneStatus == 'On' && $hwChargerThreeStatus == 'On' && $pvAvInputVoltage >= $bmsRestoreVoltage) {
		$bmsAwake = 0;
	} else {
		$bmsAwake = 0;
	}

//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                Charge Override (Almost Full)                  //
// **************************************************************//
//                                                               //

	if ($pvAvInputVoltage >= $battVoltCharged && $chargerUsage > $chargerWattsIdle) {
	$chargeOverride = 1;
	} elseif ($pvAvInputVoltage >= $battVoltCharged && $chargerUsage <= $chargerWattsIdle) {
	$chargeOverride = 0;
	} elseif ($pvAvInputVoltage < $battVoltCharged && $chargerUsage <= $chargerWattsIdle) {
	$chargeOverride = 0;
	} elseif ($pvAvInputVoltage < $battVoltCharged && $chargerUsage > $chargerWattsIdle) {
	$chargeOverride = 0;
	}
	
//															     //
// **************************************************************//
//        EcoFlow LiFePo4 12/12/20a Homebattery Charging         //
//                       Short Override                          //
// **************************************************************//
//                                                               //

// ShortOverride (Voorkom flip/flops door devices die maar even een kort hoge verbruik piek hebben)
	if ($heaterWatts <= 50 && $quookerWatts <= 50 && $aanrecht1Watts <= 100 && $aanrecht2Watts <= 100 && $natalyaWatts <= 500){	
	$shortOverride = 0;
	} elseif (($heaterWatts > 50 || $quookerWatts > 50 || $aanrecht1Watts > 100 || $aanrecht2Watts > 100 || $natalyaWatts > 500) && ($hwSolarReturn <= $chargerOneUsage || $hwInvReturn == 0)) {
	$shortOverride = 1;
	} elseif (($heaterWatts > 50 || $quookerWatts > 50 || $aanrecht1Watts > 100 || $aanrecht2Watts > 100 || $natalyaWatts > 500) && ($hwSolarReturn > $chargerOneUsage || $hwInvReturn != 0)) {
	$shortOverride = 0;
	}
	
	if ($faseProtect == 1 || $hwP1Usage > $chargerOneTwoWatts){
	$shortOverride = 0;	
	}	

?>