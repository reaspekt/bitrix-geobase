<?
/**
 * Company developer: REASPEKT
 * Developer: adel yusupov
 * Site: http://www.reaspekt.ru
 * E-mail: adel@reaspekt.ru
 * @copyright (c) 2016 REASPEKT
 */
use \Bitrix\Main\Application;
use \Bitrix\Main\Config\Configuration;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Page\Asset; 

IncludeModuleLangFile(__FILE__);

function GetPathLoadClasses($notDocumentRoot = false)
{
	if ($notDocumentRoot) {
		return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
	} else {
		return dirname(__DIR__);
	}
}

$nameCompany = "reaspekt";

class ReaspGeoBaseLoad
{
	const MID = "reaspekt.geobase";

	public static function OnPrologHandler()
	{
		global $APPLICATION;
		if (IsModuleInstalled(self::MID)) {
			if (!defined("ADMIN_SECTION") && "ADMIN_SECTION" !== true) {
				Asset::getInstance()->addJs("/bitrix/js/main/core/core.min.js", true);
				return true;
			}
		}
	}
}