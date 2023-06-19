<?php
namespace Reaspekt\Geobase;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Application;
use \Bitrix\Main\Web\Cookie;
use \Reaspekt\Geobase\Repository\LocalRepo as Local;
use \Reaspekt\Geobase\Tools\Main as GeoMain;
use \Reaspekt\Geobase\Type\Conditions;
\Bitrix\Main\Loader::includeModule("reaspekt.geobase");

Loc::loadMessages(__FILE__);

class DefaultCities
{
     const MID = "reaspekt.geobase";
     const TABLE_CITIES = "reaspekt_geobase_cities";
     const TABLE_IP = "reaspekt_geobase_codeip";
     const OPTION_DEFAULT_CITIES_SERIALIZED = "reaspekt_city_manual_default";
     const COOKIE_CITY_CHOSEN = "REASPEKT_GEOBASE_SAVE";

     /**
      * Handling all requests from admin section on default cities tab
      */
     public static function getCitySelected(array $obData): ?array
     {
          $context = Application::getInstance()->getContext();
          $request = $context->getRequest();
          $server = $context->getServer();
          $requestMethod = $server->getRequestMethod();
          $sqlConnection = Application::getConnection();
          $sqlHelper = $sqlConnection->getSqlHelper();

          if (!check_bitrix_sessid('sessid') && !IsIE()) {
               return ['ERROR' => "Incorrect session"];
          }

          if (isset($obData['action']) && !empty($obData['action'])) {
               switch ($obData['action']) {
                    case 'add':
                         if (isset($obData['cityId'])) {
                              $cityId = (int) $obData['cityId'];
                              return static::addSetCity($cityId);
                         }
                         break;
                    case 'update':
                         return static::updateCityRows();
                         break;
                    case 'delete':
                         if (isset($obData['cityId'])) {
                              $cityId = (int) $obData['cityId'];
                              return static::deleteCity($cityId);
                         }
                         break;
                    case 'search':
                         if (isset($obData['cityName'])) {
                              return static::citySearch($obData['cityName'], $obData['lang']);
                         }
                    break;
               }
          }

          return ['ERROR' => "Unknown error"];
     }

     /**
      * Searching city by name
      */
     private static function citySearch(string $cityName, string $lang): ?array
     {
          $city = trim($cityName);
          $citylen = strlen($city);
          $arCity = [];
          $i = 0;

          if ($lang == "ru" && $citylen > 1) { // LANGUAGE_ID
               $arCity = static::selectQueryCity($city);
          }
          return $arCity;
     }

     /**
      * Add city to default city list
      */
     private static function addSetCity(int $cityId): ?array
     {
          $cityId = (int) $cityId; 
          $arCity = current(Local::getCityById($cityId));

          if ($arCity["UF_XML_ID"]) {
               //Checking module settings
               $reaspektCityManualDefault = Option::get(self::MID, self::OPTION_DEFAULT_CITIES_SERIALIZED);
               $arReaspektCityManualDefault = unserialize($reaspektCityManualDefault);
               $arReaspektCityManualDefault[] = $arCity["UF_XML_ID"];
               //Remove repeating info
               $arReaspektCityManualDefault = array_unique($arReaspektCityManualDefault);
               $reaspektCityManualDefault = serialize($arReaspektCityManualDefault);
               Option::set(self::MID, self::OPTION_DEFAULT_CITIES_SERIALIZED, $reaspektCityManualDefault);
          } else {
               return ["ERROR" => "No XML ID"];
          }

          return $arCity;
     }

     /**
      * Update default city list in admin section
      */
     public static function updateCityRows(): ?array
     {
          $reaspektCityManualDefault = Option::get(self::MID, self::OPTION_DEFAULT_CITIES_SERIALIZED);
          $arReaspektCityManualDefault = unserialize($reaspektCityManualDefault);
          $arCityData = static::selectCityXmlIDArray($arReaspektCityManualDefault, true);

          $strCityDefaultTR = "";
          foreach ($arCityData as $arCity) {
               $idCity = $arCity["ID"];
               $strCityDefaultTR .= '<tr class="reaspekt_geobase_city_line">';
               $strCityDefaultTR .= "<td>" . $idCity . "</td>";
               $strCityDefaultTR .= "<td>" . $arCity["UF_XML_ID"] . "</td>";
               $strCityDefaultTR .= "<td>" . $arCity["CITY"] . "</td>";
               $strCityDefaultTR .= "<td>" . $arCity["REGION"] . "</td>";
               $strCityDefaultTR .= "<td>" . $arCity["OKRUG"] . "</td>";
               $strCityDefaultTR .= '<td><input type="submit" name="reaspekt_geobase_del_' . $arCity["UF_XML_ID"] . '" value="' . Loc::getMessage("REASPEKT_TABLE_CITY_DELETE") . '" onclick="reaspekt_geobase_delete_click(' . $arCity["UF_XML_ID"] . ');return false;"></td>';
               $strCityDefaultTR .= "</tr>";
          }

          echo $strCityDefaultTR;
          return ["HTML" => $strCityDefaultTR];
     }

