<?php
namespace Reaspekt\Geobase\Tools;

use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class Main
{
     const MID = "reaspekt.geobase";
     const API_OPTION = "reaspekt_set_apikey";

     public static function cidrToRange($cidr): ?array
     {
          $range = [];
          $cidr = explode('/', $cidr);
          $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
          $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
          return $range;
     }
     
     public static function getApiKey(): ?string
     {
          $reaspektGeoApiKey = Option::get(self::MID, self::API_OPTION);
          return $reaspektGeoApiKey;
     }

     private static function geobaseGetMicrotime(): ?float
     {
          list($usec, $sec) = explode(" ", microtime());
          return ((float)$usec + (float)$sec);
     }

     public static function getUserHostIP(): ?string
     {
          if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
               //check ip from share internet
               $ip = $_SERVER['HTTP_CLIENT_IP'];
          } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
               //to check ip is pass from proxy
               $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
          } else {
               $ip = $_SERVER['REMOTE_ADDR'];
          }

          return $ip;
     }

     public static function initBots(): ?int
     {
          if (isset($_SERVER['HTTP_USER_AGENT'])) {
               return preg_match('/rambler|abacho|acoi|accona|aspseek|altavista|estyle|scrubby|lycos|geona|ia_archiver|alexa|sogou|skype|facebook|twitter|pinterest|linkedin|naver|bing|google|yahoo|duckduckgo|yandex|baidu|teoma|xing|bot|crawl|slurp|spider|mediapartners/i', $_SERVER['HTTP_USER_AGENT']);
          }

          return null;
     }

     public function standartFormat(array $arData = []): ?array
     {
          if (empty($arData)) {
               return ["ERROR" => "No data"];
          }

          $arDataStandart = [];

          //Make groups where the values are crossing
          $group["CITY"] = ['city', 'Town', 'UF_NAME'];
          $group["COUNTRY_CODE"] = ['country', 'Country', 'UF_COUNTRY_CODE'];
          $group["REGION"] = ['region', 'Region', 'UF_REGION_NAME'];
          $group["OKRUG"] = ['district', 'UF_COUNTY_NAME', 'UF_COUNTRY_NAME'];
          $group["INETNUM"] = ['inetnum', 'UF_BLOCK_ADDR']; // ip limits
          $group["UF_XML_ID"] = ['UF_XML_ID']; // ip limits

          foreach ($arData as $keyCity => $valCity) {
               $status = true;

               if (in_array($keyCity, $group["CITY"])) {
                    $arDataStandart["CITY"] = $valCity;
                    $status = false;
               }
               if (in_array($keyCity, $group["COUNTRY_CODE"])) {
                    $arDataStandart["COUNTRY_CODE"] = $valCity;
                    $status = false;
               }
               if (in_array($keyCity, $group["REGION"])) {
                    $arDataStandart["REGION"] = $valCity;
                    $status = false;
               }
               if (in_array($keyCity, $group["OKRUG"])) {
                    $arDataStandart["OKRUG"] = $valCity;
                    $status = false;
               }
               if (in_array($keyCity, $group["INETNUM"])) {
                    $arDataStandart["INETNUM"] = $valCity;
                    $status = false;
               }
               //If there are unique fields for the service, remember it just in case
               if ($status) {
                    $arDataStandart[$keyCity] = $valCity;
               }
          }

          return $arDataStandart;
     }
}