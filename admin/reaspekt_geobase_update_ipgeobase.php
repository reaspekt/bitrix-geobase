<?require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
\Bitrix\Main\Loader::includeModule("main");
\Bitrix\Main\Loader::includeModule("reaspekt.geobase");
\Bitrix\Main\Loader::includeModule("highloadblock");
use Bitrix\Highloadblock as HL;

$nameCompany = "reaspekt";

include_once($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/classes/general/admin_notify.php");
$response = array();

ob_implicit_flush(true);
set_time_limit(1800);

if (@preg_match('#ru#i', $_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
	$lang = 'ru';
}
if ($_REQUEST['lang']) {
	$lang = htmlspecialchars($_REQUEST['lang']);
}
if (!in_array($lang, ['ru', 'en'])) {
	$lang = 'en';
}

$_REQUEST['timeout'] = intval($_REQUEST['timeout']);
$API_KEY = ReaspAdminGeoIP::getApiKey();

define("LANG", $lang);
define('LOAD_HOST', 'download.maxmind.com');
define('LOAD_PATH', '/app/geoip_download');
define('LOAD_FILE', '?edition_id=GeoLite2-City-CSV&license_key=' . $API_KEY . '&suffix=zip');

define('TIMEOUT', ($_REQUEST['timeout'] > 120 ? 120 : htmlspecialchars($_REQUEST['timeout'])));

$strRequestedUrl = 'https://' . LOAD_HOST . LOAD_PATH . LOAD_FILE;
$strFilename = $_SERVER["DOCUMENT_ROOT"] . "/upload/" . $nameCompany . "/geobase/maxmind.zip";

$this_script_name = basename(__FILE__);
$folderName = "";

umask(0);
if (!defined("AS_DIR_PERMISSIONS"))
	define("AS_DIR_PERMISSIONS", 0777);

if (!defined("AS_FILE_PERMISSIONS"))
	define("AS_FILE_PERMISSIONS", 0777);

####### MESSAGES ########
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
####### MESSAGES ########

$strAction = htmlspecialchars($_REQUEST["action"]);

if ($strAction == "LOAD") {
	/*********************************************************************/
	$iTimeOut = TIMEOUT;
	$strUserAgent = "ReaspektGeoBaseLoader";

	$strLog = '';
	$status = '';
	$res = ReaspAdminGeoIP::LoadFile($strRequestedUrl, $strFilename, $iTimeOut);

	if (!$res) {
		$response["STATUS"]	 = $res;
		$response["PROGRESS"]   = $status;
		$response["NEXT_STEP"]  = false;
		$response["MESSAGE"]	= nl2br($strLog);
	} elseif ($res == 3) { // partial downloading
		$response["STATUS"]	 = $res;
		$response["PROGRESS"]   = $status;
		$response["NEXT_STEP"]  = "LOAD";
	} elseif ($res == 2) {	
        	$response["STATUS"]	= $res;
		$response["PROGRESS"] = $status;
		$response["NEXT_STEP"] = "UNPACK";
		$response["BY_STEP"] = "Y";
		$response["FILENAME"] = urlencode(basename($strRequestedUrl));
	}

/*********************************************************************/
} elseif ($strAction == "UNPACK") {
	ReaspAdminGeoIP::SetCurrentStatus(Loc::getMessage("LOADER_UNPACK_ACTION"));

	$zip = new ZipArchive;
	$arFiles = ['GeoLite2-City-Blocks-IPv4.csv', 'GeoLite2-City-Locations-ru.csv'];
	$zipPath = $_SERVER["DOCUMENT_ROOT"] . "/upload/" . $nameCompany . "/geobase/maxmind.zip";
	$res = $zip->open($zipPath);
	if ($res === TRUE) {
		$response["FILENAMES"] = "";
		for ($i = 0; $i < $zip->numFiles; $i++) {
			$filename = $zip->getNameIndex($i);
			$fileinfo = pathinfo($filename);
			if (in_array($fileinfo['basename'], $arFiles)) {
				copy("zip://" . $zipPath . "#" . $filename, $_SERVER["DOCUMENT_ROOT"] . "/upload/" . $nameCompany . "/geobase/" . $fileinfo['basename']);
			}
		}
		$zip->close();
		$uRes = true;
	} else {
		$uRes = false;
	}

	if ($uRes) {
		$response["STATUS"] = 1;
		$response["PROGRESS"]   = 100;
		$response["NEXT_STEP"]  = "DBUPDATE";
		$response["FILENAME"]   = urlencode(basename('GeoLite2-City-Blocks-IPv4.csv'));
		$response["FOLDER_NAME"]   = "";
		$response["DROP_T"]	 = "C";
		@unlink($_SERVER["DOCUMENT_ROOT"] . "/upload/" . $nameCompany . "/geobase/" . "maxmind.zip" . ".log");
		@unlink($_SERVER["DOCUMENT_ROOT"] . "/upload/" . $nameCompany . "/geobase/" . "maxmind.zip" . ".tmp");

		\Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $nameCompany . '/geobase/maxmind.zip');
		ReaspAdminGeoIP::SetCurrentStatus(Loc::getMessage("LOADER_UNPACK_DELETE"));
	} else {
		ReaspAdminGeoIP::SetCurrentStatus(Loc::getMessage("LOADER_UNPACK_ERRORS"));
	}
	/*********************************************************************/
} elseif ($strAction == "DBUPDATE") {
	$iTimeOut = TIMEOUT;
	if ($iTimeOut > 0) {
		$start_time = ReaspAdminGeoIP::reaspekt_geobase_getmicrotime();
	}
    
	//Очистка HL
	if ($_REQUEST["drop_t"] == 'C') {
		$arTable = array("reaspekt_geobase_cities", "reaspekt_geobase_codeip");
		
		foreach ($arTable as $nameSqlTable) {
			if($DB->TableExists($nameSqlTable)){
				$DB->Query('TRUNCATE TABLE `' . $nameSqlTable . '`');
			}
		}
	}

	switch ($_REQUEST["filename"]) {
		case "GeoLite2-City-Blocks-IPv4.csv": {
			$positionCount = 0;
			$FPath = '/upload/' . $nameCompany . '/geobase' . ($_REQUEST["folder_name"] ? '/' . $_REQUEST["folder_name"] : '') . '/GeoLite2-City-Blocks-IPv4.csv';
			$fileSize = filesize($_SERVER["DOCUMENT_ROOT"] . $FPath);
			$f = fopen($_SERVER["DOCUMENT_ROOT"] . $FPath, 'r');
			$_REQUEST["seek"] ? fseek($f, $_REQUEST["seek"]) : false;
			
			if (!$DB->TableExists('reaspekt_geobase_codeip')) {
				$highloadBlockData = array (
					'NAME' => 'ReaspektGeobaseCodeip',
					'TABLE_NAME' => 'reaspekt_geobase_codeip'
				);

				//Making HL
				$obResult = HL\HighloadBlockTable::add($highloadBlockData);

				//Success
				if ($obResult->isSuccess()) {
					$arFieldsCodeIp = array(
						"ACTIVE" => "boolean",
						"BLOCK_BEGIN" => "string",
						"BLOCK_END" => "string",
						"BLOCK_ADDR" => "string",
						"COUNTRY_CODE" => "string",
						"CITY_ID" => "string",
					);
					
					$userTypeEntity = new CUserTypeEntity();
					
					foreach ($arFieldsCodeIp as $nameField => $typeField) {
						$userTypeData = array(
							'ENTITY_ID' => "HLBLOCK_".$obResult->getId(),
							'FIELD_NAME' => "UF_".$nameField,
							'USER_TYPE_ID' => $typeField,
							'MANDATORY' => 'N',
							'SHOW_FILTER' => 'S',
							'IS_SEARCHABLE' => 'N',
							'EDIT_FORM_LABEL'   => array(
								'ru'    => $nameField,
								'en'    => $nameField,
							),
							'LIST_COLUMN_LABEL' => array(
								'ru'    => $nameField,
								'en'    => $nameField,
							),
							'LIST_FILTER_LABEL' => array(
								'ru'    => $nameField,
								'en'    => $nameField,
							),
						);

						if ($typeField == "boolean") {
							$userTypeData["SETTINGS"]["DEFAULT_VALUE"] = 1;
							$userTypeData["SETTINGS"]["DISPLAY"] = "CHECKBOX";
						}

						$userTypeId = $userTypeEntity->Add( $userTypeData );
					}
				}
			}
			
			$bFinished = true;
			$strFields =    "UF_ACTIVE, "
						."UF_BLOCK_BEGIN, "
						."UF_BLOCK_END, "
						."UF_BLOCK_ADDR, "
						."UF_COUNTRY_CODE, "
						."UF_CITY_ID";
			while (!feof ($f)) {
				$positionCount++;
				if ($positionCount > 5000) {
					$bFinished = False;
					break;
				}
				$strVar = fgets($f);
				
				if(trim($strVar) !== ''){
					$arValues = explode(',' , preg_replace("/\t/", ',', $strVar));
                    
					if(!empty($arValues) && intval($arValues[1]) > 0){
						$ipRanges = ReaspAdminGeoIP::cidrToRange($arValues[0]);
						$strValues .=   (!!strlen($strValues) ? ', ' : '')
							.'( 1, '
							. "'".$DB->ForSql($ipRanges[0]) ."', "
							. "'".$DB->ForSql($ipRanges[1]) ."', "
							."'".$DB->ForSql($arValues[0])."', "
							."'".intval($arValues[2])."', "
							.intval($arValues[1]).')';
					}
				}
			}

			$DB->Query('INSERT INTO reaspekt_geobase_codeip ('.$strFields.') VALUES '.$strValues);
			ReaspAdminGeoIP::SetCurrentProgress (ftell($f), $fileSize);
			if ($bFinished){
				$response = array(
					"STATUS"	=> 1,
					"PROGRESS"  => 100,
					"NEXT_STEP" => "DBUPDATE",
					"FILENAME"  => urlencode(basename("GeoLite2-City-Locations-ru.csv")),
					"FOLDER_NAME"  => urlencode($_REQUEST["folder_name"]),
					"SEEK"	  => 0,
					"DROP_T"	=> "N",
					"MES"	   => iconv("cp1251", "UTF-8", Loc::getMessage('REASPEKT_GEOBASE_TABLE_CODEIP_UPDATED'))
				);
			} else {
				$response = array(
					"STATUS"	=> 1,
					"PROGRESS"  => $status,
					"NEXT_STEP" => "DBUPDATE",
					"SEEK"	  => ftell($f),
					"SIZE"	  => $fileSize,
					"FILENAME"  => urlencode(basename("GeoLite2-City-Blocks-IPv4.csv")),
					"FOLDER_NAME"  => urlencode($_REQUEST["folder_name"]),
					"DROP_T"	=> "N"
				);
			}
			break;
		}
		case "GeoLite2-City-Locations-ru.csv": {
			$positionCount = 0;
			$FPath = '/upload/' . $nameCompany . '/geobase' . ($_REQUEST["folder_name"] ? '/' . $_REQUEST["folder_name"] : '') . '/GeoLite2-City-Locations-ru.csv';
			$fileSize = filesize($_SERVER["DOCUMENT_ROOT"].$FPath);
			$f = fopen($_SERVER["DOCUMENT_ROOT"].$FPath, 'r');
			$_REQUEST["seek"] ? fseek($f, $_REQUEST["seek"]) : false;
		
			if (!$DB->TableExists('reaspekt_geobase_cities')) {

				$highloadBlockData = array (
					'NAME' => 'ReaspektGeobaseCities',
					'TABLE_NAME' => 'reaspekt_geobase_cities'
				);

				$obResult = HL\HighloadBlockTable::add($highloadBlockData);

				if ($obResult->isSuccess()) {

					$arFieldsCodeIp = array(
						"XML_ID" => "integer",
						"ACTIVE" => "boolean",
						"NAME" => "string",
						"REGION_NAME" => "string",
						"COUNTY_NAME" => "string",
					);
					
					$userTypeEntity = new CUserTypeEntity();
					
					foreach ($arFieldsCodeIp as $nameField => $typeField) {
						$userTypeData = array(
							'ENTITY_ID' => "HLBLOCK_".$obResult->getId(),
							'FIELD_NAME' => "UF_".$nameField,
							'USER_TYPE_ID' => $typeField,
							'MANDATORY' => 'N',
							'SHOW_FILTER' => 'S',
							'IS_SEARCHABLE' => 'N',
							'EDIT_FORM_LABEL'   => array(
								'ru'    => $nameField,
								'en'    => $nameField,
							),
							'LIST_COLUMN_LABEL' => array(
								'ru'    => $nameField,
								'en'    => $nameField,
							),
							'LIST_FILTER_LABEL' => array(
								'ru'    => $nameField,
								'en'    => $nameField,
							),
						);

						if ($typeField == "boolean") {
							$userTypeData["SETTINGS"]["DEFAULT_VALUE"] = 1;
							$userTypeData["SETTINGS"]["DISPLAY"] = "CHECKBOX";
						}

						if ($typeField == "integer") {
							$userTypeData["SETTINGS"]["DEFAULT_VALUE"] = "";
							$userTypeData["SETTINGS"]["SIZE"] = "20";
							$userTypeData["SETTINGS"]["MIN_VALUE"] = "0";
							$userTypeData["SETTINGS"]["MAX_VALUE"] = "0";
						}

						$userTypeId = $userTypeEntity->Add( $userTypeData );
					}
				}
			}

			$bFinished = true;
			$strFields  =   "UF_XML_ID, "
						."UF_ACTIVE, "
						."UF_NAME, "
						."UF_REGION_NAME, "
						."UF_COUNTY_NAME";

			while (!feof ($f)) {
				$positionCount++;
				if ($positionCount > 5000) {
					$bFinished = False;
					break;
				}

				$arValues = explode(',' ,preg_replace("/\t/", ',', fgets($f)));
                
				if (intval($arValues[0])) {
					$strValues .= (!!strlen($strValues) ? ", " : "")
								. "(".intval($arValues[0]).", "
								. "1, "
								. "'".$DB->ForSql(str_replace('"', '', $arValues[10])) ."', "
								. "'".$DB->ForSql(str_replace('"', '', $arValues[7])) ."', "
								. "'".$DB->ForSql(str_replace('"', '', $arValues[5])) ."')";
				}
			}

			$DB->Query('INSERT INTO reaspekt_geobase_cities ('.$strFields.') VALUES '.$strValues);

			ReaspAdminGeoIP::SetCurrentProgress (ftell($f), $fileSize);

			if ($bFinished) {
				$response = array(
					"STATUS"	=> 0,
					"PROGRESS"  => 100,
					"MES"	   => iconv("cp1251", "UTF-8", Loc::getMessage('REASPEKT_GEOBASE_TABLE_CODEIP_UPDATED'))
				);

				\Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $nameCompany . '/geobase/GeoLite2-City-Blocks-IPv4.csv');
				\Bitrix\Main\IO\File::deleteFile($_SERVER['DOCUMENT_ROOT'] . '/upload/' . $nameCompany . '/geobase/GeoLite2-City-Locations-ru.csv');
			} else {
				$response = array(
					"STATUS"	=> 1,
					"PROGRESS"  => $status,
					"NEXT_STEP" => "DBUPDATE",
					"SEEK"	  => ftell($f),
					"SIZE"	  => $fileSize,
					"FILENAME"  => urlencode(basename("GeoLite2-City-Locations-ru.csv")),
					"FOLDER_NAME"  => urlencode($_REQUEST["folder_name"]),
					"DROP_T"	=> "N"
				);
			}
			break;
		}
	}
}

########### JSON #########
print ReaspAdminGeoIP::json_encode_cyr($response);
##########################
