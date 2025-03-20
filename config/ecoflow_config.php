<?php
//															     //
// **************************************************************//
//          EcoFlow micro-inverter automatische baseload         //
//                       Setup Variables                         //
// **************************************************************//
//                                                               //

// Debug?
	$debug				    = 'yes';							 // Waarde 'yes' of 'no'

// Schakeltijd variables
	$invStartTime		    = '00:00';							 // Omvormer starttijd (bij $runInfinity == 'no')
	$invEndTime			    = '13:00';							 // Omvormer eindtijd (bij $runInfinity == 'no')
	$runInfinity		    = 'yes';		    				 // Waarde 'yes', 'no' bij 'yes' zal de omvormer indien mogelijk en afhankelijk van de instellingen altijd blijven opwekken
	$timezone               = 'Europe/Amsterdam';			     // Mijn php.ini slikt de timezone niet dus dan maar handmatig instelling
				
// Omvormer variables
	$ecoflowMaxOutput	    = 1150;								 // Maximale teruglevering (Watts) wat de omvormer kan/mag leveren. 
	$ecoflowMinOutput	    = 100;								 // Minimale teruglevering (Watts) Onder dit getal (Watt) zal de omvormer niet terugleveren. 
	$ecoflowOutputOffSet    = 20;								 // Trek deze value (watts) af van de nieuwe baseload, Deze value wordt alsnog van het net wordt getrokken om teruglevering te voorkomen
	$ecoflowMaxInvTemp      = 65;								 // Maximale interne temperatuur, daarboven stopt de omvormer met terugleveren 

// Batterij variables
	$batteryVolt		    = 25.6;								 // Voltage van de batterij
	$batteryAh              = 300;                               // Totale Ah van alle batterijen
	$chargerEfficiency      = 79.0;                              // Lader laad efficientie
	$batteryMinimum		    = 10;                                // Minimale procenten die in de batterij moeten blijven
	
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
	$chargerWattsIdle	    =  25;								 // Standby Watts van alle laders wanneer batterijen vol zijn

// Fase protection
	$faseProtection		    = 'yes';                             // Waarde 'yes' of 'no'
	$maxFaseWatts		    = 4500;                              // Bij verbruik op de Fase hoger dan aangegeven Watts zullen alle laders uitschakelen om de maximale belasting van de Fase niet te overschrijden
	$fase				    = 1;                                 // Welke Fase moet beschermd worden
	
// Battery BMS variables
	$keepBMSalive		    = 'yes';                             // Indien batterij is leeg getrokken zal er een beetje bij geladen worden om de BMS wakker te houden
	$bmsMinimumVoltage      = 21.5;                              // Minimale Voltage van de batterij (volgens de EcoFlow app), De batterij zal een beetje bijladen om de BMS wakker te houden

// Domoticz variables
	$domoticzIP			    = '192.168.168.168:8080'; 	    	 // IP + poort van Domoticz
	$heaterWcdIDX           = '0';
	$quookerWcdIDX			= '0';
	$aanrecht1WcdIDX		= '0';
	$aanrecht2WcdIDX	    = '0';
	$natalyaWcdIDX	        = '0';
	$controlSwitchIDX       = '0';
	
// Ecoflow Powerstream API variables
	$ecoflowPath		    = '/path/2files/';	                 // Path waar je scripts zich bevinden
	$ecoflowAccessKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API access key
	$ecoflowSecretKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API secret key
	$ecoflowOneSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream One serialnummer
	$ecoflowTwoSerialNumber = 'HWXXXXXXXXXXXXXX';		         // Powerstream Two serialnummer
?>