     /**
      * Delete city from default city list
      */
     private static function deleteCity($id): ?array
     {
          $id = (int) $id;

          if ($id <= 0) {
               return ["ERROR" => "No city id"];
          }

          $reaspektCityManualDefault = Option::get(self::MID, self::OPTION_DEFAULT_CITIES_SERIALIZED);
          $arReaspektCityManualDefault = unserialize($reaspektCityManualDefault);

          foreach ($arReaspektCityManualDefault as $keyCity => &$idCity) {
               if ((int) $idCity == $id) {
                    unset($arReaspektCityManualDefault[$keyCity]);
               }
          }

          $reaspektCityManualDefault = serialize($arReaspektCityManualDefault);

          Option::set(self::MID, self::OPTION_DEFAULT_CITIES_SERIALIZED, $reaspektCityManualDefault);

          return ["SUCCESS" => true];
     }

     /**
      * Sending request to database to get city by name (getting standartized fields)
      */
     public static function selectQueryCity(string $strCityName = ""): ?array
     {
          if (!strlen($strCityName)) {
               return null;
          }

          //check on bots (to eliminate extra quiries)
          if (GeoMain::initBots()) {
               return null;
          }

          $arResult = [];
          $conditions = new Conditions;
          $conditions->setFilter(["%UF_NAME" => $strCityName]);
          $arCities = Local::getCityData($conditions);

          foreach ($arCities as $city) {
               $arResult[] = GeoMain::standartFormat($city);
          }

          return $arResult;
     }

     /**
      * Wrap search combination in a strong tag for component
      */
     private static function strReplaceStrongSearchCity(string $search = "", string $replace = "", string $strCity = "")
     {
          if (
               !$search
               || !$replace
               || !$strCity
          ) {
               return null;
          }

          $encode = 'UTF-8';
          $strdata = str_replace([$search, mb_strtolower($search, $encode)], ["<strong>" . $replace . "</strong>", "<strong>" . mb_strtolower($replace, $encode) . "</strong>"] , $strCity);

          return $strdata;
     }

     /**
      * Change quotes into double and turn data to js object
      */
     private static function codeJSON($data)
     {
		return str_replace("'", '"', \CUtil::PhpToJSObject($data));
	}

     /**
      * Turn data back to php array
      */
     private static function decodeJSON($data): ?array
     {
		return \CUtil::JsObjectToPhp($data);
	}

     /**
      * Get city by ip in standartized mode
      */
	public static function getDataIp(string $ip = ""): ?array
     {
          if (!strlen(trim($ip))) {
               return null;
          }

          $arData = [];
          $arData = static::getGeoData($ip);

          if (!empty($arData)) {
               //Standartize the data from different services
               $arData = GeoMain::standartFormat($arData);
          }

          return $arData;
	}

     /**
      * Get city by ip
      */
     private static function getGeoData(string $ip = ""): ?array
     {
          if (!$ip) {
               return null;
          }
          //check on bots to eliminate extra quiries
          if (GeoMain::initBots()) {
               return null;
          }

          $arCity = Local::getCityByIp($ip);
          return $arCity;
     }

     /**
      * Get cities by id
      */
     private static function getGeoDataId($id = 0)
     {
          if (!$id) {
               return null;
          }
          //check on bots to eliminate extra quiries
          if (GeoMain::initBots()) {
               return null;
          }

          $arResult = [];
          $conditions = new Conditions;
          $conditions->setFilter(["=ID" => $id]);
          $arCity = Local::getCityData($conditions);

          foreach ($arCity as $city) {
               $arResult[] = $city;
          }

          return $arResult;
     }

