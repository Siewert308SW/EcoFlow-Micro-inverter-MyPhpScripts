<?php
//															 //
// **********************************************************//
//         EcoFlow micro-inverter GET Serial_Number          //
// **********************************************************//
//                                                           //

// Debug?
	$debug					= 'no';								     // Waarde 'yes' of 'no'.

// Omvormer variables
	$keepSomeCapacity 		= 'yes'; 								 // waarde 'yes' of 'no'. Bij 'yes' blijft onderstaande voltage in de batterij over, bij 'no' wordt de accu leeggetrokken, totdat BMS in werking treedt 
	$minBatteryVoltage 		= 225; 								     // Minimale Voltage wat in de accu moet blijven in hondertal.

// Ecoflow Powerstream API variables
	$ecoflowPath 			= '/path/to/files/';					 // Path waar de scripts zich bevinden
	$ecoflowAccessKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	 // Powerstream API access key
	$ecoflowSecretKey		= 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';	 // Powerstream API secret key
	$ecoflowSerialNumber 	= 'HWXXXXXXXXXXXXX';				     // Powerstream serie nummer

//															 //
// **********************************************************//
//           EcoFlow micro-inverter start script             //
// **********************************************************//
//                                                           //

	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
	echo ' --  EcoFlow Get Serial_Number   --'.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
    echo ' '.PHP_EOL;
	}
	
// Path naar EcoFlow API class file
	require_once(''.$ecoflowPath.'ecoflow-api-class.php');

// Set EcoFlow Micro-Inverter serial_number
	define('SERIAL_NUMBER', ''.$ecoflowSerialNumber.'');

// Set EcoFlow Micro-Inverter keys
	$ecoflow = new EcoFlowAPI( ''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'' );

// Get gegevens van EcoFlow Micro-Inverter
	$device = $ecoflow->getDevice(SERIAL_NUMBER);

// Check EcoFlow Micro-Inverter gegevens
	if (isset($device['data']['20_1.pv1InputVolt'], $device['data']['20_1.pv2InputVolt'])) {
		$pv1InputVolt = $device['data']['20_1.pv1InputVolt'];
		$pv2InputVolt = $device['data']['20_1.pv2InputVolt'];
		$pvAvInputVoltAverage = ($pv1InputVolt + $pv2InputVolt) / 2;
		$pvAvInputVoltRounded = ($pvAvInputVoltAverage) / 10;
		$minBatteryVoltage1 = ($minBatteryVoltage) / 10; 
	} else {
		if ($debug == 'yes'){
		echo ' -- ERROR: Kan gegevens van de omvormer niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo ' ----------------------------------'.PHP_EOL;
		}
		exit(1);
	}

	$pvAvInputVolt = ($pv1InputVolt + $pv2InputVolt) / 2;
	$statusInverter = $ecoflow->getDeviceOnline(SERIAL_NUMBER);
	
// Print
	if ($debug == 'yes'){
	echo '-/- Batterij        -\-'.PHP_EOL;
	echo ' -- Batterij Voltage : '.$pvAvInputVoltRounded.'v'.PHP_EOL;
		if ($keepSomeCapacity == 'no') {
		echo ' -- Batterij mag leeg getrokken worden'.PHP_EOL;
		} else {
		echo ' -- Batterij wordt leeg getrokken tot '.$minBatteryVoltage1.'v'.PHP_EOL;		
		}
	echo ' '.PHP_EOL;
	}
	
// Bepaal EcoFlow Micro-Inverter serial_number
	$serial_number = '';

	if ($keepSomeCapacity == 'no') {
		if ($pvAvInputVolt >= $minBatteryVoltage) {
		$serial_number = SERIAL_NUMBER;
			if ($debug == 'yes'){
			echo '-/- Serial_Number   -\-'.PHP_EOL;
			echo ' -- SN Nummer        : '.$serial_number.''.PHP_EOL;
			}
		}
	} elseif ($statusInverter == 0) {
		$serial_number = SERIAL_NUMBER;
			if ($debug == 'yes'){
			echo '-/- Serial_Number   -\-'.PHP_EOL;
			echo ' -- SN Nummer        : '.$serial_number.''.PHP_EOL;
			}
	} else {
		if ($statusInverter == 1) {
		$serial_number = SERIAL_NUMBER;
			if ($debug == 'yes'){
			echo '-/- Serial_Number   -\-'.PHP_EOL;
			echo ' -- SN Nummer        : '.$serial_number.''.PHP_EOL;
			}
		}
	}

// Schrijf EcoFlow Micro-Inverter serial_number
	function writeSerial($serial)
	{
		global $ecoflowPath;
		$filePath = ''.$ecoflowPath.'serialnumber.txt';
		$file = fopen($filePath, 'w');
		if ($file === false) {		if ($debug == 'yes'){
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan het serialnumber.txt bestand niet openen!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' ----------------------------------'.PHP_EOL;
			}
		}
		exit(1);
		}
		fwrite($file, $serial);
		fclose($file);
	}

	writeSerial($serial_number);
	
// Print	
	if ($debug == 'yes'){
	echo ' '.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
	echo ' --           The End            --'.PHP_EOL;
	echo ' ----------------------------------'.PHP_EOL;
	echo ' '.PHP_EOL;
	}
?>