<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use \Bitrix\Main\Config\Option;
use \Reaspekt\Geobase\DefaultCities;
use \Bitrix\Main\Localization\Loc;

\Bitrix\Main\Loader::includeModule("reaspekt.geobase");

Loc::loadMessages(__FILE__);
\CJSCore::init(['ajax', 'popup']);

class GeoipReaspektComponent extends CBitrixComponent
{
    public function onPrepareComponentParams($arParams)
    {
        if (!$arParams["CACHE_TIME"]) {
            $arParams["CACHE_TIME"] = 360000000;
        }
        $arParams["CHANGE_CITY_MANUAL"] = (!isset($arParams["CHANGE_CITY_MANUAL"]) ? 'Y' : $arParams["CHANGE_CITY_MANUAL"]);
		return $arParams;
    }

    public function executeComponent()
    {
        $arResult = [];
        $arResult["GEO_CITY"] = DefaultCities::getAddr();

        $arResult["CHANGE_CITY"] = "N";
        if ($this->arParams["CHANGE_CITY_MANUAL"] == "Y") {
            $arResult["POPUP_HIDE"] = DefaultCities::checkPopupShow();
        }

        
        if ($this->StartResultCache()) {
            $arResult["DEFAULT_CITY"] = DefaultCities::defaultCityList();
            $this->arResult = $arResult;
            $this->includeComponentTemplate();
        }
        
    }
}