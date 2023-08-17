<?
/**
 * Company developer: REASPEKT
 * Developer: reaspekt
 * Site: https://www.reaspekt.ru
 * E-mail: info@reaspekt.ru
 * @copyright (c) 2022 REASPEKT
 */
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie; 

class ReaspGeoIP
{

    const MID = "reaspekt.geobase";

    public static function StatusTabelDB()
    {
        global $DB;

        $arResult = false;

        if ($DB->TableExists('reaspekt_geobase_codeip') && $DB->TableExists('reaspekt_geobase_cities')) {
            $arResult = true;
        }

        return $arResult;
    }

    public static function SelectQueryCity($strCityName = "")
    {
        global $DB;

        if (!strlen($strCityName)) {
            return false;
        }

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        if (SITE_CHARSET == 'windows-1251') {
            $strCityName1 = @iconv("UTF-8", "windows-1251//IGNORE", $strCityName); // All AJAX requests come in Unicode
            if ($strCityName1) {
                $strCityName = $strCityName1;    // if used Windows-machine
            }
        }

        $strCityName = addslashes($strCityName);

        $arResult = array();

        if (ReaspGeoIP::StatusTabelDB()) {
            $data = $DB->Query("
                SELECT * FROM `reaspekt_geobase_cities` 
                WHERE `UF_NAME` like '" . $DB->ForSql($strCityName) . "%'
            "
            );

            while ($arData = $data->Fetch()) {
                $arData = ReaspGeoIP::StandartFormat($arData);
                
                $arResult[] = $arData;
            };
        } else {
            return false;
        }

        return $arResult;
    }

    public static function DefaultCityList()
    {
        $arResult["DEFAULT_CITY"] = array();
        $arResult["DEFAULT_CITY_ID"] = array();
        $arResult["DEFAULT_CITY_NAME"] = array();

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        //города по умолчанию
        $reaspekt_city_manual_default = Option::get(self::MID, "reaspekt_city_manual_default");
        $ar_reaspekt_city_manual_default = unserialize($reaspekt_city_manual_default);

        $arResult["DEFAULT_CITY"] = ReaspGeoIP::SelectCityIdArray($ar_reaspekt_city_manual_default);

        return $arResult["DEFAULT_CITY"];
    }
    
    public static function SelectCityXmlIdArray($arCityXmlId = array(), $autoKey = false)
    {
        global $DB;

        if (empty($arCityXmlId)) {
            return false;
        }

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        //Убираем дубли
        $arCityXmlId = array_unique($arCityXmlId);

        //Добавляем кавычки для поиска по троке
        foreach ($arCityXmlId as &$cityId) {
            $cityId = "'" . $DB->ForSql($cityId) . "'";
        }

        //переводим в строку
        $strCityId = implode(" OR `UF_XML_ID` = ", $arCityXmlId);
        
        $strReplaceCityId = str_replace(" ", "", $strCityId);

        $arResult = array();

        if (ReaspGeoIP::StatusTabelDB() == "Y") {
            $data = $DB->Query("
                SELECT * FROM `reaspekt_geobase_cities` 
                WHERE `UF_XML_ID` = " . $strCityId
            );

            $keyData = 0;
            while ($arData = $data->Fetch()) {
                $arData = ReaspGeoIP::StandartFormat($arData);

                if ($autoKey && $arData["ID"]) {
                    $keyData = $arData["ID"];
                }

                $arResult[$keyData] = $arData;

                if (!$autoKey) {
                    $keyData++;
                }
            };

        } else {
            return false;
        }

        return $arResult;
    }

