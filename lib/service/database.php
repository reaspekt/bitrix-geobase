<?php
namespace Reaspekt\Geobase\Service;
use \Reaspekt\Geobase\Repository\LocalRepo;
use \Reaspekt\Geobase\Repository\MaxmindRepo;
use \Reaspekt\Geobase\Type\Conditions;
use \Bitrix\Main\Config\Option;

class DataBase
{
    const MID = "reaspekt.geobase";
    const DEV_NAME = "reaspekt";
    const OPTION_STATUS = "updateStatus";
    const FILE_IP = "GeoLite2-City-Blocks-IPv4.csv";
    const FILE_CITIES = "GeoLite2-City-Locations-ru.csv";
    const DEFAULT_STATE = [
        "FINISHED" => "N",
        "PROGRESS" => 0,
        "SEEK" => 0,
        "NEXT_STEP" => "DBUPDATE_IP"
    ];
    const LIMIT = 5000;

    private static function setCurrentState(array $newState): void
    {
        Option::set(self::MID, self::OPTION_STATUS, json_encode($newState));
    }

    private static function unsetCurrentState(): void
    {
        Option::delete(self::MID, ["name" => self::OPTION_STATUS]);
        MaxmindRepo::clearFileSystem();
    }

    public static function update(): array
    {
        $isFinished = false;
        $recordedState = json_decode(Option::get(self::MID, self::OPTION_STATUS), true);
        if ($recordedState && !empty($recordedState)) {
            $arState = $recordedState;
        } else {
            $arState = self::DEFAULT_STATE;
        }

        if ($arState["NEXT_STEP"] == "DBUPDATE_IP" && $arState["SEEK"] == 0) {
            LocalRepo::clear();
        }

        $conditions = new Conditions;
        $conditions->setLimit(self::LIMIT);
        $conditions->setOffset($arState["SEEK"] ? $arState["SEEK"] : 0);

        switch ($arState["NEXT_STEP"]) {
            case "DBUPDATE_IP":
                $dataIpDict = MaxmindRepo::getIpData($conditions);
                if ($dataIpDict->count() > 0) {
                        LocalRepo::addIpData($dataIpDict);
                }
                break;
            case "DBUPDATE_CITIES":
                $dataCityDict = MaxmindRepo::getCityData($conditions);
                if ($dataCityDict->count() > 0) {
                        LocalRepo::addCityData($dataCityDict);
                }
                break;
            default:
                return ["ERROR" => "Step doesn't exist"];
                break;
        }

        if (!array_key_exists("FILE_IP_SIZE", $arState)) {
            $arState["FILE_IP_SIZE"] = static::getIPSize();
        }
        if (!array_key_exists("FILE_CITIES_SIZE", $arState)) {
            $arState["FILE_CITIES_SIZE"] = static::getCitySize();
        }

        $currentPosition = (int) $arState["SEEK"] + (int) self::LIMIT;
        $resultProgress = static::getCurrentProgress(
            ($arState["NEXT_STEP"] == "DBUPDATE_IP" ? $currentPosition : ($currentPosition + $arState["FILE_IP_SIZE"])), 
            ($arState["FILE_IP_SIZE"] + $arState["FILE_CITIES_SIZE"])
        );

        if ($currentPosition >= (int) $arState["FILE_IP_SIZE"] && $arState["NEXT_STEP"] == "DBUPDATE_IP") {
            $arState["NEXT_STEP"] = "DBUPDATE_CITIES";
            $currentPosition = 0;
        }
        if ($currentPosition > $arState["FILE_CITIES_SIZE"] && $arState["NEXT_STEP"] == "DBUPDATE_CITIES") {
            $isFinished = true;
        }

        $resultState = [
            "FINISHED" => ($isFinished ? "Y" : "N"),
            "PROGRESS" => ($isFinished ? 100 : $resultProgress),
            "SEEK" => ($isFinished ? 0 : $currentPosition),
            "NEXT_STEP" => ($isFinished ? "" : $arState["NEXT_STEP"]),
            "FILE_IP_SIZE" => $arState["FILE_IP_SIZE"],
            "FILE_CITIES_SIZE" => $arState["FILE_CITIES_SIZE"],
        ];

        // Last step, update is over
        if ($isFinished) {
            static::unsetCurrentState();
        } else {
            static::setCurrentState($resultState);
        }
        return ["FINISHED" => $resultState["FINISHED"], "PROGRESS" => $resultState["PROGRESS"]];
    }

    public static function checkVersion(): string
    {
        $recordedState = json_decode(Option::get(self::MID, self::OPTION_STATUS), true);
        if ($recordedState && !empty($recordedState)) {
            return "N";
        }
        return MaxmindRepo::checkLatestVersion();
    }

    private static function getIpSize(): int
    {
        $documentRoot = \Bitrix\Main\Application::getDocumentRoot();
        $fPath = $documentRoot . '/upload/' . self::DEV_NAME . '/geobase/' . self::FILE_IP;

        $file = new \SplFileObject($fPath);
        $file->seek(PHP_INT_MAX);

        return $file->key() + 1;
    }

    private static function getCitySize(): int
    {
        $documentRoot = \Bitrix\Main\Application::getDocumentRoot();
        $fPath = $documentRoot . '/upload/' . self::DEV_NAME . '/geobase/' . self::FILE_CITIES;

        $file = new \SplFileObject($fPath);
        $file->seek(PHP_INT_MAX);

        return $file->key() + 1;
    }

    private static function getCurrentProgress($cur, $total = 0)
    {
        if (!$total) {
            $total  = 100;
            $cur    = 0;
        }
        $val = intval($cur / $total * 100);
        if ($val > 100) {
            $val = 100;
        }

        $progress = $val;
        return $progress;
    }
}