     /**
      * Get city by name in standartized mode
      */
     private static function getDataName($strName = "")
     {
          if (!strlen(trim($strName))) {
               return null;
          }

          $strName = trim($strName);
          $arData = static::getGeoDataName($strName);

          if ($arData!== null && !empty($arData)) {
               //Standartize the data from different services
               $arData = GeoMain::standartFormat($arData);
          }

          return $arData;
	}

     /**
      * Get cities by name (getting data as in Highload)
      */
     private static function getGeoDataName(string $strName = ""): ?array
     {
          if (!strlen(trim($strName))) {
               return null;
          }
          //check on bots to eliminate extra quiries
          if (GeoMain::initBots()) {
               return null;
          }

          $arResult = [];
          $conditions = new Conditions;
          $conditions->setFilter(["=UF_NAME" => $strName]);
          $arCity = Local::getCityData($conditions);

          foreach ($arCity as $city) {
               $arResult[] = $city;
          }

          return $arResult;
     }

     /**
      * Get city from cookies
      */
     public static function getAddr(): ?array
     {
          $defaultCity = Loc::getMessage("DEFAULT_CITY");
		$request = Application::getInstance()->getContext()->getRequest();
		$response = Application::getInstance()->getContext()->getResponse();
          $strData = $request->getCookie("REASPEKT_GEOBASE");
          $arData = [];

          //If there is no cookie
          if (
               !$strData
          ) {
               //Getiing user IP
               $ip = GeoMain::getUserHostIP();

               //checking cookies
               $last_ip = $request->getCookie("REASPEKT_LAST_IP");

               // if there is data about geoposition and it is in cookies
               if (($ip == $last_ip) && $strData) {
                    $decodedData = (array) json_decode($strData, true);
                    if (count($decodedData) > 0) {
                         $arData = $decodedData;
                    }
               } else {
                    // Getting data
                    $arData = static::getDataIp($ip); // local_db

                    if ($arData) {
                         // Formatting it to json to write to cookies
                         $strData = static::codeJSON($arData);
     
                         // Writing cookies
                         $cookieLastIP = new Cookie("REASPEKT_LAST_IP", $ip, time() + 31104000); // 60*60*24*30*12
                         $cookieLastIP->setHttpOnly(false);
                         $response->addCookie($cookieLastIP);
                         $cookieGeobase = new Cookie("REASPEKT_GEOBASE", $strData, time() + 31104000); // 60*60*24*30*12
                         $cookieGeobase->setHttpOnly(false);
                         $response->addCookie($cookieGeobase);
                         $response->writeHeaders();
                    } else {
                         return static::selectCityNameArray([$defaultCity]);
                    }
               }
          } else {
               $arData = static::decodeJSON($strData);
          }

          if (!$arData["ID"]) {
               $arData = current($arData);
          }
		return $arData;
	}

     /**
      * Set cookie that user did select a city in component
      */
     public static function setCityYes()
     {
		$response = Application::getInstance()->getContext()->getResponse();

		$arResult["STATUS"] = "Y";

          $cookie = new Cookie(self::COOKIE_CITY_CHOSEN, "Y", time() + 86400); // 60*60*24
          $cookie->setHttpOnly(false);
          $response->addCookie($cookie);
          $response->writeHeaders();

		return $arResult;
	}

     /**
      * Setting selected city by user manually in component
      */
     public static function setCityManual($strPostCityId = "")
     {
		$response = Application::getInstance()->getContext()->getResponse();

          if (!strlen(trim($strPostCityId))) {
               return null;
          }

          //check on bots to eliminate extra quiries
          if (GeoMain::initBots()) {
               return null;
          }

          $arData = static::selectCityXmlIDArray([$strPostCityId]);
          
          $arResult["STATUS"] = "N";

          if (!empty($arData)) {
               // Formatting it to json to write to cookies
               $strData = static::codeJSON($arData);
               $cookie = new Cookie("REASPEKT_GEOBASE", $strData, time() + 31104000); // 60*60*24*30*12
               $cookie->setHttpOnly(false);
               $response->addCookie($cookie);
               $response->writeHeaders();

               $arResult["STATUS"] = "Y";
          }

          return $arResult;
     }

