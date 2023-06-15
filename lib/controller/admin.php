<?php
namespace Reaspekt\Geobase\Controller;
use \Reaspekt\Geobase\CoreUpdater;
use \Reaspekt\Geobase\DefaultCities;
use \Reaspekt\Geobase\Service\DataBase;
use \Bitrix\Main\Error;
use \Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

class Admin extends \Bitrix\Main\Engine\Controller
{
     public function updateAction(): array
     {
          $dbService = new DataBase();
          $result = $dbService::update();
          if ($result["ERROR"]) {
               $this->addError(new Error(Loc::getMessage('ERROR_BASE_NOT_DOWNLOADED') . ' ' . $result["ERROR"], '1001'));
          }
          return $result;
     }

     public function checkLatestVersionAction(): array
     {
          $isUpdateNeeded = DataBase::checkVersion();
          return ["LAST_VERSION" => $isUpdateNeeded];
     }

     public function updateSelectedCitiesAction(array $obData): ?array
     {
          $result = DefaultCities::getCitySelected($obData);
          if ($result["ERROR"]) {
               $this->addError(new Error($result["ERROR"], '1001'));
               return null;
          }

          return $result;
     }
     
     public function updateCoreAction(): ?array
     {
          $result = CoreUpdater::updateCore();

          if ($result["ERROR"]) {
               switch ($result["ERROR"]) {
                    case "BAD_VERSION":
                         $this->addError(new Error(Loc::getMessage('ERROR_BAD_VERSION'), '1002'));
                         break;
               }

               return null;
          }

          return $result;
     }
}