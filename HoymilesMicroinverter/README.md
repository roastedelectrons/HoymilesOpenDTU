# Hoymiles Microinverter
Das Modul stellt alle Daten der Hoymiles Modulwechselrichter (HM-300, HM-350, HM-400, HM-600, HM-800, HM-1200, HM-1500), die an einem OpenDTU Gateway angemeldet sind in IP-Symcon bereit. Außerdem kann aus IP-Symcon heraus die Leistungsbegrenzung des Wechselrichters geändert werden.

### Inhaltsverzeichnis

1. [Einrichten der Instanzen in IP-Symcon](#1-einrichten-der-instanzen-in-ip-symcon)
2. [Statusvariablen und Profile](#2-statusvariablen)
3. [WebFront](#3-webfront)
4. [PHP-Befehlsreferenz](#4-php-befehlsreferenz)


### 1. Einrichten der Instanzen in IP-Symcon

__Konfigurationsseite__:

Name     | Beschreibung
-------- | ------------------
BaseTopic  | MQTT BaseTopic der OpenDTU (kann im Webinterface der OpenDTU unter *Settings->MQTT* gefunden und bei Bedarf konfiguriert werden)
Serial     | Seriennummer des Wechselrichters
Model      | Modell des Wechselrichters

![Instanzkonfiguration](../docs/HoymilesMicroinverter_Configuration.png)

### 2. Statusvariablen

Alle Statusvariablen können in der Instantzkonfiguration einzeln aktiviert bzw. deaktiviert werden.


### 3. WebFront

Im Webfront können die folgenden Funktionen gesteuert werden:
* Leistungsbegrenzung absolut setzen (nicht-persistent)
* Leistungsbegrenzung relativ setzen (nicht-persistent)
* Wechselrichter an- und ausschalten


### 4. PHP-Befehlsreferenz

`boolean HOYMILES_SetLimitAbsolute(integer $InstanzID, integer $Limit);`

Setzt das  in Watt. Das Limit wird im Wechselrichter nicht persistent gespeichert und wird nach Ausschalten des Wechselrichters (nachts) wieder auf den persistent gepseicherten Wert zurückgesetzt.

`boolean HOYMILES_SetLimitRelative(integer $InstanzID, integer $Limit);`

Setzt das Einspeiselimit in % von 1-100. Das Limit wird im Wechselrichter nicht persistent gespeichert und wird nach Ausschalten des Wechselrichters (nachts) wieder auf den persistent gepseicherten Wert zurückgesetzt.

`boolean HOYMILES_SetLimitPersistentAbsolute(integer $InstanzID, integer $Limit);`

Setzt das Einspeiselimit in Watt. Das Limit wird im Wechselrichter persistent gespeichert und bleibt auch nach Ausschalten des Wechselrichters (nachts) erhalten.

`boolean HOYMILES_SetLimitPersistentRelative(integer $InstanzID, integer $Limit);`

Setzt das Einspeiselimit in % von 1-100. Das Limit wird im Wechselrichter persistent gespeichert und bleibt auch nach Ausschalten des Wechselrichters (nachts) erhalten.

`boolean HOYMILES_RestartInverter(integer $InstanzID);`

Startet den Wechselrichter neu. Hierbei wird auch der Zähler des Tagesertrags zurückgesetzt.

`boolean HOYMILES_SwitchInverter(integer $InstanzID, boolean $status);`

Schalten die Einspeisung des Wechselrichters an bzw. aus.
