<?php
// Require Ecoflow Class files
	$files = glob(__DIR__ . '/config/*.php');
	foreach ($files as $file) {
		if ($file != __FILE__) {
			require($file);
		}
	}
	
//															     //
// **************************************************************//
//                      EcoFlow 2 Domoticz                       //
//                          Variables                            //
// **************************************************************//
//                                                               //

// Domoticz variables
	$domoticzIP			          = '127.0.0.1:8080'; 	    	        // IP + poort van Domoticz
	$batteryVoltageIDX 			  = '41'; 						     
	$ecoFlowTempIDX 		      = '50'; 
	$batterySOCIDX 				  = '64';
	$pvCounterIDX 	              = '59';

	$batteryChargeTimeIDX         = '66';
	$batteryDischargeTimeIDX      = '67';
	$batteryAvailIDX              = '68';
	
	$inputCounterIDX 	          = '60';
	$outputCounterIDX 	          = '58';
	
	//$outputIDX 				  = '51';
	
// URLs
	$baseUrl = 'http://'.$domoticzIP.'/json.htm?type=command&param=getdevices&rid=';
	$urls = [	
		'batteryVoltageIDX'       => $baseUrl . $batteryVoltageIDX,	
		'ecoFlowTempIDX'          => $baseUrl . $ecoFlowTempIDX,
		'batterySOCIDX'           => $baseUrl . $batterySOCIDX,
		'batteryAvailIDX'         => $baseUrl . $batteryAvailIDX,
	    'batteryChargeTimeIDX'    => $baseUrl . $batteryChargeTimeIDX,
		'batteryDischargeTimeIDX' => $baseUrl . $batteryDischargeTimeIDX,
		'pvCounter'               => $baseUrl . $pvCounterIDX,
		'outputCounterIDX'        => $baseUrl . $outputCounterIDX,
		'inputCounterIDX'         => $baseUrl . $inputCounterIDX,
	];  
	
//															     //
// **************************************************************//
//                      EcoFlow 2 Domoticz                       //
//                  Functions & Get/Set Data                     //
// **************************************************************//
//                                                               //

// Function Update Domoticz Device
	function UpdateDomoticzDevice($idx,$cmd) {
	  global $domoticzIP;
	  global $batterySOCIDX;
	  global $ecoFlowTempIDX;
	  global $batteryVoltageIDX;
	  global $batteryChargeTimeIDX;
	  global $batteryDischargeTimeIDX;
	  global $batteryAvailIDX;
	  global $pvCounterIDX;
	  global $inputCounterIDX;
	  global $outputCounterIDX;
	  
	  if ($idx == $inputCounterIDX || $idx == $outputCounterIDX || $idx == $batterySOCIDX || $idx == $ecoFlowTempIDX || $idx == $batteryVoltageIDX || $idx == $pvCounterIDX){
	  $reply=json_decode(file_get_contents('http://'.$domoticzIP.'/json.htm?type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$cmd.';0'),true);
	  }
	  
	  if ($idx == $batteryChargeTimeIDX || $idx == $batteryDischargeTimeIDX || $idx == $batteryAvailIDX){
	  $reply=json_decode(file_get_contents('http://'.$domoticzIP.'/json.htm?type=command&param=udevice&idx='.$idx.'&nvalue=0&svalue='.$cmd.''),true);
	  }
	  
	  if($reply['status']=='OK') $reply='OK';else $reply='ERROR';
	  return $reply;
	}
	
//															     //
// **************************************************************//
//                      EcoFlow 2 Domoticz                       //
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
	$disChargeTimeRemaining = round(($batteryAvailable - $batteryMinimumLeft) / $hwInvReturnABS, 2);
	} elseif ($hwInvReturn >= 0){
	$disChargeTimeRemaining = 0;	
	}
	
	$realChargeTime         = convertTime($chargeTimeRemaining);
	$realDischargeTime      = convertTime($disChargeTimeRemaining);
	
//															     //
// **************************************************************//
//                      EcoFlow 2 Domoticz                       //
//                          PushUpdate                           //
// **************************************************************//
//                                                               //

	UpdateDomoticzDevice($ecoFlowTempIDX, ''.$invTemp.'');
	sleep(0.1);	
	UpdateDomoticzDevice($batteryVoltageIDX, ''.$pvAvInputVoltage.'');
	sleep(0.1);	
	UpdateDomoticzDevice($batterySOCIDX, ''.$batterySOC.'');
	sleep(0.1);		
	UpdateDomoticzDevice($pvCounterIDX, ''.$hwSolarReturn.'');
	sleep(0.1);	
	UpdateDomoticzDevice($batteryAvailIDX, ''.$batteryAvailable.'');
	sleep(0.1);	
	UpdateDomoticzDevice($batteryChargeTimeIDX, ''.$realChargeTime.'');
	sleep(0.1);		
	UpdateDomoticzDevice($batteryDischargeTimeIDX, ''.$realDischargeTime.'');
	sleep(0.1);		
	UpdateDomoticzDevice($inputCounterIDX, ''.$chargerUsage.'');
	sleep(0.1);		
	UpdateDomoticzDevice($outputCounterIDX, ''.$hwInvReturn.'');
?>