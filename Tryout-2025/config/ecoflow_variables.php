<?php

//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                           Variables                           //
// **************************************************************//
//                                                               //
	
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

// Get Sunrise/Sunset
	$sunrise              = (date_sunrise(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLat,$gmt));
	$sunset               = (date_sunset(time(),SUNFUNCS_RET_STRING,$latitude,$longitude,$zenitLong,$gmt));

// Get Ecoflow status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$inv = $ecoflow->getDevice($ecoflowSerialNumber);
	
	if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
		$ecoflowStatus = 0;

	} else {
		$ecoflowStatus = 1;
	}

// HomeWizard GET Variables
	$hwP1Usage              = getHwData($hwP1IP);
	$hwP1Fase               = getHwP1FaseData($hwP1IP, $fase);
	$hwSolarReturn          = getHwData($hwKwhIP);
	$hwInvReturn            = getHwData($hwEcoFlowIP);
	$hwChargerOneUsage      = getHwData($hwChargerOneIP);
	$hwChargerTwoUsage      = getHwData($hwChargerTwoIP);
	$hwChargerThreeUsage    = getHwData($hwChargerThreeIP);
	$hwChargerOneStatus     = getHwStatus($hwChargerOneIP);
	$hwChargerTwoStatus     = getHwStatus($hwChargerTwoIP);
	$hwChargerThreeStatus   = getHwStatus($hwChargerThreeIP);

	$hwInvTotal             = getHwTotalOutputData($hwEcoFlowIP);
	$hwChargerOneTotal      = getHwTotalInputData($hwChargerOneIP);
	$hwChargerTwoTotal      = getHwTotalInputData($hwChargerTwoIP);
	$hwChargerThreeTotal    = getHwTotalInputData($hwChargerThreeIP);
	$hwChargersTotalInput   = ($hwChargerOneTotal + $hwChargerTwoTotal + $hwChargerThreeTotal);
	
// SET/GET Usage Variables	
	$productionTotal        = ($hwSolarReturn + $hwInvReturn);
	$realUsage              = ($hwP1Usage - $productionTotal);
	$newInfPVProduction     = abs($hwSolarReturn);
	
// Get battery Voltage
	$pv1InputVolt 		    = ($inv['data']['20_1.pv1InputVolt']) / 10;
	$pv2InputVolt 		    = ($inv['data']['20_1.pv2InputVolt']) / 10;
	$pvAvInputVoltage       = round(($pv1InputVolt + $pv2InputVolt) / 2, 2);
	
// Get Inverter output Watts
	$pv1InputWatts          = ($inv['data']['20_1.pv1InputWatts']) / 10;
	$pv2InputWatts          = ($inv['data']['20_1.pv2InputWatts']) / 10;
	$pvAvInputWatts         = ($pv1InputWatts + $pv2InputWatts);

// Get Current Baseload
	$currentBaseload	    = ($inv['data']['20_1.permanentWatts']) / 10;

// Set bmsMaximumVoltage
	$bmsMaximumVoltage      = ($bmsMinimumVoltage + 1);
	
// Get Inverter Temperature
	$invTemp                = ($inv['data']['20_1.llcTemp']) / 10;

// Determine Power Usage
	$chargerUsage           = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage);
	$productionTotal        = ($hwSolarReturn + $hwInvReturn);
	$realUsage              = ($hwP1Usage - $productionTotal);
	$P1ChargerUsage         = ($hwP1Usage - $chargerUsage);

	$chargerOneUsage  	    = -abs($chargerOneUsage);
	$chargerTwoUsage  	    = -abs($chargerTwoUsage);
	$chargerOneTwoUsage     = -abs($chargerOneUsage + $chargerTwoUsage); 
	$chargerThreeUsage      = -abs($chargerThreeUsage);
	$chargerTotalUsage      = ($chargerOneUsage + $chargerTwoUsage + $chargerThreeUsage);

// Determine total charger usage
	$chargerUsage           = ($hwChargerOneUsage + $hwChargerTwoUsage + $hwChargerThreeUsage);
	
// Get Battery Input/Output Total Files
	$batteryInputFile  		= ''.$ecoflowPath.'batteryInput.txt';
	$batteryOutputFile      = ''.$ecoflowPath.'batteryOutput.txt';
	
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
	
// Get/Set Battery Charge/Discharge/SOC values
	$batteryCapacity        = round($batteryVolt * $batteryAh / 1000, 2);
	$batteryCharged         = round($hwChargersTotalInput - $batteryInputkWh,2);
	$batteryDischarged      = round($hwInvTotal - $batteryOutputkWh,2);
	$batteryAvailable       = round(($batteryCharged / 100 * $chargerEfficiency) - $batteryDischarged,2);
	$batteryMinimumLeft     = round($batteryCapacity / 100 * $batteryMinimum,2);
	$batterySOC		        = round($batteryAvailable / $batteryCapacity * 100, 1);
	$hwInvReturnABS         = round(abs(($hwInvReturn) / 1000), 2);
	$batteryInputCal        = round(($hwChargersTotalInput) - $batteryCapacity / $chargerEfficiency * 100,2);
	$batteryOutputCal       = round($hwInvTotal - $batteryCapacity,2);
	
?>