<?php
//															     //
// **************************************************************//
//       EcoFlow EcoFlow micro-inverter custom cooling fans      //
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
//       EcoFlow EcoFlow micro-inverter custom cooling fans      //
//                          Fan On/Off                           //
// **************************************************************//
//                                                               //

	if ($hwInvFanStatus == 'Off' && $invTemp >= 35){
		switchHwSocket('fan','On');
	} elseif ($hwInvFanStatus == 'On' && $invTemp < 30 && $hwInvReturn == 0){
		switchHwSocket('fan','Off');
	}
	
?>