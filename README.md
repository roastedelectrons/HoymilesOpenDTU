[![Symcon Module](https://img.shields.io/badge/Symcon-PHPModul-blue.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
![Symcon Version](https://img.shields.io/badge/dynamic/json?color=blue&label=Symcon%20Version&prefix=%3E%3D&query=compatibility.version&url=https%3A%2F%2Fraw.githubusercontent.com%2Froastedelectrons%2FHoymilesOpenDTU%2Fmain%2Flibrary.json)
![Module Version](https://img.shields.io/badge/dynamic/json?color=green&label=Module%20Version&query=version&url=https%3A%2F%2Fraw.githubusercontent.com%2Froastedelectrons%2FHoymilesOpenDTU%2Fmain%2Flibrary.json)
![GitHub](https://img.shields.io/github/license/roastedelectrons/hoymilesopendtu)
 
# Hoymiles Mikrowechselrichter mit OpenDTU

Modul für IP-Symcon zur Integration der Hoymiles Mikrowechselrichter für Photovoltaik-Module (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500) über eine OpenDTU. 

OpenDTU ist eine Firmware für den ESP32 und bildet zusamen mit einem NRF24L01+ Funkmodul ein Gateway zur Kommunikation mit den Wechselrichtern. OpenDTU stellt ein Webinterface zur Konfiguration und zum Auslesen der Wechselrichter zur Verfügung und kann mittels MQTT in andere Systeme eingebunden werden. Weitere Infos zum Bau und Einrichtung der OpenDTU gibt es im  [OpenDTU GitHub-Repository](https://github.com/tbnobody/OpenDTU).

### Inhaltsverzeichnis

1. [Voraussetzungen](#1-voraussetzungen)
2. [Enthaltene Module](#2-enthaltene-module)
3. [Software-Installation](#3-software-installation)
4. [Einrichtung in IP-Symcon](#4-einrichtung-in-ip-symcon)
5. [Einrichtung in OpenDTU](#5-einrichtung-in-opendtu)
6. [Changelog](#6-changelog)
7. [Lizenz](#7-lizenz)


### 1. Voraussetzungen

- IP-Symcon ab Version 6.0
- OpenDTU ([Dokumentation](https://github.com/tbnobody/OpenDTU))
- Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500)

### 2. Enthaltene Module

- __Hoymiles Microinverter__ ([Dokumentation](HoymilesMicroinverter))  
	Das Modul stellt alle Daten der Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500), die an einem OpenDTU Gateway angemeldet sind in IP-Symcon bereit. Außerdem kann aus IP-Symcon heraus die Leistungsbegrenzung des Wechselrichters geändert werden.

- __OpenDTU__ ([Dokumentation](OpenDTU))  
	Das Modul stellt die Betriebsdaten der OpenDTU in IP-Symcon bereit. Außerdem ermöglicht es den Neustart der OpenDTU und ein Wiederverbinden der MQTT Verbindung.

- __OpenDTU Configurator__ ([Dokumentation](OpenDTUConfigurator))  
	Der Konfigurator erkennt automatisch alle OpenDTU's, die am IP-Symcon MQTT Server angemeldet sind und ermöglicht so die einfache Erstellung der Instanz für den Hoymiles Modulwechselrichter ohne weiteren Konfigurationsaufwandt.

### 3. Software-Installation

Über den Module Store das 'Hoymiles OpenDTU'-Modul installieren.

### 4. Einrichtung in IP-Symcon

*Hinweis: Die Einrichtung sollte erfolgen, wenn der Wechselrichter eingeschaltet ist (es liegt eine ausreichende DC-Spannung am Modul-Eingang an), da nur dann alle notwendigen Daten von OpenDTU bereitgestellt werden.*

Vor der Einrichtung in IP-Symcon sollten die MQTT-Einstellungen in OpenDTU, wie im nächsten Kapitel beschrieben, vorgenommen werden.

Die Installation und Einrichtung der *Hoymiles Microinverter*-Instanzen erfolgt am einfachten mit dem *OpenDTU Configurator*. Der Konfigurator erkennt automatisch alle OpenDTU's und Wechselrichter, die am Symcon MQTT-Server angemeldet sind. Werden neue Instanzen aus dem Konfigurator heraus angelegt, werden sie automatisch eingerichtet und sind sofort funktionsbereit.

### 5. Einrichtung in OpenDTU

Im Webinterface der OpenDTU müssen unter *Settings->MQTT* die folgenden *MQTT Broker Parameter* angepasst werden:
- **Hostname**: IP oder Hostname des IP-Symcon Servers
- **Port**: Port des IP-Symcon MQTT-Servers
- **Base Topic**: Dieser Wert kann beliebig gesetzt werden und muss in IP-Symcon in der *Hoymiles Microinverter*-Instanz eingetragen werden, sofern nicht der Konfigurator benutzt wird.

### 6. Changelog
Version 1.1.0 (2023-04-23)
* Neu: OpenDTU Splitter Instanz
	* Statusvariablen der OpenDTU (Hostname, IP, RSSI, Uptime, Status)
	* Reboot() startet OpenDTU neu (über WebAPI)
	* ReconnectMQTT() versucht eine MQTT-Verbindung wiederherzustellen (über WebAPI)
	* Schalter für automatischen Reconnect bei MQTT-Verbindungsproblemen
	* Datenfluss (wird automatisch angepasst): MQTT-Server <-> OpenDTU (Splitter) <-> Microinverter (Device)
* Fix: Variablen mit Aktion können nun ohne Fehlermeldung deaktiviert werden

Version 1.0.2
* Fix: Probleme mit utf-8 Kodierung bei Umlauten in Wechselrichternamen und Topics in Symcon 6.3
* Neu: Button zum Zurücksetzten des Konfigurators

Version 1.0.1
* FIX: Konfigurator hat falsche Wechselrichter-Namen angezeigt

Version 1.0.0 
* Inital stable release

### 7. Lizenz
MIT License

Copyright (c) 2022 Tobias Ohrdes

