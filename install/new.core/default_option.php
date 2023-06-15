<?
use \Reaspekt\Geobase\Repository\LocalRepo as Local;
use \Reaspekt\Geobase\DefaultCities;

$reaspekt_geobase_default_option = [
	"reaspekt_set_timeout" => "4",
	"reaspekt_get_update" => "N",
	"reaspekt_city_manual_default" => "",
	"reaspekt_enable_jquery" => "Y",
    "reaspekt_elib_site_code" => "",
    "reaspekt_set_apikey" => "",
    "reaspekt_transferred_core" => "",
    "only_cis" => "",
];

$arDefaultCity = [
    "524901",
    "498817",
];

if (CModule::IncludeModule("reaspekt.geobase")) {
    $statusDB = Local::statusTableDB();

    if ($statusDB) {
        if (!empty($arDefaultCity)) {
            $reaspekt_geobase_default_option["reaspekt_city_manual_default"] = serialize($arDefaultCity);
        }
    }
}