<?
/**
 * Company developer: REASPEKT
 * Developer: reaspekt
 * Site: https://www.reaspekt.ru
 * E-mail: info@reaspekt.ru
 * @copyright (c) 2022 REASPEKT
 */
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Cookie; 

Loc::loadMessages(__FILE__);

class ReaspAdminGeoIP
{
    const MID = "reaspekt.geobase";

    function GetCitySelected()
    {
        
        if ($_SERVER["REQUEST_METHOD"] != "POST") {
            return false;
        }

        if (!check_bitrix_sessid('sessid') && !IsIE()) {
            return false;
        }

        if (
            !isset($_POST['city_name']) 
            && !isset($_POST['add_city'])
            && !isset($_POST['delete_city'])
            && !isset($_POST['update'])
        ) {            
            return false; 
        } elseif (
            empty($_POST['city_name']) 
            && empty($_POST['add_city'])
            && empty($_POST['delete_city'])
            && empty($_POST['update'])
        ) {
            die('pusto');
        } elseif (isset($_POST['city_name'])) { // search cities
            return ReaspAdminGeoIP::CitySearch(true);
        } elseif (isset($_POST['add_city']) && $_POST['add_city'] == 'Y') { // add city
            if (isset($_POST['city_id'])) {
                global $DB;
                $city_id = $DB->ForSql(htmlspecialchars($_POST['city_id']));

                $sites = CSite::GetList($by = "sort", $order = "desc", Array("ACTIVE" => "Y"));
                while($Site = $sites->Fetch()) {
                    BXClearCache(true, $Site["LID"] . "/reaspekt/geobase/");
                }

                return(ReaspAdminGeoIP::AddSetCity($city_id));
            }
        } elseif (isset($_POST['update']) && $_POST['update'] == 'Y') { // restart html table
            return(ReaspAdminGeoIP::UpdateCityRows());
        } elseif (isset($_POST['delete_city']) && $_POST['delete_city'] == 'Y') {
            if (isset($_POST['entry_id'])) {
                global $DB;
                $city_id = $DB->ForSql(htmlspecialchars($_POST['entry_id']));

                $sites = CSite::GetList($by = "sort", $order = "desc", Array("ACTIVE" => "Y"));
                while ($Site = $sites->Fetch()) {
                    BXClearCache(true, $Site["LID"]."/reaspekt/geobase/");
                }

                return (ReaspAdminGeoIP::DeleteCity($city_id));
            }
        }
    }

    function CitySearch($adminSection = false)
    {
        $city = trim(urldecode($_POST['city_name']));

        if (SITE_CHARSET == 'windows-1251') {
            $city1 = @iconv("UTF-8", "windows-1251//IGNORE", $city); // All AJAX requests come in Unicode
            if ($city1) {
                $city = $city1;    // if used Windows-machine
            }
        }

        $city = addslashes($city);
        $citylen = strlen($city);

        $arCity = array();
        $i = 0;

        if (isset($_POST['lang']) && strtolower($_POST['lang']) == "ru") { // LANGUAGE_ID
            if ($citylen > 1) {
                $arCity = ReaspGeoIP::SelectQueryCity($city);

                if (SITE_CHARSET == 'windows-1251') {
                    $arCity = ReaspGeoIP::iconvArrUtfToUtf8($arCity);
                }
            }
        }

        echo json_encode($arCity);
    }

    function AddSetCity($city_id)
    {
        global $DB;

        $arCity = ReaspGeoIP::SelectCityId($city_id);

        if ($arCity["ID"]) {
            //Смотрим настройки модуля
            $reaspekt_city_manual_default = Option::get(self::MID, "reaspekt_city_manual_default");
            $ar_reaspekt_city_manual_default = unserialize($reaspekt_city_manual_default);
            $ar_reaspekt_city_manual_default[] = $arCity["ID"];
            //Убираем дубли
            $ar_reaspekt_city_manual_default = array_unique($ar_reaspekt_city_manual_default);
            $reaspekt_city_manual_default = serialize($ar_reaspekt_city_manual_default);
            Option::set(self::MID, "reaspekt_city_manual_default", $reaspekt_city_manual_default);
        } else {
            return false;
        }

        return $arCity["ID"];
    }

