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
	$invEndTime			    = '12:30';							 // Omvormer eindtijd (bij $runInfinity == 'no')
	$runInfinity		    = 'yes';		    				 // Waarde 'yes', 'no' bij 'yes' zal de omvormer indien mogelijk en afhankelijk van de instellingen altijd blijven opwekken
	$runInfinityMidday	    = 'no';								 // Waarde 'yes', 'no' bij 'yes' zal opladen voorrang hebben in de winter
	$runInfinityEvening	    = 'yes';							 // Waarde 'yes', 'no' bij 'yes' zal er in de winter in de avond geen opwek zijn
	$runInfinityNight	    = 'yes';							 // Waarde 'yes', 'no' bij 'yes' zal er in de winter in de nacht geen opwek zijn

// Lokatie variables tbv $sunset/$sunrise
	$latitude               = '00.00000';						 // Latitude is de afstand in graden 'Noord' of 'Zuid' tot de evenaar
	$longitude              = '-0.00000';						 // Longitude is de afstand in graden 'Oost' of 'West' tot de Meridiaan in Greenwich
	$zenitLat               = '89.5';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$zenitLong              = '91.7';							 // Het hoogste punt van de hemel gezien vanuit het punt waar de waarnemer staat
	$timezone               = 'Europe/Amsterdam';			     // Mijn php.ini slikt de timezone niet dus dan maar handmatig instelling
				
// Omvormer variables
	$ecoflowMaxOutput	    = 600;								 // Maximale teruglevering (Watts) wat de omvormer kan/mag leveren. 
	$ecoflowOutputOffSet    = 10;								 // Trek deze value (watts) af van de nieuwe baseload, Deze value wordt alsnog van het net wordt getrokken om teruglevering te voorkomen
	$maxInvTemp             = 65;								 // Maximale interne temperatuur, daarboven stopt de omvormer met terugleveren 

// Batterij variables
	$batteryVolt		    = 25.6;								 // Voltage van de batterij
	$batteryAh              = 200;                               // Totale Ah van alle batterijen
	$chargerEfficiency      =  83;                               // Lader laad efficientie
	$batteryMinimum		    =  10;                               // Minimale procenten die in de batterij moeten blijven
	
// Homewizard variables
	$hwP1IP				    = '000.000.000.00';					 // IP Homewizard P1 Meter
	$hwKwhIP			    = '000.000.000.00';					 // IP Homewizard Solar kwh Meter
	$hwEcoFlowIP		    = '000.000.000.00';					 // IP Homewizard EcoFlow socket
	$hwChargerOneIP 	    = '000.000.000.00';     			 // IP Homewizard Charger ONE 300w socket
	$hwChargerTwoIP 	    = '000.000.000.00';     			 // IP Homewizard Charger TWO 600w socket
	$hwChargerThreeIP 	    = '000.000.000.00';     			 // IP Homewizard Charger THREE 300w socket
	
// Lader variables
	$chargerOneUsage	    = 300;								 // Verbruik van Lader 1 (Watt)
	$chargerTwoUsage	    = 600;								 // Verbruik van Lader 2 (Watt)
	$chargerThreeUsage      = 300;								 // Verbruik van Lader 3 (Watt)
	$chargerWattsIdle	    =  14;								 // Standby Watts van alle laders wanneer batterijen vol zijn
	$chargerOffsetWatt	    = 1200;								 // Maximale Watts waarbij Lader 1 uitgeschakeld in de wintertijd dient te worden, ivm een grootverbruiker die maar even aan staat

// Fase protection
	$faseProtection		    = 'yes';                             // Waarde 'yes' of 'no'
	$maxFaseWatts		    = 5000;                              // Bij verbruik op de Fase hoger dan aangegeven Watts zullen alle laders uitschakelen om de maximale belasting van de Fase niet te overschrijden
	$fase				    = 1;                                 // Welke Fase moet beschermd worden
	
// Battery BMS variables
	$keepBMSalive		    = 'yes';                             // Indien batterij is leeg getrokken zal er een beetje bij geladen worden om de BMS wakker te houden
	$bmsMinimumVoltage      = 21.6;                              // Minimale Voltage van de batterij (volgens de EcoFlow app), De batterij zal een beetje bijladen om de BMS wakker te houden
	
// Ecoflow Powerstream API variables
	$ecoflowPath		    = '/Path/2/Files/';	                 // Path waar je scripts zich bevinden
	$ecoflowAccessKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API access key
	$ecoflowSecretKey	    = 'xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';// Powerstream API secret key
	$ecoflowSerialNumber    = 'HWXXXXXXXXXXXXXX';		         // Powerstream serie nummer
?>