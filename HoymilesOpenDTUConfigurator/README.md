# Hoymiles OpenDTU Configurator
Der Konfigurator erkennt automatisch alle OpenDTU's, die am Symcon MQTT Server angemeldet sind und ermöglicht so die einfache Erstellung der Instanz für den Hoymiles Modulwechselrichter ohne weiteren Konfigurationsaufwandt.

### Inhaltsverzeichnis

1. [Funktionsumfang](#1-funktionsumfang)
2. [Voraussetzungen](#2-voraussetzungen)
3. [Software-Installation](#3-software-installation)
4. [Einrichten der Instanzen in IP-Symcon](#4-einrichten-der-instanzen-in-ip-symcon)

### 1. Funktionsumfang

* Erkennt automatisch alle am IP-Symcon MQTT-Server angemeldeten OpenDTU's und ermöglicht die einfache Erstellung der Hoymiles Microinverter Instanzen ohne Konfigurationsaufwandt.

### 2. Voraussetzungen

- IP-Symcon ab Version 6.0
- IP-Symcon MQTT-Server
- OpenDTU
- Hoymiles Modulwechselrichter

### 3. Software-Installation

* Über den Module Store das 'Hoymiles Microinverter mit OpenDTU'-Modul installieren.
* Alternativ über das Module Control folgende URL hinzufügen

### 4. Einrichten der Instanzen in IP-Symcon

 Unter 'Instanz hinzufügen' kann das 'HoymilesOpenDTUConfigurator'-Modul mithilfe des Schnellfilters gefunden werden.  
	- Weitere Informationen zum Hinzufügen von Instanzen in der [Dokumentation der Instanzen](https://www.symcon.de/service/dokumentation/konzepte/instanzen/#Instanz_hinzufügen)

__Konfigurationsseite__:

![Instanzkonfiguration](..\docs\HoymilesOpenDTUConfigurator_Configuration.png)

Der Konfigurator findet automatisch alle OpenDTU's, die am IP-Symcon MQTT-Server angemeldet sind. Sofern noch keine MQTT-Server Instanz vorhanden ist, erstellt der Konfigurator eine neue.

__Konfiguration in OpenDTU__:

Im Webinterface der OpenDTU müssen unter *Settings->MQTT* die IP-Adresse des IP-Symcon Servers, sowie der entsprechende Port des MQTT-Servers angegeben werden.