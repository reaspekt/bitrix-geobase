<?
/**
 * Company developer: REASPEKT
 * Developer: reaspekt
 * Site: https://www.reaspekt.ru
 * E-mail: info@reaspekt.ru
 * @copyright (c) 2022 REASPEKT
 */
use \Bitrix\Main\Application;
use \Bitrix\Main\Config\Configuration;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Page\Asset; 

IncludeModuleLangFile(__FILE__);

$arClassesList = array(
	// main classes
	"ReaspGeoIP"	    => "classes/general/geoip.php",
	"ReaspAdminGeoIP"   => "classes/general/geoip_admin.php",
	// API classes
);

function GetPathLoadClasses($notDocumentRoot = false) {
	if ($notDocumentRoot) {
		return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
	} else {
		return dirname(__DIR__);
	}
}

$nameCompany = "reaspekt";

// fix strange update bug
if (method_exists(CModule, "AddAutoloadClasses")) {
	$asd = CModule::AddAutoloadClasses(
		$nameCompany . ".geobase",
		$arClassesList
	);
} else {
	foreach ($arClassesList as $sClassName => $sClassFile) {
		require_once(GetPathLoadClasses() . "/" . $nameCompany . ".geobase/" . $sClassFile);
	}
}

class ReaspGeoBaseLoad {
	const MID = "reaspekt.geobase";

	function OnPrologHandler() {
		global $APPLICATION;
		if (IsModuleInstalled(self::MID)) {
			if (!defined(ADMIN_SECTION) && ADMIN_SECTION !== true) {
				Asset::getInstance()->addJs("/bitrix/js/main/core/core.min.js", true);
				return true;
			}
		}
	}
}