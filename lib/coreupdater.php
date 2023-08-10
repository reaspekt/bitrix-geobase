<?
namespace Reaspekt\Geobase;
use \Bitrix\Main\Application;
use \Bitrix\Main\EventManager;
use \Bitrix\Main\IO\Directory;
use \Bitrix\Main\IO\File;
use \Bitrix\Main\Config\Option;
use \Reaspekt\Geobase\Repository\LocalRepo;

class CoreUpdater
{
    public static function updateCore()
    {
        $server = Application::getInstance()->getContext()->getServer();
        $companyName = "reaspekt";
        $moduleID = "reaspekt.geobase";
        $corePath = __DIR__ . "/../";
        $citiesTableName = "reaspekt_geobase_cities";
        $arRemovePaths = [
            $server->getDocumentRoot() . "/bitrix/components/" . $companyName . "/reaspekt.geoip/",
            $server->getDocumentRoot() . "/local/components/" . $companyName . "/reaspekt.geoip/",
            $corePath . "admin/",
            $corePath . "classes/",
            $corePath . "install/new.core/",
        ];
        $arRemoveFiles = [$corePath . "include.php"];
        $arReplacePaths = ["options.php", "default_option.php"];

        if (
            CheckVersion(phpversion(), '7.4.0')
            && CheckVersion(SM_VERSION, '21.1200.800')
        ) {
            // We don't use event handlers in new core
            static::unregisterEvents($moduleID);

            // Replace files with new core
            foreach ($arReplacePaths as $fileName) {
                static::replaceFiles(
                    $corePath . $fileName,
                    $corePath . "install/new.core/" . $fileName
                );
            }
            // Remove old components, classes and endpoints
            foreach ($arRemoveFiles as $removeFile) {
                if (File::isFileExists($removeFile)) {
                    (new File($removeFile))->delete();
                }
            }
            foreach ($arRemovePaths as $removePath) {
                if (Directory::isDirectoryExists($removePath)) {
                    Directory::deleteDirectory($removePath);
                }
            }

            $isOldColumnExist = LocalRepo::isColumnExist($citiesTableName, "UF_COUNTY_NAME");
            if ($isOldColumnExist) {
                $sqlConnection = \Bitrix\Main\Application::getConnection();

                $sqlConnection->query("UPDATE `b_user_field` SET `FIELD_NAME`='UF_COUNTRY_NAME' WHERE `FIELD_NAME`='UF_COUNTY_NAME'");
                $sqlConnection->query('ALTER TABLE `reaspekt_geobase_cities` CHANGE COLUMN `UF_COUNTY_NAME` `UF_COUNTRY_NAME` varchar(255)');
                $field = LocalRepo::addCountryField();
                LocalRepo::removeCountryField($field);
            }
            Option::set($moduleID, "reaspekt_transferred_core", "Y");
        } else {
            return ["ERROR" => "BAD_VERSION"];
        }
        return ["CORE_PATH" => $corePath];
    }

    private static function replaceFiles(string $fileOldPath, string $fileNewPath): void
    {
        if (File::isFileExists($fileNewPath)) {
            $newCoreFile = new File($fileNewPath);
            $oldCoreFile = new File($fileOldPath);
            $contentNewCore = $newCoreFile->getContents();
            $oldCoreFile->putContents($contentNewCore);
            $newCoreFile->delete();
        }
    }

    private static function unregisterEvents(string $moduleID): void
    {
        $arHandlers = EventManager::getInstance()->findEventHandlers("main", "OnProlog", ["TO_MODULE_ID" => $moduleID]);
        if (!empty($arHandlers)) {
            EventManager::getInstance()->unRegisterEventHandler(
                "main",
                "OnProlog",
                $moduleID,
                "ReaspGeoBaseLoad",
                "OnPrologHandler"
            );
        }
    }
}