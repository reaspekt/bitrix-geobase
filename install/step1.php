<?
/**
 * Company developer: REASPEKT
 * Developer: reaspekt
 * Site: https://www.reaspekt.ru
 * E-mail: info@reaspekt.ru
 * @copyright (c) 2022 REASPEKT
 */
 
use \Bitrix\Main\Localization\Loc;

if (!check_bitrix_sessid())
	return;

if ($ex = $APPLICATION->GetException()){
	echo CAdminMessage::ShowMessage(array(
		"TYPE" => "ERROR",
		"MESSAGE" => Loc::getMessage("MOD_INST_ERR"),
		"DETAILS" => $ex->GetString(),
		"HTML" => true,
	));
}

if (!\Bitrix\Main\Loader::includeModule("highloadblock")) {
	return;
}
?>

<form action="<?=$APPLICATION->GetCurPage()?>">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="lang" value="<?=LANGUAGE_ID?>"/>
	<input type="hidden" name="id" value="reaspekt.geobase"/>
	<input type="hidden" name="install" value="Y"/>
	<input type="hidden" name="step" value="2"/>
	<input type="checkbox" name="ONLY_CIS" id="ONLY_CIS" value="Y" checked/>
	<label for="ONLY_CIS"><?=Loc::getMessage('INSTALL_GEOBASE_ONLY_CIS')?></label>
	<br/><br/>
	
	<input type="submit" name="" value="<?= Loc::getMessage("MOD_INSTALL")?>"/>
</form>