<?php
namespace Reaspekt\Geobase\Repository;

\Bitrix\Main\Loader::includeModule("main");
use \Bitrix\Main\SystemException;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\IO\File;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Type\Dictionary;
use \Bitrix\Main\Web\HttpClient;
use \Reaspekt\Geobase\Contract\DatabaseInterface;
use \Reaspekt\Geobase\Tools\Main as GeoMain;
use \Reaspekt\Geobase\Type\Conditions;

Loc::loadMessages(__FILE__);

/**
* Working with Maxmind Database
*/
class MaxmindRepo implements DatabaseInterface
{
    const DEV_NAME = "reaspekt";
    const MID = "reaspekt.geobase";
    const FILE_IP = "GeoLite2-City-Blocks-IPv4.csv";
    const FILE_CITIES = "GeoLite2-City-Locations-ru.csv";
    const DB_LINK = "https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City-CSV&license_key=#API_KEY#&suffix=zip";
    const VERSION_OPTION = "reaspekt_latest_downloaded_geobase";
    const CIS_OPTION = "only_cis";
    const CIS_COUNTRIES = ["Россия", "Украина", "Беларусь", "Казахстан", "Узбекистан", "Туркменистан", "Таджикистан", "Молдова", "Кыргызстан", "Армения", "Азербайджан"];

    public static function getIpData(Conditions $conditions): Dictionary
    {
        $documentRoot = \Bitrix\Main\Application::getDocumentRoot();

        $companyName = self::DEV_NAME;
        $positionCount = 0;
        $limit = $conditions->getLimit();
        $offset = $conditions->getOffset();
        $fPath = $documentRoot . '/upload/' . $companyName . '/geobase/' . self::FILE_IP;
        static::prepareFileStatus($fPath);

        $resultDict = new Dictionary();
        $file = new \SplFileObject($fPath);
        if ($offset > 0) {
            $file->seek($offset);
        }

        while ($file->valid()) {
            $positionCount++;
            if ($positionCount > $limit) {
                break;
            }
            $strVar =  $file->fgets();
            if (trim($strVar) !== '') {
                $arValues = explode(',', $strVar);

                if (!empty($arValues) && intval($arValues[1]) > 0) {
                    $ipRanges = GeoMain::cidrToRange($arValues[0]);

                    $arIpData = [
                        "BLOCK_BEGIN" => $ipRanges[0],
                        "BLOCK_END" => $ipRanges[1],
                        "CITY_ID" => intval($arValues[1])
                    ];
                    $resultDict->set($positionCount, $arIpData);
                }
            }
        }

        return $resultDict;
    }

    public static function getCityData(Conditions $conditions): Dictionary
    {
        $documentRoot = \Bitrix\Main\Application::getDocumentRoot();

        $companyName = self::DEV_NAME;
        $positionCount = 0;
        $limit = $conditions->getLimit();
        $offset = $conditions->getOffset();
        $fPath = $documentRoot . '/upload/' . $companyName . '/geobase/' . self::FILE_CITIES;
        static::prepareFileStatus($fPath);

        $isOnlyCIS = Option::get(self::MID, self::CIS_OPTION); // Checking option to understand whether we download all countries or no
        $resultDict = new Dictionary();
        $file = new \SplFileObject($fPath);
        if ($offset > 0) {
            $file->seek($offset);
        }

        while ($file->valid()) {
            $strVar =  $file->fgets();
            $positionCount++;
            if ($positionCount > $limit) {
                break;
            }

            if (trim($strVar) !== '') {
                $arValues = explode(',', $strVar);

                $countryName = str_replace('"', "", $arValues[5]);
                if ($isOnlyCIS == "Y" && !in_array($countryName, self::CIS_COUNTRIES)) {
                    continue;
                }
                if (!empty($arValues) && intval($arValues[0]) > 0) {
                    $arCityData = [
                        "XML_ID" => intval($arValues[0]),
                        "NAME" => str_replace('"', "", $arValues[10]),
                        "REGION_NAME" => str_replace('"', "", $arValues[7]),
                        "COUNTRY_NAME" => $countryName
                    ];
                    $resultDict->set($positionCount, $arCityData);
                }
            }
        }

        return $resultDict;
    }

    public static function clearFileSystem(): void
    {
        $documentRoot = \Bitrix\Main\Application::getDocumentRoot();
        $fPathIP = $documentRoot . '/upload/' . self::DEV_NAME . '/geobase/' . self::FILE_IP;
        $fPathCities = $documentRoot . '/upload/' . self::DEV_NAME . '/geobase/' . self::FILE_CITIES;
        if (File::isFileExists($fPathIP)) {
            File::deleteFile($fPathIP);
        }
        if (File::isFileExists($fPathCities)) {
            File::deleteFile($fPathCities);
        }
    }

