<?
/**
 * Company developer: REASPEKT
 * Developer: adel yusupov
 * Site: http://www.reaspekt.ru
 * E-mail: adel@rreaspekt_geobase_citieseaspekt.ru
 * @copyright (c) 2016 REASPEKT
 */
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Config as Conf;
use \Bitrix\Main\Config\Configuration;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\Loader;
use \Bitrix\Main\Entity\Base;
use \Bitrix\Main\Application;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\ModuleManager;
use \Reaspekt\Geobase\CoreUpdater;

use \Bitrix\Highloadblock as HL;

Loc::loadMessages(__FILE__);

Class reaspekt_geobase extends CModule {

	var $nameCompany = "reaspekt";
	var $pathResourcesCompany = "local";
    	var $pathCompany = "bitrix";

    	var $exclusionAdminFiles;

	var $MODULE_ID = "reaspekt.geobase";
	var $MODULE_VERSION;
	var $MODULE_VERSION_DATE;
	var $MODULE_NAME;
	var $MODULE_DESCRIPTION;

	function __construct()
	{
		$arModuleVersion = array();

		include(__DIR__ . "/version.php");

		//Exceptions
		$this->exclusionAdminFiles=array(
			'..',
			'.',
		);

        	$this->nameCompany = Configuration::getInstance('reaspekt.geobase')->get('information')['developer'];
		$this->MODULE_VERSION = $arModuleVersion["VERSION"];
		$this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
		$this->MODULE_NAME = Loc::getMessage("REASPEKT_GEOBASE_MODULE_NAME");
		$this->MODULE_DESCRIPTION = Loc::getMessage("REASPEKT_GEOBASE_MODULE_DESC");

		$this->PARTNER_NAME = Loc::getMessage("REASPEKT_GEOBASE_PARTNER_NAME");
		$this->PARTNER_URI = Loc::getMessage("REASPEKT_GEOBASE_PARTNER_URI");

		$this->MODULE_SORT = 1;
		$this->SHOW_SUPER_ADMIN_GROUP_RIGHTS='N';
		$this->MODULE_GROUP_RIGHTS = "N";

		if (!\Bitrix\Main\Loader::includeModule("highloadblock")) {
			$APPLICATION->ThrowException("Please install module <a href='/bitrix/admin/module_admin.php?lang=ru'>highloadblock</a>");
			return false;
		}
	}

	//Define the place where to put module
	public function GetPath($notDocumentRoot=false)
	{
		if ($notDocumentRoot)
			return str_ireplace(Application::getDocumentRoot(), '', dirname(__DIR__));
		else
			return dirname(__DIR__);
	}

	//Checking that the system supports D7
	public function isVersionD7()
	{
		return CheckVersion(\Bitrix\Main\ModuleManager::getVersion('main'), '14.00.00');
	}

	function DoInstall()
	{
		global $DB, $APPLICATION;

        	if ($this->isVersionD7()) {
			$context = Application::getInstance()->getContext();
			$request = $context->getRequest();

			$step = IntVal($request['step']);

			if ($step < 2) {
				$GLOBALS["install_step"] = 1;
				
				$APPLICATION->IncludeAdminFile(
					Loc::getMessage("REASPEKT_GEOBASE_INSTALL_TITLE"),
					$this->GetPath() . "/install/step1.php"
				);
			} elseif ($step == 2) { // end
				ModuleManager::registerModule($this->MODULE_ID);
				Loader::includeModule($this->MODULE_ID);

				$GLOBALS["install_step"]	= 2;
				if ($request["ONLY_CIS"] == "Y") {
					Option::set($this->MODULE_ID, "only_cis", "Y");
				}

				$this->InstallFiles();
				CoreUpdater::updateCore();

				if ($this->InstallDB()) {
					$APPLICATION->IncludeAdminFile(
						Loc::getMessage("REASPEKT_GEOBASE_INSTALL_TITLE"),
						$this->GetPath() . "/install/step2.php"
					);
				}				
			}
		} else {
            	$APPLICATION->ThrowException(Loc::getMessage("REASPEKT_GEOBASE_INSTALL_ERROR_VERSION"));
			$APPLICATION->IncludeAdminFile(
				Loc::getMessage("REASPEKT_GEOBASE_INSTALL_TITLE"),
				$this->GetPath() . "/install/step.php"
			);
        	}
	}

	function DoUninstall()
	{
		global $DB, $APPLICATION;

		$context = Application::getInstance()->getContext();
        	$request = $context->getRequest();

		if ($request["step"] < 2) {
			$APPLICATION->IncludeAdminFile(
				Loc::getMessage("REASPEKT_GEOBASE_UNINSTALL_TITLE"), 
				$this->GetPath() . "/install/unstep1.php"
			);
		} elseif ($request["step"] == 2) {
			$this->UnInstallFiles();

			if ($request["savedata"] != "Y") {
				$this->UnInstallDB(array(
					"savedata" => $request["savedata"],
				));
			}

			EventManager::getInstance()->unRegisterEventHandler(
				"main",
				"OnProlog",
				$this->MODULE_ID,
				"ReaspGeoBaseLoad",
				"OnPrologHandler"
			); 

			ModuleManager::unRegisterModule($this->MODULE_ID);

			$APPLICATION->IncludeAdminFile(
				Loc::getMessage("REASPEKT_GEOBASE_UNINSTALL_TITLE"), 
				$this->GetPath() . "/install/unstep2.php"
			);
		}
	}

	function InstallDB()
	{
		$context = Application::getInstance()->getContext();
		$request = $context->getRequest();

		global $DB, $DBType, $APPLICATION;
		$this->errors = false;

		if ($this->errors !== false) {
			$APPLICATION->ThrowException(implode("", $this->errors));
			return false;
		}

		EventManager::getInstance()->registerEventHandler(
			"main",
			"OnProlog",
			$this->MODULE_ID,
			"ReaspGeoBaseLoad",
			"OnPrologHandler"
		);

		return true;
	}

	function UnInstallDB($arParams = array())
	{
		global $DB, $DBType, $APPLICATION;

		if (!$arParams['savedata']){
			$requestHL = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('order' => array('NAME'), 'filter' => array("TABLE_NAME" => array("reaspekt_geobase_cities","reaspekt_geobase_codeip"))));

			while ($rowHL = $requestHL->fetch()){
				if ($DB->TableExists($rowHL["TABLE_NAME"])) {
					HL\HighloadBlockTable::delete($rowHL["ID"]);
				}
			}
		}

		Option::delete($this->MODULE_ID);
	}

	function InstallFiles()
	{
		//Making folder 'geobase' in /upload/ to download there geoip bases
		if (!\Bitrix\Main\IO\Directory::isDirectoryExists(Application::getDocumentRoot() . "/upload/" . $this->nameCompany . "/geobase/")) {
			if(!defined("BX_DIR_PERMISSIONS"))
				mkdir(Application::getDocumentRoot() . "/upload/" . $this->nameCompany . "/geobase/", 0755, true);
			else
				mkdir(Application::getDocumentRoot() . "/upload/" . $this->nameCompany . "/geobase/", BX_DIR_PERMISSIONS, true);
		}

		//Path to folder /install/components in module
		$pathComponents = $this->GetPath() . "/install/components";

		//Check if the folder exists
		if (\Bitrix\Main\IO\Directory::isDirectoryExists($pathComponents))
			CopyDirFiles($pathComponents, Application::getDocumentRoot() . "/" . $this->pathResourcesCompany . "/components", true, true);
        	else
            	throw new \Bitrix\Main\IO\InvalidPathException($pathComponents);

		return true;
	}

	function UnInstallFiles()
	{
		$arDirecoriesToDelete = [
			Application::getDocumentRoot() . '/bitrix/components/' . 'reaspekt' . '/reaspekt.geoip/',
			Application::getDocumentRoot() . '/bitrix/components/' . 'reaspekt' . '/reaspekt.geobase.city/',
			Application::getDocumentRoot() . '/local/components/' . 'reaspekt' . '/reaspekt.geoip/',
			Application::getDocumentRoot() . '/local/components/' . 'reaspekt' . '/reaspekt.geobase.city/',
			Application::getDocumentRoot() . '/upload/' . 'reaspekt' . '/geobase/'
		];

		foreach ($arDirecoriesToDelete as $directoryPath) {
			if (\Bitrix\Main\IO\Directory::isDirectoryExists($directoryPath)) {
				\Bitrix\Main\IO\Directory::deleteDirectory($directoryPath);
			}
		}

		return true;
	}
}