# Hoymiles Microinverter
Das Modul stellt alle Daten der Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500), die an einem OpenDTU Gateway angemeldet sind in IP-Symcon bereit. Außerdem kann aus IP-Symcon heraus die Einspeiseleistung des Wechselrichters geändert werden.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)
5. [Statusvariablen und Profile](#5-statusvariablen-und-profile)
6. [WebFront](#6-webfront)
7. [PHP-Befehlsreferenz](#7-php-befehlsreferenz)

### 1. Funktionsumfang

* Auslesen aller Daten und Steuerung der Hoymiles Mikro-Wechselrichter über OpenDTU
* Persistentes und Nichtpersistentes Setzen der Einspeiseleistung des Wechselrichters (Power Limit)

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- OpenDTU
- Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500)

### 3. Software-Installation

* Über den Module Store das 'Hoymiles Microinverter mit OpenDTU'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'Hoymiles Microinverter'-Modul mithilfe des Schnellfilters gefunden werden. Alternativ können Instanzen auch über den Konfigurator hinzugefügt werden. Der Konfigurator erkennt automatisch alle OpenDTU's und Wechselrichter, die am Symcon MQTT-Server angemeldet sind.

- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
BaseTopic  | MQTT BaseTopic der OpenDTU (kann im Webinterface der OpenDTU unter Settings->MQTT konfiguriert werden)
Serial     | Seriennummer des Wechselrichters
Model      | Modell des Wechselrichters

![Instanzkonfiguration](..\docs\HoymilesMicroinverter_Configuration.png)

### 5. Statusvariablen und Profile

Alle Statusvariablen können in der Instantzkonfiguration einzeln aktiviert bzw. deaktiviert werden.


### 6. WebFront

Im Webfront können die folgenden Funktionen gesteuert werden:
* Power Limit absolut setzen (nicht-persistent)
* Power Limit relativ setzen (nicht-persistent)
* Wechselrichter an- und ausschalten


### 7. PHP-Befehlsreferenz

`boolean HOYMILES_SetLimitAbsolute(integer $InstanzID, integer $Limit);`
Setzt das Einspeiselimit in Watt. Das Limit wird im Wechselrichter nicht persistent gespeichert und wird nach Ausschalten des Wechselrichters (nachts) wieder auf den persistent gepseicherten Wert zurückgesetzt.

`boolean HOYMILES_SetLimitRelative(integer $InstanzID, integer $Limit);`
Setzt das Einspeiselimit in % von 1-100. Das Limit wird im Wechselrichter nicht persistent gespeichert und wird nach Ausschalten des Wechselrichters (nachts) wieder auf den persistent gepseicherten Wert zurückgesetzt.

`boolean HOYMILES_SetLimitAbsolutePersistent(integer $InstanzID, integer $Limit);`
Setzt das Einspeiselimit in Watt. Das Limit wird im Wechselrichter persistent gespeichert und bleibt auch nach Ausschalten des Wechselrichters (nachts) erhalten.

`boolean HOYMILES_SetLimitRelativePersistent(integer $InstanzID, integer $Limit);`
Setzt das Einspeiselimit in % von 1-100. Das Limit wird im Wechselrichter persistent gespeichert und bleibt auch nach Ausschalten des Wechselrichters (nachts) erhalten.

`boolean HOYMILES_RestartInverter(integer $InstanzID);`
Startet den Wechselrichter neu. Hierbei wird auch der Zähler des Tagesertrags zurückgesetzt.

`boolean HOYMILES_SwitchInverter(integer $InstanzID, boolean $status);`
Schalten die Einspeisung des Wechselrichters an bzw. aus.