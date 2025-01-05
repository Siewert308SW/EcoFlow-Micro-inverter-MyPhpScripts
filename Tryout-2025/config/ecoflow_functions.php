<?php
//															     //
// **************************************************************//
//           EcoFlow micro-inverter automatic baseload           //
//                           Functions                           //
//                        No need to edit                        //
// **************************************************************//
//                                                               //

// Get Ecoflow status
	$ecoflow = new EcoFlowAPI(''.$ecoflowAccessKey.'', ''.$ecoflowSecretKey.'');
	$inv = $ecoflow->getDevice($ecoflowSerialNumber);
	
	if (!$inv || !isset($inv['data']['20_1.permanentWatts'])) {
		if ($debug == 'yes'){
		echo '  -- ERROR: Kan EcoFlow inverter gegevens niet ophalen!'.PHP_EOL;
		echo ' '.PHP_EOL;
		echo '  --------------------------------------'.PHP_EOL;
		}
		exit(1);
	} 
	
// Function GET HomeWizard data
	function getHwData($ip) {
		global $debug;
		$hwData = curl_init();
		curl_setopt($hwData, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($hwData, CURLOPT_RETURNTRANSFER, true);
		$hwDataResult = curl_exec($hwData);

		if (curl_errno($hwData)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
			$hwDataDecode  = json_decode($hwDataResult);
			$hwDataDecoded = round($hwDataDecode->active_power_w);
			return $hwDataDecoded;
			curl_close($hwData);
		}
	}

// Function GET HomeWizard (energy-socket) status
	function getHwStatus($ip) {
		global $debug;
		$hwChargerStatus = curl_init();
		curl_setopt($hwChargerStatus, CURLOPT_URL, "http://".$ip."/api/v1/state");
		curl_setopt($hwChargerStatus, CURLOPT_RETURNTRANSFER, true);
		$hwChargerStatusResult = curl_exec($hwChargerStatus);

		if (curl_errno($hwChargerStatus)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
		  $hwChargerStatusDecode = json_decode($hwChargerStatusResult);
		  $hwChargerStatus  = abs($hwChargerStatusDecode->power_on);
		  
		  if ($hwChargerStatus == 1){
			  $hwChargerStatus  = 'On';
		  } else {
			$hwChargerStatus  = 'Off';  
		  }
		  return $hwChargerStatus;
		  curl_close($hwChargerStatus);	  
		}
	}
	
// Function Switch HomeWizard (energy-socket) status
	function switchHwSocket($energySocket,$cmd) {
		global $debug;
		global $hwChargerOneIP;
		global $hwChargerTwoIP;
		global $hwChargerThreeIP;
		global $hwEcoFlowIP;
		
		$socket = curl_init();
		if ($energySocket == 'two') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerTwoIP.'/api/v1/state');			
		} elseif ($energySocket == 'one') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerOneIP.'/api/v1/state');
		} elseif ($energySocket == 'three') {
		curl_setopt($socket, CURLOPT_URL, 'http://'.$hwChargerThreeIP.'/api/v1/state');
		}
		curl_setopt($socket, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($socket, CURLOPT_CUSTOMREQUEST, 'PUT');
		curl_setopt($socket, CURLOPT_HTTPHEADER, [
			'Content-Type: application/x-www-form-urlencoded',
		]);
		
		if ($cmd == 'On') {
			$cmd = 'true';
		} elseif ($cmd == 'Off') {
			$cmd = 'false';
		}
		
		curl_setopt($socket, CURLOPT_POSTFIELDS, '{"power_on": '.$cmd.'}');
		
		$response = curl_exec($socket);

		curl_close($socket);
	}
	
// Function GET HomeWizard Total Input Data
	function getHwTotalInputData($ip) {
		global $debug;
		$hwData = curl_init();
		curl_setopt($hwData, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($hwData, CURLOPT_RETURNTRANSFER, true);
		$hwDataResult = curl_exec($hwData);

		if (curl_errno($hwData)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
			$hwDataDecode  = json_decode($hwDataResult);
			$hwDataDecoded = round($hwDataDecode->total_power_import_kwh, 3);
			return $hwDataDecoded;
			curl_close($hwData);
		}
	}
	
// Function GET HomeWizard Total Output Data
	function getHwTotalOutputData($ip) {
		global $debug;
		$hwData = curl_init();
		curl_setopt($hwData, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($hwData, CURLOPT_RETURNTRANSFER, true);
		$hwDataResult = curl_exec($hwData);

		if (curl_errno($hwData)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			
			$hwDataDecode  = json_decode($hwDataResult);
			$hwDataDecoded = round($hwDataDecode->total_power_export_kwh, 3);
			return $hwDataDecoded;
			curl_close($hwData);
		}
	}

// Function GET HomeWizard P1 fase data
	function getHwP1FaseData($ip,$fase) {
		global $debug;
		$hwP1FaseData = curl_init();
		curl_setopt($hwP1FaseData, CURLOPT_URL, "http://".$ip."/api/v1/data");
		curl_setopt($hwP1FaseData, CURLOPT_RETURNTRANSFER, true);
		$hwP1FaseDataResult = curl_exec($hwP1FaseData);

		if (curl_errno($hwP1FaseData)) { 
			if ($debug == 'yes'){
			echo ' -- ERROR: Kan geen gegevens op halen van Homewizard: '.$ip.'!'.PHP_EOL;
			echo ' '.PHP_EOL;
			echo ' --------------------------------------'.PHP_EOL;
			}
			exit(0);	

		} else {
			$hwP1FaseDataDecode  = json_decode($hwP1FaseDataResult);
			if ($fase == 1){			
			$hwP1FaseDataDecoded = round($hwP1FaseDataDecode->active_power_l1_w, 3);
			} elseif ($fase == 2){
			$hwP1FaseDataDecoded = round($hwP1FaseDataDecode->active_power_l2_w, 3);				
			} else if ($fase == 3){
			$hwP1FaseDataDecoded = round($hwP1FaseDataDecode->active_power_l3_w, 3);				
			}
			return $hwP1FaseDataDecoded;
			curl_close($hwP1FaseData);
		}
	}
	
// Function Write batteryInputOutput.txt
	function writeBattInputOutput($value,$file)
	{
		global $ecoflowPath;

		if ($file == 'Input'){
		$filePath = ''.$ecoflowPath.'files/batteryInput.txt';
		} elseif ($file == 'Output'){
		$filePath = ''.$ecoflowPath.'files/batteryOutput.txt';
		} else {
		die("Unable to write file!");
		}
		
		$file = fopen($filePath, "w");
		if ($file === false) {
			die("Unable to open file!");
		}
		fwrite($file, $value);
		fclose($file);
	}
	
// Function to convert time in decimals to realTime
	function convertTime($dec)
	{
		$seconds = ($dec * 3600);
		$hours = floor($dec);
		$seconds -= $hours * 3600;
		$minutes = floor($seconds / 60);
		$seconds -= $minutes * 60;
		return lz($hours).":".lz($minutes)."";
	}

// lz = leading zero
	function lz($num)
	{
		return (strlen($num) < 2) ? "0{$num}" : $num;
	}
?>