    function UpdateCityRows()
    {
        $reaspekt_city_manual_default = Option::get(self::MID, "reaspekt_city_manual_default");
        $ar_reaspekt_city_manual_default = unserialize($reaspekt_city_manual_default);
        $arCityData = ReaspGeoIP::SelectCityIdArray($ar_reaspekt_city_manual_default, true);

        $strCityDefaultTR = "";

        foreach ($ar_reaspekt_city_manual_default as $idCity) {
            $strCityDefaultTR .= '<tr class="reaspekt_geobase_city_line">';
            $strCityDefaultTR .= "<td>" . $arCityData[$idCity]["ID"] . "</td>";
            $strCityDefaultTR .= "<td>" . $arCityData[$idCity]["UF_XML_ID"] . "</td>";
            $strCityDefaultTR .= "<td>" . (($arCityData[$idCity]["UF_ACTIVE"]) ? Loc::getMessage("REASPEKT_GEOBASE_ACTIVE_CITY_TRUE") : Loc::getMessage("REASPEKT_GEOBASE_ACTIVE_CITY_FALSE")) . "</td>";
            $strCityDefaultTR .= "<td>" . $arCityData[$idCity]["CITY"] . "</td>";
            $strCityDefaultTR .= "<td>" . $arCityData[$idCity]["REGION"] . "</td>";
            $strCityDefaultTR .= "<td>" . $arCityData[$idCity]["OKRUG"] . "</td>";
            $strCityDefaultTR .= '<td><input type="submit" name="reaspekt_geobase_del_' . $idCity . '" value="' . GetMessage("REASPEKT_TABLE_CITY_DELETE") . '" onclick="reaspekt_geobase_delete_click(' . $idCity . ');return false;"></td>';
            $strCityDefaultTR .= "</tr>";
        }

        echo $strCityDefaultTR;
    }

    function DeleteCity($ID)
    {
        global $DB;
        $ID = IntVal($ID);

        if($ID <= 0)
            return false;

        $reaspekt_city_manual_default = Option::get(self::MID, "reaspekt_city_manual_default");
        $ar_reaspekt_city_manual_default = unserialize($reaspekt_city_manual_default);

        foreach ($ar_reaspekt_city_manual_default as $keyCity => &$idCity) {
            if (intval($idCity) == $ID) {
                unset($ar_reaspekt_city_manual_default[$keyCity]);
            }
        }

        $reaspekt_city_manual_default = serialize($ar_reaspekt_city_manual_default);

        Option::set(self::MID, "reaspekt_city_manual_default", $reaspekt_city_manual_default);

        return true;
    }

