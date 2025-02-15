<?php
//															     //
// **************************************************************//
//          EcoFlow micro-inverter automatische baseload         //
//                       Setup Variables                         //
// **************************************************************//
//                                                               //

// Debug?
	$debug				    = 'yes';							 // Waarde 'yes' of 'no'

// Tijd variables
	$invStartTime		    = '00:00';							 // Omvormer starttijd (bij $runInfinity == 'no')
	$invEndTime			    = '13:00';							 // Omvormer eindtijd (bij $runInfinity == 'no')
	$runInfinity		    = 'auto';		    				 // Waarde 'day', 'night', 'dark', 'yes', 'no' bij 'yes' zal de omvormer indien mogelijk en afhankelijk van de instellingen altijd blijven opwekken
	
// Lokatie variables
	$latitude               = '00.00000';						 // Latitude is de afstand in graden 'Noord' of 'Zuid' tot de evenaar
	$longitude              = '-0.000000';						 // Longitude is de afstand in graden 'Oost' of 'West' tot de Meridiaan in Greenwich
	$zenitLat               = '89.5';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$zenitLong              = '91.7';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$timezone               = 'Europe/Amsterdam';			     // Mijn php.ini slikt de timezone niet dus dan maar handmatig instelling
				
// Omvormer variables
	$ecoflowMaxOutput	    = 1200;								 // Maximale teruglevering (Watts) wat de omvormer kan/mag leveren. 
	$ecoflowMinOutput	    = 100;								 // Minimale teruglevering (Watts) Onder dit getal (Watt) zal de omvormer niet terugleveren. 
	$ecoflowOutputOffSet    = 15;								 // Trek deze value (watts) af van de nieuwe baseload, Deze value wordt alsnog van het net wordt getrokken om teruglevering te voorkomen
	$ecoflowMaxInvTemp      = 65;								 // Maximale interne temperatuur, daarboven stopt de omvormer met terugleveren 

// Batterij variables
	$batteryVolt		    = 25.6;								 // Voltage van de batterij
	$batteryAh              = 300;                               // Totale Ah van alle batterijen
	$chargerEfficiency      = 79.7;                              // Lader laad efficientie
	$batteryMinimum		    = 5;                                 // Minimale procenten die in de batterij moeten blijven
	
// Homewizard variables
	$hwP1IP				    = '192.168.178.0';					 // IP Homewizard P1 Meter
	$hwKwhIP			    = '192.168.178.0';					 // IP Homewizard Solar kwh Meter
	$hwEcoFlowOneIP		    = '192.168.178.0';					 // IP Homewizard EcoFlow One socket
	$hwEcoFlowTwoIP		    = '192.168.178.0';					 // IP Homewizard EcoFlow Two socket
	$hwChargerOneIP 	    = '192.168.178.0';     			     // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 	    = '192.168.178.0';     			     // IP Homewizard Charger TWO 600w socket
	$hwChargerThreeIP 	    = '192.168.178.0';     			     // IP Homewizard Charger THREE 300w socket
	
// Lader variables
	$chargerOneWatts	    = 350;								 // Verbruik van Lader 1 (Watt)
	$chargerTwoWatts	    = 600;								 // Verbruik van Lader 2 (Watt)
	$chargerThreeWatts      = 350;								 // Verbruik van Lader 3 (Watt)
	$chargerWattsIdle	    =  14;								 // Standby Watts van alle laders wanneer batterijen vol zijn
	$chargerOffSet			= 300;
	
// Fase protection
	$faseProtection		    = 'yes';                             // Waarde 'yes' of 'no'
	$maxFaseWatts		    = 4500;                              // Bij verbruik op de Fase hoger dan aangegeven Watts zullen alle laders uitschakelen om de maximale belasting van de Fase niet te overschrijden
	$fase				    = 1;                                 // Welke Fase moet beschermd worden
	
// Battery BMS variables
	$keepBMSalive		    = 'yes';                             // Indien batterij is leeg getrokken zal er een beetje bij geladen worden om de BMS wakker te houden
	$bmsMinimumVoltage      = 22.1;                              // Minimale Voltage van de batterij (volgens de EcoFlow app), De batterij zal een beetje bijladen om de BMS wakker te houden

// Domoticz variables
	$domoticzIP			    = '192.168.168.168:8080'; 	    	 // IP + poort van Domoticz
	$heaterWcdIDX           = '0';
	$quookerWcdIDX			= '0';
	$aanrecht1WcdIDX		= '0';
	$aanrecht2WcdIDX	    = '0';
	$natalyaWcdIDX	        = '0';
	$controlSwitchIDX       = '0';
	$baseloadSwitchIDX      = '0';
	$afzuigkapWcdIDX        = '0';
	
// Ecoflow Powerstream API variables
	$ecoflowPath		    = '/path/2files/';	                 // Path waar je scripts zich bevinden
	$ecoflowAccessKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API access key
	$ecoflowSecretKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API secret key
	$ecoflowOneSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream One serialnummer
	$ecoflowTwoSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream Two serialnummer
?>