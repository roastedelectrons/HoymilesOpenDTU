<?php

// Copy the content of this file in a Symcon script and execute to generate variable's translations for locale files

$translationList = [
    "Power limit relative" => "Leistungsbegrenzung relativ",
    "Power limit absolute" => "Leistungsbegrenzung absolut",
    "Inverter is producing" => "Wechselrichter produziert",
    "Inverter is reachable" => "Wechselrichter ist erreichbar",
    "Last inverter statistics udpate" => "Letzte Statusmeldung des Wechselrichters",
    "OpenDTU online status" => "OpenDTU Online Status",
    "Power" => "Leistung",
    "Current" => "Strom",
    "Voltage" => "Spannung",
    "Reactive power" => "Blindleistung",
    "Powerfactor" => "Leistungsfaktor",
    "Frequency" => "Frequenz",
    "Efficiency" => "Effizienz",
    "Inverter" => "Wechselrichter",
    "Temperature" => "Temperatur",
    "Energy today" => "Energie heute",
    "Energy total" => "Energie gesamt",
    "Energy" => "Energie",
    "irradiation" => "Einstrahlung" 
];


$file = __DIR__ . "/../modules/HoymilesOpenDTU/libs/variables.json";

$variables = json_decode(file_get_contents($file), true);;

$locale = array();

foreach ($variables as $variable)
{
    if (array_key_exists( $variable['Name'], $translationList))
    {
        $locale[$variable['Name']] = $translationList[$variable['Name']];
    } 
    else
    {
        $translatedName = $variable['Name'];

        foreach( $translationList as $text => $translation)
        {
            $translatedName = str_ireplace( $text, $translation,  $translatedName);
        }

        $locale[$variable['Name']] = $translatedName;
    }
}

echo json_encode($locale, JSON_PRETTY_PRINT);