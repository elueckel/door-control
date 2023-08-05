# Garage Door

Das Garage Door Modul ermöglicht die Steuerung eines Garagentors. Funktionen:

* Tor Auf/Zu (Zugriff auf Aktor via Boolean An/Aus)
* Zugriff via "Taster" und Homekit
* Visualisierung der Bewegung des Tors als Status (Keine Bewegung, In Bewegung, Lüften, ...)
* Unterstützung für das Lüften (nach dem Öffnen wird der Aktor nochmal angesprochen um ein direktes öffnen zu ermöglichen)
* Unterstützung von Lichtschranken (geplant)
* Automatisches Schließen nach Verlassen der Garage (geplant)
* Nachrichten beim Auslösen (geplant)

## Setup
Die Einrichtung des Moduls ist sehr einfach. 
1. Download des Moduls via (Module Store geplant) oder github https://github.com/elueckel/door-control 
2. Anlegen der Instanz: Garage Door
3. Konfiguration im Modul 

## Funktionen und deren Nutzung

### Lüften
Um die Gerage zu lüften kann das Modul das Tor ein Stück öffnen. Die Funktion muss aber von außen aktivert werden (Boolean An/Aus)- also entweder via Zeit oder Luftfeuchte. Die Zeit zum öffnen sollte z.B. 3 Sekunden betragen - nach der öffnen, spricht das Modul den Aktor nochmal an, damit es wieder auf den Zustand vor dem Lüften gebracht wird. 
Wenn ein Tor geöffnet war und lüften aktiv ist, dann wird das Tor geschlossen und danach wieder auf die Lüften funktion gesetzt. 
Beim Beenden von Lüften wird das Tor wieder geschlossen.

### Automatisches Schließen
Die Funktion schliest das Tor nach x Minuten. Die Funktion wird im Modul grundsätzlich aktiviert und dann über eine Variable im Objektbaum ein oder aus geschaltet. Somit kann man es im Webfront/App einfach schalten. Vor dem Automatischen Schließen wird geprüft ob eine evtl. vorhandene Lichtschranke offen ist. 

## Variablen

* Tor Taster: Taster welcher das Modul anspricht.
* Homekit Variable (im Modul zu aktivieren): Diese kann in das Homekit Modul eingebunden werden (muss vorher im Modul aktiviert werden).
* Tor Status: Zu, Auf, Lüften
* Tor aktuelle Funktion: Keine Bewegung, Öffnet, Schließt, Ventilation Öffnet, Ventilation Schließt, Ventilation Reverse.
* Ventilation (im Modul zu aktivieren): An/Aus kann manuell gesetzt werden oder durch Ereignisse von außen (z.B. Luftfeuchte).
* Auto Schließen (im Modul zu aktivieren): Wenn aktiv wird das Modul das Tor nach eine konfigurierten Zeit schließen.


## Versionen (siehe Module)
0.1 - 05-08-2023 (Test)
* Tor Auf/Zu (Zugriff auf Aktor via Boolean An/Aus)
* Zugriff via "Taster" und Homekit
* Visualisierung der Bewegung des Tors als Status (Keine Bewegung, In Bewegung, Lüften, ...)
* Unterstützung für das Lüften (nach dem Öffnen wird der Aktor nochmal angesprochen um ein direktes öffnen zu ermöglichen)
* Unterstützung für automatisches Schließen
