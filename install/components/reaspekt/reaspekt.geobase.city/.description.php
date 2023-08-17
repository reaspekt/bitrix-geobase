<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arComponentDescription = array(
        "NAME" => GetMessage("REASPEKT_GEOIP_NAME_NEW"),
        "DESCRIPTION" => GetMessage("REASPEKT_GEOIP_DESC_NEW"),
        "ICON" => "/images/icon.gif",
        "CACHE_PATH" => "Y",
        "PATH" => array(
            "ID" => "REASPEKT.RU",
            "NAME" => GetMessage("REASPEKT_DESC_SECTION_NAME_NEW"),
            "CHILD" => array(
                "ID" => "REASPEKT_serv_new",
                "NAME" => GetMessage("REASPEKT_GEOIP_SERVICE_NEW")
            )
        ),
);

?>
