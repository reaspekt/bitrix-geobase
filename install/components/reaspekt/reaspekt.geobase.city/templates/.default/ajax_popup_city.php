<?
define('NO_KEEP_STATISTIC', true);
define('NO_AGENT_STATISTIC', true);
define('NO_AGENT_CHECK', true);
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
use \Reaspekt\Geobase\DefaultCities;

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;

Loc::loadMessages(__FILE__);

$module_id = "reaspekt.geobase";

if (!CModule::IncludeModule($module_id)) {
    ShowError("Error! Module no install");
	return;
}

$arData = DefaultCities::getAddr();

$arResult["DEFAULT_CITY"] = DefaultCities::defaultCityList();
$showCitiesList = is_array($arResult["DEFAULT_CITY"]) ? true : false;
?>
<div class="reaspektGeobaseWrapperPopup">
	<div id="reaspektGeobaseFind">
		<input type="text" onkeyup="JCReaspektGeobase.inpKeyReaspektGeobase(event);" autocomplete="off" placeholder="<?=Loc::getMessage("REASPEKT_INPUT_ENTER_CITY");?>" name="reaspekt_geobase_search" id="reaspektGeobaseSearch">
	</div>
	
	<? if ($showCitiesList) { ?>
		<div class="reaspektGeobaseTitle"><?=Loc::getMessage("REASPEKT_TITLE_ENTER_CITY");?>:</div>				
		<div class="reaspektGeobaseCities reaspekt_clearfix">
			<div class="reaspekt_row">
				<? foreach ($arResult["DEFAULT_CITY"] as $arCity) {?>				
					<div class="reaspektGeobaseAct">
						<?if($arData["UF_XML_ID"] == $arCity["UF_XML_ID"]):?>
						<strong><?=$arCity["CITY"]?></strong>
						<?else:?>
						<a onclick="JCReaspektGeobase.onClickReaspektGeobase('<?=$arCity["UF_XML_ID"]?>'); return false;" id="reaspekt_geobase_list_<?=$cell?>" title="<?=$arCity["CITY"]?>" href="javascript:void(0);"><?=$arCity["CITY"]?></a>
						<?endif;?>
					</div>
				<? }
				?>
			</div>
		</div>
	<? } ?>
	<div class="preloaderReaspekt"><div></div><div></div><div></div><div></div></div>
</div>