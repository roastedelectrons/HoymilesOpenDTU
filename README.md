# Hoymiles Modulwechselrichter mit OpenDTU

Modul für IP-Symcon zur Integration der Hoymiles Mikro-Wechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500) über eine OpenDTU. OpenDTU ist ein Funkgateway bestehend aus einem ESP32 und einem NRF24L01+ Funkchip. OpenDTU kann mittels MQTT in weitere SmartHome Systeme eingebunden werden. Weitere Infos zum Bau und Einrichtung der OpenDTU gibt es im GitHub-Repository [OpenDTU](https://github.com/tbnobody/OpenDTU)).


Folgende Module beinhaltet das HoymilesOpenDTU Repository:

- __Hoymiles Microinverter__ ([Dokumentation](HoymilesMicroinverter))  
	Das Modul stellt alle Daten der Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500), die an einem OpenDTU Gateway angemeldet sind in IP-Symcon bereit. Außerdem kann aus IP-Symcon heraus die Einspeiseleistung des Wechselrichters geändert werden.

- __Hoymiles OpenDTU Configurator__ ([Dokumentation](HoymilesOpenDTUConfigurator))  
	Der Konfigurator erkennt automatisch alle OpenDTU's, die am IP-Symcon MQTT Server angemeldet sind und ermöglicht so die einfache Erstellung der Instanz für den Hoymiles Modulwechselrichter ohne weiteren Konfigurationsaufwandt.