    private static function prepareFileStatus(string $fPath): void
    {
        if (!File::isFileExists($fPath)) {
            $loadStatus = static::load();
            if ($loadStatus["ERROR"]) {
                throw new Exception($loadStatus["ERROR"]);
            }

            $unpackStatus = static::unpack();
            if ($unpackStatus["ERROR"]) {
                throw new Exception($unpackStatus["ERROR"]);
            }
        }
    }

    public static function addIpData(Dictionary $dictionary): void
    {
        throw new SystemException("Empty method");
    }

    public static function addCityData(Dictionary $dictionary): void
    {
        throw new SystemException("Empty method");
    }

    public static function checkLatestVersion(): array
    {
        $arLastVersion = ["LAST_VERSION" => "Y"];

        $reaspektLatestVersion = Option::get(self::MID, self::VERSION_OPTION);

        $lastReleaseDate = static::getLastReleaseDate();
        if ($lastReleaseDate["ERROR"]) {
            return $lastReleaseDate;
        }

        if ((int) $lastReleaseDate["TIMESTAMP"] > (int) $reaspektLatestVersion) {
            $arLastVersion = ["LAST_VERSION" => "N"];
        }

        return $arLastVersion;
    }

    private static function getLastReleaseDate(): array
    {
        $result = [];
        $apiKey = GeoMain::getApiKey();
        $strRequestedUrl = str_replace("#API_KEY#", $apiKey, self::DB_LINK);
        $httpClient = new HttpClient();

        $httpClient->head($strRequestedUrl);
        $arHeaders = $httpClient->getHeaders()->toArray();
        $status = $httpClient->getStatus();
        if ($status == 200) {
            $result = ["TIMESTAMP" => strtotime($arHeaders['last-modified']['values'][0])];
        } elseif ($status == 401) {
            $result = ["ERROR" => Loc::getMessage("AUTH_ERROR_MAXMIND")];
        }

        return $result;
    }

    private static function load(): array
    {
        $apiKey = GeoMain::getApiKey();
        $linkToDatabase = str_replace("#API_KEY#", $apiKey, self::DB_LINK);

        $documentRoot = \Bitrix\Main\Application::getDocumentRoot();
        $httpClient = new HttpClient();
        $result = $httpClient->download($linkToDatabase, $documentRoot . "/upload/" . self::DEV_NAME . "/geobase/maxmind.zip");

        if ($result) {
            $response["SUCCESS"] = "DOWNLOAD SUCCESS";
        } else {
            $response["ERROR"] = "ERROR OCCURED";
        }

        return $response;
    }

    private static function unpack(): array
    {
        $companyName = self::DEV_NAME;
        $status = ["SUCCESS" => Loc::getMessage("LOADER_UNPACK_ACTION")];
        $documentRoot = \Bitrix\Main\Application::getDocumentRoot();

        umask(0);
        if (!defined("AS_DIR_PERMISSIONS")) {
            define("AS_DIR_PERMISSIONS", 0777);
        }

        if (!defined("AS_FILE_PERMISSIONS")) {
            define("AS_FILE_PERMISSIONS", 0777);
        }

        $zip = new \ZipArchive;
        $arFiles = [self::FILE_IP, self::FILE_CITIES];
        $zipPath = $documentRoot . "/upload/" . $companyName . "/geobase/maxmind.zip";
        $res = $zip->open($zipPath);
        if ($res === true) {
            $response["FILENAMES"] = "";
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $filename = $zip->getNameIndex($i);
                $fileinfo = pathinfo($filename);
                if (in_array($fileinfo['basename'], $arFiles)) {
                    copy("zip://" . $zipPath . "#" . $filename, $documentRoot . "/upload/" . $companyName . "/geobase/" . $fileinfo['basename']);
                }
            }
            $zip->close();
            $uRes = true;
        } else {
            $uRes = false;
        }

        if ($uRes) {
            $lastReleaseDate = static::getLastReleaseDate();
            Option::set(self::MID, self::VERSION_OPTION, $lastReleaseDate);

            File::deleteFile($zipPath);
            $status = ["SUCCESS" => Loc::getMessage("LOADER_UNPACK_DELETE")];
        } else {
            $status = ["ERROR" => Loc::getMessage("LOADER_UNPACK_ERRORS")];
        }

        return $status;
    }
}