     /**
      * Check if selected city is set
      */
	public static function checkPopupShow(): ?string
     {
		$request = Application::getInstance()->getContext()->getRequest();

		$wasShownPopup = "N";
          $strData = $request->getCookie(self::COOKIE_CITY_CHOSEN);

		if ($strData == "Y") {
			$wasShownPopup = "Y";
		}

		return $wasShownPopup;
	}

     /**
      * Getting list of default cities
      */
     public static function defaultCityList(): ?array
     {
          $arResult["DEFAULT_CITY"] = [];
		$request = Application::getInstance()->getContext()->getRequest();
		$response = Application::getInstance()->getContext()->getResponse();

          //check on bots to eliminate extra quiries
          if (GeoMain::initBots()) {
               return null;
          }
          //default cities
          $reaspektCityManualDefault = Option::get(self::MID, self::OPTION_DEFAULT_CITIES_SERIALIZED);
          $arReaspektCityManualDefault = unserialize($reaspektCityManualDefault);

          // Getting data
          $arResult["DEFAULT_CITY"] = static::selectCityXmlIDArray($arReaspektCityManualDefault);

          return $arResult["DEFAULT_CITY"];
     }

     /**
      * Get cities by xml id in standartized mode
      */
     private static function selectCityXmlIDArray($arCityXmlId = [], $autoKey = false): ?array
     {
          if (empty($arCityXmlId)) {
              return null;
          }
          //check on bots to eliminate extra quiries
          if (GeoMain::initBots()) {
               return null;
          }
          //Removing repeating info
          $arCityXmlId = array_unique($arCityXmlId);

          $arResult = [];
          $keyData = 0;
          $conditions = new Conditions;
          $conditions->setFilter(["=UF_XML_ID" => $arCityXmlId]);
          $arCity = Local::getCityData($conditions);

          foreach ($arCity as $city) {
               $arData = GeoMain::standartFormat($city);
               if ($autoKey && $arData["ID"]) {
                    $keyData = $arData["ID"];
               }

               $arResult[$keyData] = $arData;

               if (!$autoKey) {
                    $keyData++;
               }
          }

          return $arResult;
     }

     /**
      * Get cities by their names in array in standartized mode
      */
     private static function selectCityNameArray($arCityName = array(), $autoKey = false): ?array
     {
          if (empty($arCityName)) {
              return null;
          }
          //check on bots to eliminate extra quiries
          if (GeoMain::initBots()) {
               return null;
          }
          //Removing repeating info
          $arCityName = array_unique($arCityName);

          $arResult = [];
          $keyData = 0;
          $conditions = new Conditions;
          $conditions->setFilter(["=UF_NAME" => $arCityName]);
          $arCity = Local::getCityData($conditions);

          foreach ($arCity as $city) {
               $arData = GeoMain::standartFormat($city);
               if ($autoKey && $arData["ID"]) {
                    $keyData = $arData["ID"];
               }

               $arResult[$keyData] = $arData;

               if (!$autoKey) {
                    $keyData++;
               }
          }

          return $arResult;
     }

     /**
      * Return popup with default cities
      */
     public static function showSearchedCity(string $cityName): ?string
     {
          $arCity = static::selectQueryCity($cityName);    
          $arData = static::getAddr();
          $returnHTML = "";

          if (!empty($arCity)) {
               $cell = 1;
               foreach ($arCity as $valCity) {
                    $returnHTML .= '<div class="reaspektSearchCity">';
                    if ($arData["UF_XML_ID"] == $valCity["UF_XML_ID"]) {
                         $returnHTML .= '<strong>' . $valCity["CITY"] . '</strong>';
                    } else {
                         $returnHTML .= '<a onclick="JCReaspektGeobase.onClickReaspektGeobase(\'' . $valCity["UF_XML_ID"] . '\'); return false;" id="reaspekt_geobase_list_' . $cell . '" title="' . $valCity["CITY"] . '" href="javascript:void(0);">' . static::strReplaceStrongSearchCity($cityName, $cityName, $valCity["CITY"]) . ', ' . $valCity["REGION"] . '</a>';
                    }
                    $returnHTML .= '</div>';
                    $cell++;
               }
          } else {
               $returnHTML .= '<div class="reaspektNotFound">' . Loc::getMessage("REASPEKT_RESULT_CITY_NOT_FOUND") . '</div>';
          }

          return $returnHTML;
     }
}