    private static function SelectCityIdArray($arCityId = array(), $autoKey = false)
    {
        global $DB;

        if (empty($arCityId)) {
            return false;
        }

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        //Убираем дубли
        $arCityId = array_unique($arCityId);

        //Добавляем кавычки для поиска по троке
        foreach ($arCityId as &$cityId) {
            $cityId = "'" . $DB->ForSql($cityId) . "'";
        }

        //переводим в строку
        $strCityId = implode(" OR `ID` = ", $arCityId);

        $strReplaceCityId = str_replace(" ", "", $strCityId);

        $arResult = array();

        if (ReaspGeoIP::StatusTabelDB()) {
            $data = $DB->Query("
                SELECT * FROM `reaspekt_geobase_cities` 
                WHERE `ID` = " . $strCityId
            );

            $keyData = 0;
            while ($arData = $data->Fetch()) {
                $arData = ReaspGeoIP::StandartFormat($arData);

                if ($autoKey && $arData["ID"]) {
                    $keyData = $arData["ID"];
                }

                $arResult[$keyData] = $arData;

                if (!$autoKey) {
                    $keyData++;
                }
            };
        } else {
            return false;
        }

        return $arResult;
    }

    private static function SelectCityNameArray($arCityName = array(), $autoKey = false)
    {
        global $DB;

        if (empty($arCityName)) {
            return false;
        }

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        //Убираем дубли
        $arCityName = array_unique($arCityName);

        //Добавляем кавычки для поиска по троке
        foreach ($arCityName as &$cityName) {
            $cityName = "'" . $DB->ForSql($cityName) . "'";
        }
        //переводим в строку
        $strCityName = implode(" OR `UF_NAME` = ", $arCityName);

        $strReplaceCityId = str_replace(" ", "", $strCityName);

        $arResult = array();

        if (ReaspGeoIP::StatusTabelDB()) {
            $data = $DB->Query("
                SELECT * FROM `reaspekt_geobase_cities` 
                WHERE `UF_NAME` = " . $strCityName
            );

            $keyData = 0;
            while ($arData = $data->Fetch()) {
                $arData = ReaspGeoIP::StandartFormat($arData);

                if ($autoKey && $arData["ID"]) {
                    $keyData = $arData["ID"];
                }

                $arResult[$keyData] = $arData;

                if (!$autoKey) {
                    $keyData++;
                }
            };
        } else {
            return false;
        }

        return $arResult;
    }

    public static function SelectCityId($сityId = 0)
    {
        global $DB;

        if (!$сityId) {
            return false;
        }

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        $arResult = array();

        if (ReaspGeoIP::StatusTabelDB()) {
            $data = $DB->Query("
                SELECT * FROM `reaspekt_geobase_cities` 
                WHERE `ID` = " . $DB->ForSql($сityId) . "
            "
            );

            while ($arData = $data->Fetch()) {
                $arData = ReaspGeoIP::StandartFormat($arData);
                
                $arResult = $arData;
            };
        } else {
            return false;
        }

        return $arResult;
    }

    public static function ChangeCity()
    {
        global $APPLICATION;

        $arResult = "N";

        $strData = $APPLICATION->get_cookie("REASPEKT_GEOBASE_SAVE");

        if ($strData == "Y") {
            $arResult = "Y";
        }

        return $arResult;
    }

    public static function SetCityYes()
    {
        global $APPLICATION;

        $arResult["STATUS"] = "Y";

        $APPLICATION->set_cookie("REASPEKT_GEOBASE_SAVE", "Y", time() + 86400); // 60*60*24

        return json_encode($arResult);
    }

    public static function SetCityManual($strPostCityId = "")
    {
        global $APPLICATION;

        if (!strlen(trim($strPostCityId))) {
            return false;
        }

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        if (SITE_CHARSET == 'windows-1251') {
            $strPostCityId = iconv("UTF-8", "WINDOWS-1251", $strPostCityId);
        }

        $arData = ReaspGeoIP::GetDataName($strPostCityId);

        $arResult["STATUS"] = "N";

        if (!empty($arData)) {
            //переводим в формат json для записи в куки
            $strData = ReaspGeoIP::CodeJSON($arData);

            $_SESSION["REASPEKT_GEOBASE"] = $arData;

            $APPLICATION->set_cookie("REASPEKT_GEOBASE", $strData, time() + 31104000); // 60*60*24*30*12

            $arResult["STATUS"] = "Y";
        }

        return $arResult;
    }

