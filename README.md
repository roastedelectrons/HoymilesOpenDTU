[![Version](https://img.shields.io/badge/Symcon-PHPModul-blue.svg)](https://www.symcon.de/service/dokumentation/entwicklerbereich/sdk-tools/sdk-php/)
[![Version](https://img.shields.io/badge/Symcon%20Version-%20%3E6.0-blue.svg)](https://www.symcon.de/de/service/dokumentation/installation/migrationen/)
![GitHub](https://img.shields.io/github/license/roastedelectrons/hoymilesopendtu)
 
# Hoymiles Mikrowechselrichter mit OpenDTU

Modul für IP-Symcon zur Integration der Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500) über eine OpenDTU. 

OpenDTU ist eine Firmware für den ESP32 und bildet zusamen mit einem NRF24L01+ Funkmodul ein Gateway zur Kommunikation mit den Wechselrichtern. OpenDTU stellt ein Webinterface zur Konfiguration und zum Auslesen der Wechselrichter zur Verfügung und kann mittels MQTT in andere Systeme eingebunden werden. Weitere Infos zum Bau und Einrichtung der OpenDTU gibt es im  [OpenDTU GitHub-Repository](https://github.com/tbnobody/OpenDTU).

### Inhaltsverzeichnis

1. [Voraussetzungen](#1-voraussetzungen)
2. [Enthaltene Module](#2-enthaltene-module)
3. [Software-Installation](#3-software-installation)
4. [Einrichtung in IP-Symcon](#4-einrichtung-in-ip-symcon)
5. [Einrichtung in OpenDTU](#5-einrichtung-in-opendtu)
6. [Lizenz](#6-lizenz)


### 1. Voraussetzungen

- IP-Symcon ab Version 6.0
- OpenDTU ([Dokumentation](https://github.com/tbnobody/OpenDTU))
- Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500)

### 2. Enthaltene Module

- __Hoymiles Microinverter__ ([Dokumentation](HoymilesMicroinverter))  
	Das Modul stellt alle Daten der Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500), die an einem OpenDTU Gateway angemeldet sind, in IP-Symcon bereit. Außerdem kann aus IP-Symcon heraus die Einspeiseleistung des Wechselrichters geändert werden.

- __OpenDTU Configurator__ ([Dokumentation](OpenDTUConfigurator))  
	Der Konfigurator erkennt automatisch alle OpenDTU's, die am IP-Symcon MQTT Server angemeldet sind und ermöglicht so die einfache Erstellung der Instanz für den Hoymiles Modulwechselrichter ohne weiteren Konfigurationsaufwandt.

### 3. Software-Installation

Über den Module Store das 'Hoymiles OpenDTU'-Modul installieren.

### 4. Einrichtung in IP-Symcon

Vor der Einrichtung in IP-Symcon sollten die MQTT-Einstellungen in der OpenDTU, wie im nächsten Abschnitt beschrieben, vorgenommen werden.

Die Installation und Einrichtung der *Hoymiles Microinverter*-Instanzen erfolgt am einfachten mit dem *OpenDTU Configrator*. Der Konfigurator erkennt automatisch alle OpenDTU's und Wechselrichter, die am Symcon MQTT-Server angemeldet sind. Werden neue Instanzen aus dem Konfigurator heraus angelegt, werden sie automatisch eingerichtet und sind sofort funktionsbereit.

Die manuelle Konfiguration der *Hoymiles Microinverter*-Instanzen ist in der Modul-Dokumentation beschrieben.

### 5. Einrichtung in OpenDTU

Im Webinterface der OpenDTU müssen unter *Settings->MQTT* die folgenden *MQTT Broker Parameter* angepasst werden:
- **Hostname**: IP oder Hostname des IP-Symcon Servers
- **Port**: Port des IP-Symcon MQTT-Servers
- **Base Topic**: Dieser Wert kann beliebig gesetzt werden und muss in IP-Symcon in der *Hoymiles Microinverter*-Instanz eingetragen werden, sofern nicht der Konfigurator benutzt wird.

### 6. Lizenz
MIT License

Copyright (c) 2022 Tobias Ohrdes