    function cidrToRange($cidr)
    {
        $range = array();
        $cidr = explode('/', $cidr);
        $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
        $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 1);
        return $range;
    }

    function SetCurrentStatus($str)
    {
        global $strLog;
        $strLog .= $str."\n";
    }

    function SetCurrentProgress($cur, $total = 0)
    {
        global $status;
        if (!$total) {
            $total  = 100;
            $cur    = 0;
        }
        $val = intval($cur / $total * 100);
        if ($val > 100) {
            $val = 100;
        }

        $status = $val;
    }

    function reaspekt_geobase_getmicrotime()
    {
        list($usec, $sec) = explode(" ", microtime());
        return ((float)$usec + (float)$sec);
    }

    function json_encode_cyr($str)
    {
        $arr_replace_utf = array(   'null', '\u0410', '\u0430','\u0411','\u0431','\u0412','\u0432',
            '\u0413','\u0433','\u0414','\u0434','\u0415','\u0435','\u0401','\u0451','\u0416',
            '\u0436','\u0417','\u0437','\u0418','\u0438','\u0419','\u0439','\u041a','\u043a',
            '\u041b','\u043b','\u041c','\u043c','\u041d','\u043d','\u041e','\u043e','\u041f',
            '\u043f','\u0420','\u0440','\u0421','\u0441','\u0422','\u0442','\u0423','\u0443',
            '\u0424','\u0444','\u0425','\u0445','\u0426','\u0446','\u0427','\u0447','\u0428',
            '\u0448','\u0429','\u0449','\u042a','\u044a','\u042b','\u044b','\u042c','\u044c',
            '\u042d','\u044d','\u042e','\u044e','\u042f','\u044f');

        $arr_replace_cyr = array('false', 'А', 'а', 'Б', 'б', 'В', 'в', 'Г', 'г', 'Д', 'д', 'Е', 'е',
            'Ё', 'ё', 'Ж','ж','З','з','И','и','Й','й','К','к','Л','л','М','м','Н','н','О','о',
            'П','п','Р','р','С','с','Т','т','У','у','Ф','ф','Х','х','Ц','ц','Ч','ч','Ш','ш',
            'Щ','щ','Ъ','ъ','Ы','ы','Ь','ь','Э','э','Ю','ю','Я','я');

        $str1 = json_encode($str, JSON_FORCE_OBJECT);
        $str2 = str_replace($arr_replace_utf,$arr_replace_cyr,$str1);
        return $str2;
    }

    function getApiKey()
    {
        $reaspekt_city_apikey = Option::get(self::MID, "reaspekt_set_apikey");
        return $reaspekt_city_apikey;
    }

    function LoadFile($strRequestedUrl, $strFilename, $iTimeOut)
    {
        global $strUserAgent;
        $iTimeOut = IntVal($iTimeOut);
        if ($iTimeOut > 0) {
            $start_time = ReaspAdminGeoIP::reaspekt_geobase_getmicrotime();
        }
        $strRealUrl = $strRequestedUrl;
        $iStartSize = 0;

        // Initialize if spool download
        $strRealUrl_tmp = "";
        $iRealSize_tmp = 0;
        if (file_exists ($strFilename . ".tmp") && file_exists ($strFilename . ".log") && filesize ($strFilename . ".log") > 0) {
            $fh = fopen ($strFilename . ".log", "rb");
            $file_contents_tmp = fread ($fh, filesize ($strFilename . ".log"));
            fclose ($fh);

            list($strRealUrl_tmp, $iRealSize_tmp) = preg_split ("/\n/", $file_contents_tmp);
            $strRealUrl_tmp = Trim($strRealUrl_tmp);
            $iRealSize_tmp = Trim($iRealSize_tmp);
        }
        if ($iRealSize_tmp <= 0 || strlen ($strRealUrl_tmp) <= 0) {
            if (file_exists ($strFilename . ".tmp"))
                @unlink ($strFilename . ".tmp");
            if (file_exists ($strFilename . ".log"))
                @unlink ($strFilename . ".log");
        } else {
            $strRealUrl = $strRealUrl_tmp;
            $iStartSize = filesize ($strFilename . ".tmp");
        }

        // END: Initialize if spool download

        // Look for a file and requests INFO
        do {
            $lasturl    = $strRealUrl;
            $parsedUrl  = parse_url ($strRealUrl);
            $host       = $parsedUrl["host"];
            $port       = $parsedUrl["port"];
            $hostName   = $host;
            $port       = $port ? $port : "80";

            $socketHandle = fsockopen ($host, $port, $error_id, $error_msg, 30);
            if (!$socketHandle) {
                return false;
            } else {
                if (!$parsedUrl["path"]) {
                    $parsedUrl["path"] = "/";
                }
                $request = "";
                $request .= "HEAD " . $parsedUrl["path"] . ($parsedUrl["query"] ? '?' . $parsedUrl["query"] : '') . " HTTP/1.0\r\n";
                $request .= "Host: $hostName\r\n";
                if ($strUserAgent != "") $request .= "User-Agent: $strUserAgent\r\n";
                $request .= "\r\n";
                fwrite ($socketHandle, $request);
                $replyHeader = "";
                while (($result = fgets ($socketHandle, 4024)) && $result != "\r\n") {
                    $replyHeader .= $result;
                }
                fclose ($socketHandle);
                $ar_replyHeader = preg_split ("/\r\n/", $replyHeader);
                $replyCode = 0;
                $replyMsg = "";
                if (preg_match("#([A-Z]{4})/([0-9.]{3}) ([0-9]{3})#", $ar_replyHeader[0], $regs)) {
                    $replyCode = IntVal ($regs[3]);
                    $replyMsg = substr ($ar_replyHeader[0], strpos ($ar_replyHeader[0], $replyCode) + strlen ($replyCode) + 1, strlen ($ar_replyHeader[0]) - strpos ($ar_replyHeader[0], $replyCode) + 1);
                }
                if ($replyCode != 200 && $replyCode != 302) {
                    if ($replyCode == 403) ReaspAdminGeoIP::SetCurrentStatus (Loc::getMessage ("LOADER_LOAD_SERVER_ANSWER1")); else
                        ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#ANS#", $replyCode . " - " . $replyMsg, Loc::getMessage ("LOADER_LOAD_SERVER_ANSWER")) . '<br>' . htmlspecialchars ($strRequestedUrl));
                    return false;
                }
                $strLocationUrl = "";
                $iNewRealSize = 0;
                $strAcceptRanges = "";
                for ($i = 1; $i < count ($ar_replyHeader); $i++) {
                    if (strpos ($ar_replyHeader[$i], "Location") !== false) $strLocationUrl = trim (substr ($ar_replyHeader[$i], strpos ($ar_replyHeader[$i], ":") + 1, strlen ($ar_replyHeader[$i]) - strpos ($ar_replyHeader[$i], ":") + 1)); elseif (strpos ($ar_replyHeader[$i], "Content-Length") !== false) $iNewRealSize = IntVal (Trim (substr ($ar_replyHeader[$i], strpos ($ar_replyHeader[$i], ":") + 1, strlen ($ar_replyHeader[$i]) - strpos ($ar_replyHeader[$i], ":") + 1))); elseif (strpos ($ar_replyHeader[$i], "Accept-Ranges") !== false) $strAcceptRanges = Trim (substr ($ar_replyHeader[$i], strpos ($ar_replyHeader[$i], ":") + 1, strlen ($ar_replyHeader[$i]) - strpos ($ar_replyHeader[$i], ":") + 1));
                }
                if (strlen ($strLocationUrl) > 0) {
                    $redirection = $strLocationUrl;
                    if ((strpos ($redirection, "http://") === false)) {
                        $strRealUrl = dirname ($lasturl) . "/" . $redirection;
                    } else {
                        $strRealUrl = $redirection;
                    }
                }
                if (strlen ($strLocationUrl) <= 0) {
                    break;
                }
            }
        } while (true);
        // END: Look for a file and requests INFO

        $bCanContinueDownload = ($strAcceptRanges == "bytes");

        // If it is possible to complete the download
        if ($bCanContinueDownload) {
            $fh = fopen ($strFilename . ".log", "wb");
            if (!$fh) {
                ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#FILE#", $strFilename . ".log", Loc::getMessage ("LOADER_LOAD_NO_WRITE2FILE")));
                return false;
            }
            fwrite ($fh, $strRealUrl . "\n");
            fwrite ($fh, $iNewRealSize . "\n");
            fclose ($fh);
        }
        // END: If it is possible to complete the download

        // download file
        $parsedUrl = parse_url($strRealUrl);
        $host = $parsedUrl["host"];
        $port = $parsedUrl["port"];
        $hostName = $host;
        $port = $port ? $port : "80";

        ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#HOST#", $host, Loc::getMessage ("LOADER_LOAD_CONN2HOST")));

        $socketHandle = fsockopen ($host, $port, $error_id, $error_msg, 30);
        if (!$socketHandle) {
            ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#HOST#", $host, Loc::getMessage ("LOADER_LOAD_NO_CONN2HOST")) . " [" . $error_id . "] " . $error_msg);
            return false;
        } else {
            if (!$parsedUrl["path"]) $parsedUrl["path"] = "/";

            ReaspAdminGeoIP::SetCurrentStatus (Loc::getMessage ("LOADER_LOAD_QUERY_FILE"));

            $request = "";
            $request .= "GET " . $parsedUrl["path"] . ($parsedUrl["query"] ? '?' . $parsedUrl["query"] : '') . " HTTP/1.0\r\n";
            $request .= "Host: $hostName\r\n";

            if ($strUserAgent != "") $request .= "User-Agent: $strUserAgent\r\n";
            if ($bCanContinueDownload && $iStartSize > 0) $request .= "Range: bytes=" . $iStartSize . "-\r\n";

            $request .= "\r\n";

            fwrite ($socketHandle, $request);

            $result = "";
            ReaspAdminGeoIP::SetCurrentStatus (Loc::getMessage ("LOADER_LOAD_WAIT"));
            $replyHeader = "";
            while (($result = fgets ($socketHandle, 4096)) && $result != "\r\n")
                $replyHeader .= $result;
            $ar_replyHeader = preg_split ("/\r\n/", $replyHeader);
            $replyCode = 0;
            $replyMsg = "";
            if (preg_match("#([A-Z]{4})/([0-9.]{3}) ([0-9]{3})#", $ar_replyHeader[0], $regs)) {
                $replyCode = IntVal ($regs[3]);
                $replyMsg = substr ($ar_replyHeader[0], strpos ($ar_replyHeader[0], $replyCode) + strlen ($replyCode) + 1, strlen ($ar_replyHeader[0]) - strpos ($ar_replyHeader[0], $replyCode) + 1);
            }
            if ($replyCode != 200 && $replyCode != 302 && $replyCode != 206) {
                ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#ANS#", $replyCode . " - " . $replyMsg, Loc::getMessage ("LOADER_LOAD_SERVER_ANSWER")));
                return false;
            }
            $strContentRange = "";
            $iContentLength = 0;
            for ($i = 1; $i < count ($ar_replyHeader); $i++) {
                if (strpos ($ar_replyHeader[$i], "Content-Range") !== false) $strContentRange = trim (substr ($ar_replyHeader[$i], strpos ($ar_replyHeader[$i], ":") + 1, strlen ($ar_replyHeader[$i]) - strpos ($ar_replyHeader[$i], ":") + 1)); elseif (strpos ($ar_replyHeader[$i], "Content-Length") !== false) $iContentLength = doubleval (Trim (substr ($ar_replyHeader[$i], strpos ($ar_replyHeader[$i], ":") + 1, strlen ($ar_replyHeader[$i]) - strpos ($ar_replyHeader[$i], ":") + 1))); elseif (strpos ($ar_replyHeader[$i], "Accept-Ranges") !== false) $strAcceptRanges = Trim (substr ($ar_replyHeader[$i], strpos ($ar_replyHeader[$i], ":") + 1, strlen ($ar_replyHeader[$i]) - strpos ($ar_replyHeader[$i], ":") + 1));
            }
            $bReloadFile = True;
            if (strlen ($strContentRange) > 0) {
                if (preg_match("# *bytes +([0-9]*) *- *([0-9]*) */ *([0-9]*)#", $strContentRange, $regs)) {
                    $iStartBytes_tmp = doubleval ($regs[1]);
                    $iEndBytes_tmp = doubleval ($regs[2]);
                    $iSizeBytes_tmp = doubleval ($regs[3]);

                    if ($iStartBytes_tmp == $iStartSize && $iEndBytes_tmp == ($iNewRealSize - 1) && $iSizeBytes_tmp == $iNewRealSize) {
                        $bReloadFile = False;
                    }
                }
            }
            if ($bReloadFile) {
                @unlink ($strFilename . ".tmp");
                $iStartSize = 0;
            }
            if (($iContentLength + $iStartSize) != $iNewRealSize) {
                ReaspAdminGeoIP::SetCurrentStatus (Loc::getMessage ("LOADER_LOAD_ERR_SIZE"));
                return false;
            }
            $fh = fopen ($strFilename . ".tmp", "ab");
            if (!$fh) {
                ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#FILE#", $strFilename . ".tmp", Loc::getMessage ("LOADER_LOAD_CANT_OPEN_WRITE")));
                return false;
            }
            $bFinished = True;
            $downloadsize = (double)$iStartSize;
            ReaspAdminGeoIP::SetCurrentStatus (Loc::getMessage ("LOADER_LOAD_LOADING"));
            while (!feof ($socketHandle)) {
                if ($iTimeOut > 0 && (ReaspAdminGeoIP::reaspekt_geobase_getmicrotime() - $start_time) > $iTimeOut) {
                    $bFinished = False;
                    break;
                }
                $result = fread ($socketHandle, 256 * 1024);
                $downloadsize += strlen ($result);
                if ($result == "") break;
                fwrite ($fh, $result);
            }
            ReaspAdminGeoIP::SetCurrentProgress ($downloadsize, $iNewRealSize);
            fclose ($fh);
            fclose ($socketHandle);
            if ($bFinished) {
                @unlink ($strFilename);
                if (!@rename ($strFilename . ".tmp", $strFilename)) {
                    ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#FILE2#", $strFilename, str_replace ("#FILE1#", $strFilename . ".tmp", Loc::getMessage ("LOADER_LOAD_ERR_RENAME"))));
                    return false;
                }
                @unlink ($strFilename . ".tmp");
            } else
                return 3;

            ReaspAdminGeoIP::SetCurrentStatus (str_replace ("#SIZE#", $downloadsize, str_replace ("#FILE#", $strFilename, Loc::getMessage ("LOADER_LOAD_FILE_SAVED"))));
            @unlink ($strFilename . ".log");
            return 2;
        }
    // END: download file
    }
}