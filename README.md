# MyEcoFlow Micro-Inverter thuisbatterij:
Sinds kort (2024) wat zonnepanelen op dak liggen.<br />
En alhoewel deze berekend op het jaarlijkse verbruik blijft er op sommige periodes van de dag toch nog een behoorlijke injectie terug op het net<br />
En aangezien de energiemarkt snel verandert en Den Haag net zo wispelturig is als ons nederlands weer wilde ik wat doen om zo veel mogelijk overtollige PV opwek zelf te gebruiken/opslaan<br />
Aangezien de op dit moment beschikbare kant & klare thuisbatterij oplossingen nog vrij prijzig zijn<br />
Zocht ik een oplossing om te testen hoe op een simpele en relatief goedkope manier mijn eigenverbuik van de panelen op te krikken.<br />
Dat werd dus een zelfbouw thuisbatterij welke tot opheden een redelijke betaalbare en duurzame oplossing is om de overtollige energie die je zelf opwekt op te slaan voor later gebruik.<br />
En hierbij wil ik mijn scripts, specs delen met julli.<br />
Ik weet dat deze scripts op persoonlijk verbruik en hardware toepassingen zijn gericht.<br />
Zul je zelf hier en daar wat in de scripts moeten sleutelen.<br />
<br />
Ik ga verder niet in op zaken hoe je bepaalde technische zaken of het opzetten van bijvoorbeeld de controler.<br />
Ga er namelijk vanuit dat je met enige voorkennis aan dit avontuur begint.<br />
Wel kan ik je verwijzen naar een topic op Tweaker waar ik ook op ben gestuit tijdens mijn zoektocht.<br />
Het is een leuke topic met veel informatie omtrent het bouwen van DIY Thuisbatterij.<br />
<br />
[Tweakers: Eenvoudige thuisaccu samenstellen](https://gathering.tweakers.net/forum/list_messages/2253584/0)
<br />

# MyEcoFlow Micro-Inverter thuisbatterij
# Doel:
Mijn doel is/was om op een goedkopere manier te testen hoe opslag en gebruik van overtollige zonnestroom goed is in te zetten.<br />
Het was niet de bedoeling om offgrid te gaan werken of het hele verbruik in huis af te dekken.<br />
Maar meer om mijn nacht-verbruik te dekken en eventueel wat pieken gedurende de dag op te vangen.<br />
Het systeem wat ik heb geknutseld kan dan ook geen grote pieken op vangen.<br />
De omvormer die maximaal 800w kan uitspugen is de grootste bottleneck.<br />
Maar hé, elk niet afgenomen kWh is er 1.<br />
Doel is dus nachtverbruik naar bijna 0 terugbrengen en gedurende de dag bij springen.<br />
<br />

# MyEcoFlow Micro-Inverter thuisbatterij
# Specs:
De volgende onderdelen heb ik moeten aanschaffen.<br />
Prijzen kunnen varieren nagelang kortingen of onderdelen die je al wellicht in huis hebt.<br />
Zo had ik al het Homewizard en Raspberry gebeuren al in huis.<br />
Toch zet ik de hele lijst hier neer om een idee van de kosten te krijgen.<br />
Nogmaals, kan zijn dat jij andere oplossing hebt voor het uitlezen van de P1 en zonnepanelen.<br />
<br />
# Onderdelen
- Ecoflow Micro-Inverter 800w       	=  €118<br />
- Ecoflow Smart Cooling Deck        	=    39<br />
- Powerqueen 25.6volt LiFepo4 100ah 	=   500<br />
- Powerqueen 29.2v 20a lader			=   160<br />
- Set van 2 mc4-connectoren         	=     8 (aansluiten batterij op de omvormer)<br />
- Homewizard P1 meter					= 	 30 (uitlezen P1 SlimmeMeter<br />
- Homewizard 3fase kWh meter			=   130 (uitlezen PV opwek)<br />
- Homewizzard Energy Socket				=  	 28	(Uitlezen omvormer opwek)<br />
- Zwave smart-plug						=	 30 (Schakelen lader dmv Domoticz)<br />
- Raspberry Pi 4B - 1Gb(incl behuizing) =	 65 (tbv Domoticz)<br />
- Aeotec Z Stick 5						=    50 (Om via zwavemqtt en Domoticz)
- Losse onderdelen (klein materiaal)	=	 10 (krimkousen, M8 ogen ect)<br />
Totaal:									= €1168<br />
<br />
Nogmaals: prijzen kunnen varieren.<br />
Ik zelf had het grootste gedeelte al in huis en mijn totale kosten waar voor alleen de batterij, lader en klein materiaal +/- €800.<br />
Bovenstaande wil dus niet zeggen dat jij dit allemaal hoeft aan te schaffen.<br />
<br />
# MyEcoFlow Micro-Inverter thuisbatterij
# Werking:
Eerst wil ik je graag verwijzen naar de volgende twee website.
- [Ehoco.nl - Een eenvoudige thuisbatterij zelf maken: stapsgewijze handleiding](https://ehoco.nl/eenvoudige-thuisbatterij-zelf-maken/#onderdelen)<br />
- [Tweakers: Eenvoudige thuisaccu samenstellen](https://gathering.tweakers.net/forum/list_messages/2253584/0)<br />
<br />
Via de eerste link kun je lezen hoe je globaal een thuisbatterij systeem kan samenstellen.<br />
Ook heb ik hier de basis verkregen en zijn mijn php scripts gebasseerd op Ehoco zijn scripts.<br />
Via de tweede link kom in de topic van Tweakers waar je informatie, tips en tricks kan lezen omtrent een diy thuisbatterij.<br />
<br />
De werking is eigenlijk net zo simpel als het idee.<br />
Je hebt een Raspberry Pi draaien met z-wave antenne.<br />
Die kan op basis van bepaalde variablen de lader gekoppeld aan een smart-plug start of doet stoppen.<br />
Ook heb je een php scriptje draaien die op basis van verschillende variablen de omvormer kan aansturen.<br />
En dus op basis van het verbruik in huis een bepaalde load het huis-net op te sturen met als doel bijna of geheel met 0kWh op de meter de nacht door te komen.<br />
Op de website van Ehoco vond een uitgebreidere uitleg hoe je de scripts op een Raspberry kan laten draaien.<br />
<br />
- De basis is Raspberry Pi als controler.<br />
- Batterij waarop de lader is aangesloten.<br />
- Deze zelfde batterij is aangesloten op de PV ingang van de omvormer.<br />
- De omvormer is middels normale schuko stekker in het stop-contact gestoken.<br />
<br />
De output van de omvormer is dus maximaal 800w maar in geval van 25.6v batterij maar 600w<br />
Wat inhoud dat je inprincipe geen aparte groep nodig hebt.<br />
Word wel aangeraden natuurlijk maar als je een groep pakt waarom bijvoorbeeld maar 1 grootverbruiker is aangesloten van bijvoorbeeld een e-boiler die 2500w nodig heeft heb je op die groep nog 1000w over.<br />
2500w + 800w van de omvormer maakt nog 200w vrije hebt.<br />
Het is krap maar kan wel, zelf heb ik wel een vrije groep gepakt.<br />
<br />
Bron - Ehoco.nl (foto):<br />
<br />
# MyEcoFlow Micro-Inverter thuisbatterij
# Toekomst:
Wat de toekomst precies zal brengen is koffiedik kijken.<br />
De energiemarkt is grillig en wat Den Haag wil is ook onduidelijk.<br />
Mij is wel duidelijk dat ondanks de beperkte capaciteit van de omvormer en batterij dit geintje aardig kan besparen op jaarbasis.<br />
Dat is natuurlijk voor elk huishouden anders.<br />
Voorlopige schatting is dat mij dit tussen €300 á €400 gaat besparen op de totale stroomkosten.<br />
Dan kun je van daaruit ook de terugverdien kosten uitrekenen.<br />
Je zou meer kunnen besparen als je bijvoorbeeld het ontlaad scriptje van Ehoco gebruikt die op basis van als je die hebt een dynamische contract kan laden en ontladen.<br />
Ik heb geen dynamisch contract dus moet het doen op manier zoals ik het nu doe.<br />
<br />
In de zeer nabije toekomst komt er een ook een kleine 300w lader bij.<br />
Maar ook nog een extra 2.5kWh batterij om deze parallel aan te sluiten.<br />
Zodat ik op totaal 5kWh bruikbare zelf opgewekte energie uit te komen.<br />
En de extra kleinere lader is om eerder te kunnen beginnen met laden en bij springen met de sterkere lader om sneller te kunnen laden.<br />
<br />
 
# MyEcoFlow Micro-Inverter thuisbatterij
# Screenshots:
<br />
Binnenkort wat screenshots van het verbruik, opbrengst en setup.<br /><br />


https://github.com/user-attachments/assets/67f3f522-5b3e-480c-b5ad-277477704de5


<br />
