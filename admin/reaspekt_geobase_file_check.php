<? define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
use Bitrix\Main\Config\Option;
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php");

$module_id = "reaspekt.geobase";
$incMod = CModule::IncludeModuleEx($module_id);
$reaspekt_api_key = Option::get($module_id, "reaspekt_set_apikey");

if ($incMod == '0') {
	return false;
} elseif ($incMod == '3') {
	return false;
} else {
	$response["IPGEOBASE"] =
		(ReaspGeoIP::CheckServiceAccess('https://download.maxmind.com/app/geoip_download?edition_id=GeoLite2-City-CSV&license_key=' . $reaspekt_api_key . '&suffix=zip') ? 1 : 0);
	
	echo json_encode($response, JSON_FORCE_OBJECT);
}