    public static function GetAddr()
    {
        global $APPLICATION;
        $strData = $APPLICATION->get_cookie("REASPEKT_GEOBASE");

        //Если сессии нет
        if (
            !$strData
            || !isset($_SESSION["REASPEKT_GEOBASE"]) 
            || !is_array($_SESSION["REASPEKT_GEOBASE"]) 
            || empty($_SESSION["REASPEKT_GEOBASE"])
        ) {
            //Определяем IP
            $ip = ReaspGeoIP::getUserHostIP();

            //Проверяем  Куки
            $last_ip = $APPLICATION->get_cookie("REASPEKT_LAST_IP");
            $arData = array();

            //если данные о местоположении записаны и лежат в куки
            if (($ip == $last_ip) && $strData && count(ReaspGeoIP::deCodeJSON($strData)) > 0) {
                $arData = ReaspGeoIP::deCodeJSON($strData);
            } else {
                //Получаем данные
                $arData = ReaspGeoIP::GetDataIp($ip); // local_db

                //переводим в формат json для записи в куки
                $strData = ReaspGeoIP::CodeJSON($arData);

                //пишем куки
                $APPLICATION->set_cookie("REASPEKT_LAST_IP", $ip, time() + 31104000); // 60*60*24*30*12
                $APPLICATION->set_cookie("REASPEKT_GEOBASE", $strData, time() + 31104000); // 60*60*24*30*12                
            }

            $_SESSION["REASPEKT_GEOBASE"] = $arData;
        }

        return $_SESSION["REASPEKT_GEOBASE"];
    }

    public static function GetDataIp($ip = "")
    {
        if (!strlen(trim($ip))) {
            return false;
        }

        $arData = array();
        $arData = ReaspGeoIP::GetGeoData($ip);

        if (!empty($arData)) {
            //Приводим данные к одному формату т.к. из разных сервисов приходят разные ключи
            $arData = ReaspGeoIP::StandartFormat($arData);
        }

        return $arData;
    }

    public static function GetDataName($strName = "")
    {

        if (!strlen(trim($strName))) {
            return false;
        }

        $strName = trim($strName);

        $arData = array();
        $arData = ReaspGeoIP::GetGeoDataName($strName);

        if (!empty($arData)) {
            //Приводим данные к одному формату т.к. из разных сервисов приходят разные ключи
            $arData = ReaspGeoIP::StandartFormat($arData);
        }

        return $arData;
    }

    private static function StandartFormat($arData = array())
    {

        if (empty($arData)) {
            return false;
        }

        $arDataStandart = array();

        //Создаем группы где пересекаются значения
        $group["CITY"] = array('city','Town','UF_NAME');
        $group["COUNTRY_CODE"] = array('country','Country','UF_COUNTRY_CODE');
        $group["REGION"] = array('region','Region','UF_REGION_NAME');
        $group["OKRUG"] = array('district','UF_COUNTY_NAME');
        $group["INETNUM"] = array('inetnum','UF_BLOCK_ADDR'); // ip пределы

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
            //Если есть уникальные для сервиса поля на вс¤кий запоминаем их
            if ($status) {
                $arDataStandart[$keyCity] = $valCity;
            }
        }

        return $arDataStandart;
    }

    private static function GetGeoData($ip = "")
    {
        if (!$ip) {
            return false;
        }

        global $DB;

        //проверяем на ботов (чтоб лишний раз запросы не делать)
        if (ReaspGeoIP::InitBots()) {
            return false;
        }

        if (ReaspGeoIP::StatusTabelDB()) {
            //через таблицу
            $arIP    = explode('.', $ip);
            $codeIP = $arIP[0] * pow(256, 3) + $arIP[1] * pow(256, 2) + $arIP[2] * 256 + $arIP[3];

            $data = $DB->Query("SELECT * FROM reaspekt_geobase_codeip
                INNER JOIN reaspekt_geobase_cities ON reaspekt_geobase_codeip.UF_CITY_ID = reaspekt_geobase_cities.UF_XML_ID
                WHERE reaspekt_geobase_codeip.UF_BLOCK_BEGIN <= " . $DB->ForSql($codeIP) . " AND " . $DB->ForSql($codeIP) . " <= reaspekt_geobase_codeip.UF_BLOCK_END"
            );

            $arData = $data->Fetch();
        } else {
            $arData = array();
        }

        return $arData;
    }

    private static function GetGeoDataId($id = 0)
    {
        if (!$id) {
            return false;
        }

        global $DB;

        if ($DB->TableExists('reaspekt_geobase_cities')) {
            //через таблицу
            $data = $DB->Query("SELECT * FROM `reaspekt_geobase_cities`
                WHERE ID = '" . $DB->ForSql($id) . "'"
            );

            $arData = $data->Fetch();

        } else {
            $arData = array();
        }

        return $arData;
    }
    
    private static function GetGeoDataName($strName = "")
    {
        if (!strlen(trim($strName))) {
            return false;
        }

        global $DB;
        $arData = array();

        if ($DB->TableExists('reaspekt_geobase_cities')) {
            //через таблицу
            $data = $DB->Query("SELECT * FROM `reaspekt_geobase_cities`
                WHERE UF_NAME = '" . $DB->ForSql($strName) . "'"
            );

            $arData = $data->Fetch();
        }

        return $arData;
    }

    public static function GetGeoDataIpgeobase_ru($ip = "")
    {
        if (!strlen(trim($ip))) {
            return false;
        }

        if (!function_exists('curl_init')) {
            ShowError("Error! cURL not installed!");
            return;
        }

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, "http://ipgeobase.ru:7020/geo/?ip=" . $ip);
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);

        $text = curl_exec($ch);

        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            return false;
        }

        $text = iconv("windows-1251", SITE_CHARSET, $text);

        $arData = ReaspGeoIP::ParseXML($text);
        return ($arData);
    }

    public static function GetGeoDataGeoip_Elib_ru($ip = "")
    {
        if (!strlen(trim($ip))) {
            return false;
        }

        if (!function_exists('curl_init')) {
            ShowError("Error! cURL not installed!");
            return;
        }

        $ch = curl_init();

        $site_code = Option::get(self::MID, "reaspekt_elib_site_code");
        curl_setopt($ch, CURLOPT_URL, "http://geoip.elib.ru/cgi-bin/getdata.pl?sid=" . $site_code . "&ip=" . $ip . "&hex=3ffd");
        curl_setopt($ch, CURLOPT_HEADER, TRUE);
        curl_setopt($ch, CURLOPT_VERBOSE, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1);

        $text = curl_exec($ch);

        $errno = curl_errno($ch);
        $errstr = curl_error($ch);
        curl_close($ch);

        if ($errno) {
            return false;
        }

        $text = iconv("UTF-8", SITE_CHARSET, $text);

        $arData_ = ReaspGeoIP::ParseXML($text);
        if (isset($arData_["Error"])) {
            return false;
        }

        $arData = Array(
            "inetnum" => $ip,
            "country" => $arData_["Country"],
            "city" => $arData_["Town"],
            "region" => $arData_["Region"],
            "district" => ""
        );

        return ($arData);
    }

    public static function CheckServiceAccess($address) // Check for availability of the service
    {
        stream_context_set_default(
            array(
                'http' => array(
                    'method' => 'HEAD',
                    'timeout' => 6
                )
            )
        );
        $headers = @get_headers($address);
        if (preg_match("/(200 OK)$/", $headers[0])) {
            return true;
        }

        return false;
    }

    private static function ParseXML($text)
    {
        if (strlen($text) > 0) {

            require_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/xml.php");

            $objXML = new CDataXML();
            $res = $objXML->LoadString($text);

            if ($res !== false) {
                $arRes = $objXML->GetArray();
            }
        }

        $arRes = current($arRes);
        $arRes = $arRes["#"];
        $arRes = current($arRes);

        $ar = array();

        foreach ($arRes as $key => $arVal) {
            foreach ($arVal["#"] as $title => $Tval) {
                $ar[$key][$title] = $Tval["0"]["#"];
            }
        }

        return ($ar[0]);
    }

    private static function InitBots()
    {
        $bots = array(
            'rambler',
            'googlebot',
            'ia_archiver',
            'Wget',
            'WebAlta',
            'MJ12bot',
            'aport',
            'yahoo',
            'msnbot',
            'mail.ru',
            'alexa.com',
            'Baiduspider',
            'Speedy Spider',
            'abot',
            'Indy Library' 
        );

        foreach ($bots as $bot) {
            if (stripos($_SERVER['HTTP_USER_AGENT'], $bot) !== false) {
              return $bot;
            }
        }

        return false;
    }

    private static function CodeJSON($data)
    {
        $jsonData = CUtil::PhpToJSObject($data);

        if (SITE_CHARSET !== "UTF-8") {
            $jsonData = iconv(SITE_CHARSET, "UTF-8", $jsonData);
        }

        $jsonData = str_replace("'", '"', $jsonData);

        return $jsonData;
    }

    private static function deCodeJSON($data)
    {
        $resData = (array)json_decode($data, true);

        if (SITE_CHARSET !== "UTF-8") {
            $resData = ReaspGeoIP::iconvArrUtfTo1251($resData);
        }

        return $resData;
    }

    public static function StrReplaceStrongSearchCity($search = "", $replace = "", $strCity = "")
    {
        if (
            !$search
            || !$replace
            || !$strCity
        ) {
            return false;
        }

        $encode = 'UTF-8';

        if (SITE_CHARSET == 'windows-1251') {
            $encode = 'cp1251';

            $search = iconv("UTF-8", "WINDOWS-1251", $search);
            $replace = iconv("UTF-8", "WINDOWS-1251", $replace);
        }

        $strdata = str_replace(array($search, mb_strtolower($search, $encode)), array("<strong>" . $replace . "</strong>", "<strong>" . mb_strtolower($replace, $encode) . "</strong>") , $strCity);

        return $strdata;
    }
    
    public static function iconvArrUtfTo1251($data)
    {
        $arProp = "";

        if (is_array($data)) {
            foreach($data as $key => $Prop) {
                if (is_array($Prop)) {
                    $arProp[$key] = ReaspGeoIP::iconvArrUtfTo1251($Prop);
                } else {
                    $arProp[$key] = iconv('UTF-8', 'WINDOWS-1251', $Prop);
                }
            }
        } else {
            $arProp = iconv('UTF-8', 'WINDOWS-1251', $data);
        }

        return $arProp;
    }

    public static function iconvArrUtfToUtf8($data)
    {
        $arProp = "";

        if (is_array($data)) {
            foreach($data as $key=>$Prop) {
                if (is_array($Prop)) {
                    $arProp[$key] = ReaspGeoIP::iconvArrUtfToUtf8($Prop);
                } else {
                    $arProp[$key] = iconv('WINDOWS-1251', 'UTF-8', $Prop);
                }
            }
        } else {
            $arProp = iconv('WINDOWS-1251', 'UTF-8', $data);
        }

        return $arProp;
    }

    public static function GetIsUpdateDataFile($Host, $Path, $File, $localFilename)
    {
        $response = "N";
        if (file_exists($localFilename)) {
            $res = @fsockopen($Host, 80, $errno, $errstr, 3);

            if ($res) {
                $strRequest = "HEAD " . $Path . $File . " HTTP/1.1\r\n";
                $strRequest .= "Host: " . $Host . "\r\n";
                $strRequest .= "\r\n";

                fputs($res, $strRequest);
                while($line = fgets($res, 4096)) {
                    if (@preg_match("/Content-Length: *([0-9]+)/i", $line, $regs)) {
                        if (@filesize($localFilename) != trim($regs[1])) {
                            $response = true;
                        } else {
                            $response = false;
                        }
                        break;
                    }
                }
                fclose($res);
            }
        } else
            $response = true;
        return $response;
    }
    
    public static function getUserHostIP()
    {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            //check ip from share internet
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            //to check ip is pass from proxy
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }

        return $ip;
    }
}