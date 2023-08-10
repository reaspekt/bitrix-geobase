<?php
namespace Reaspekt\Geobase\Repository;

use \Bitrix\Main\Loader;
use \Bitrix\Highloadblock as HL;
use \Bitrix\Main\Error;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Main\Type\Dictionary;
use \Reaspekt\Geobase\Contract\DatabaseInterface;
use \Reaspekt\Geobase\Tools\Main as GeoMain;
use \Reaspekt\Geobase\Type\Conditions;

Loader::includeModule("reaspekt.geobase");
Loader::includeModule("highloadblock");
Loader::includeModule("main");

Loc::loadMessages(__FILE__);

/**
* Working with Local Database
*/
class LocalRepo implements DatabaseInterface
{
    const DEV_NAME = "reaspekt";
    const FILE_IP = "GeoLite2-City-Blocks-IPv4.csv";
    const FILE_CITIES = "GeoLite2-City-Locations-ru.csv";
    const TABLE_IP = "reaspekt_geobase_codeip";
    const TABLE_CITIES = "reaspekt_geobase_cities";
    const HL_NAME_IP = "ReaspektGeobaseCodeip";
    const HL_NAME_CITIES = "ReaspektGeobaseCities";

    public static function getIpData(Conditions $conditions): Dictionary
    {
        $connection = \Bitrix\Main\Application::getConnection();
        $resultDict = new Dictionary();

        $filter = $conditions->getFilter();
        if (!$filter["IP"]) {
            $error = new Error(Loc::getMessage('QUERY_ERROR'), '400');
            return $resultDict;
        }

        $queryResult = $connection->query('
            SELECT * FROM (
                SELECT * FROM `' . self::TABLE_IP . '`
                WHERE ' . $filter["IP"] . ' <= `' . self::TABLE_IP . '`.`UF_BLOCK_END`
                ORDER BY `' . self::TABLE_IP . '`.`UF_BLOCK_END` ASC
                LIMIT 1
            ) ' . self::TABLE_IP . '
            WHERE ' . $filter["IP"] . ' >= `' . self::TABLE_IP . '`.`UF_BLOCK_BEGIN`
        ');

        while ($arIP = $queryResult->fetch()) {
            $resultDict->set($arIP["ID"], $arIP);
        }

        return $resultDict;
    }

    public static function getCityData(Conditions $conditions): Dictionary
    {
        $limit = $conditions->getLimit();
        $filter = $conditions->getFilter();
        $hlID = static::getCityHighloadID();
        $entity = static::getHighloadEntity($hlID);
        $hlDataClass = $entity->getDataClass();
        $resultDict = new Dictionary();

        $rsData = $hlDataClass::getList([
            "select" => ["*"],
            "limit" => $limit,
            "filter" => $filter
        ]);

        while ($arData = $rsData->Fetch()) {
            $resultDict->set($arData["ID"], $arData);
        }

        return $resultDict;
    }

    public static function addIpData(Dictionary $dictionary): void
    {
        $highloadID = static::getIpHighloadID();
        if ($highloadID == null) {
            $highloadID = static::createHighloadIp();
        }

        $entity = static::getHighloadEntity($highloadID);
        $valuesCollection = $entity->createCollection();

        foreach ($dictionary as $dataRow) {
            $collectionObj =
                $entity->createObject()
                    ->set("UF_BLOCK_BEGIN", sprintf("%u", ip2long($dataRow["BLOCK_BEGIN"])))
                    ->set("UF_BLOCK_END", sprintf("%u", ip2long($dataRow["BLOCK_END"])))
                    ->set("UF_CITY_ID", $dataRow["CITY_ID"])
            ;
            $valuesCollection->add($collectionObj);
        }

        $valuesCollection->save(true);
    }

    public static function addCityData(Dictionary $dictionary): void
    {
        $highloadID = static::getCityHighloadID();
        if ($highloadID == null) {
            $highloadID = static::createHighloadCity();
        }

        $entity = static::getHighloadEntity($highloadID);
        $valuesCollection = $entity->createCollection();

        foreach ($dictionary as $dataRow) {
            $collectionObj =
                $entity->createObject()
                    ->set("UF_XML_ID", $dataRow["XML_ID"])
                    ->set("UF_NAME", $dataRow["NAME"])
                    ->set("UF_REGION_NAME", $dataRow["REGION_NAME"])
                    ->set("UF_COUNTRY_NAME", $dataRow["COUNTRY_NAME"])
            ;
            $valuesCollection->add($collectionObj);
        }

        $valuesCollection->save(true);
    }

    public static function clear(): void
    {
        $sqlConnection = \Bitrix\Main\Application::getConnection();
        $arTable = [self::TABLE_IP, self::TABLE_CITIES];
        foreach ($arTable as $nameSqlTable) {
            if ($sqlConnection->isTableExists($nameSqlTable)) {
                $sqlConnection->truncateTable($nameSqlTable);
            }
        }
    }

    public static function getCityByIp(string $ip): ?array
    {
        $cityFilter = [];
        $filter = [
            "IP" => sprintf("%u", ip2long($ip))
        ];
        $conditions = new Conditions;
        $conditions->setFilter($filter);
        $resIP = static::getIpData($conditions);
        if ($resIP->isEmpty()) {
            return null;
        }

        foreach ($resIP as $arIP) {
            $cityFilter = ["=UF_XML_ID" => $arIP["UF_CITY_ID"]];
        }

        $conditions = new Conditions;
        $conditions->setFilter($cityFilter);
        $resCity = static::getCityData($conditions);

        foreach ($resCity as $arCity) {
            $arData = $arCity;
        }
        return $arData;
    }

    public static function getCityById(int $сityId): ?array
    {
        if (!$сityId) {
            return ["ERROR" => "No city ID"];
        }
        //check on bots to eliminate extra quiries
        if (GeoMain::initBots()) {
            return ["ERROR" => "You're a bot"];
        }

        $arResult = [];
        $conditions = new Conditions;
        $conditions->setLimit(1);
        $conditions->setFilter(["=ID" => $сityId]);
        $arCity = static::getCityData($conditions);
        $arResult = GeoMain::standartFormat(current($arCity));

        return $arResult;
    }

    private static function getIpHighloadID(): ?int
    {
        $highloadInfo = HL\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::TABLE_IP]
        ])->fetch();
        if (!$highloadInfo) {
            return null;
        } else {
            return $highloadInfo["ID"];
        }
    }

    private static function getCityHighloadID(): ?int
    {
        $highloadInfo = HL\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => self::TABLE_CITIES]
        ])->fetch();
        if (!$highloadInfo) {
            return null;
        } else {
            return $highloadInfo["ID"];
        }
    }

    private static function getHighloadEntity(int $hlBlockId)
    {
        $hlblock = HL\HighloadBlockTable::getById($hlBlockId)->fetch(); 
        $entity = HL\HighloadBlockTable::compileEntity($hlblock);
        return $entity;
    }

    public static function isColumnExist($tableName, $columnName): bool
    {
        $highloadInfo = HL\HighloadBlockTable::getList([
            'filter' => ['=TABLE_NAME' => $tableName]
        ])->fetch();
        if ($highloadInfo) {
            $entityID = 'HLBLOCK_' . $highloadInfo["ID"];
        } else {
            return false;
        }

        if ($entityID) {
            $resProperty = \CUserTypeEntity::GetList(
                [],
                array('ENTITY_ID' => $entityID, 'FIELD_NAME' => $columnName)
            );

            if ($aUserHasField = $resProperty->Fetch()) {
                return true;
            }
        }

        return false;
    }

    public static function addCountryField(): int
    {
        $hlID = static::getCityHighloadID();

        $arUserTypeData = [
            'ENTITY_ID' => 'HLBLOCK_' . $hlID, /*highload block id*/
            'FIELD_NAME' => 'UF_COUNTY_NAME',
            'USER_TYPE_ID' => 'string',
            'MANDATORY' => 'N',
            'SHOW_FILTER' => 'S',
            'IS_SEARCHABLE' => 'N',
            'EDIT_FORM_LABEL'   => [
                'ru'    => 'UF_COUNTY_NAME',
                'en'    => 'UF_COUNTY_NAME',
            ],
            'LIST_COLUMN_LABEL' => [
                'ru'    => 'UF_COUNTY_NAME',
                'en'    => 'UF_COUNTY_NAME',
            ],
            'LIST_FILTER_LABEL' => [
                'ru'    => 'UF_COUNTY_NAME',
                'en'    => 'UF_COUNTY_NAME',
            ],
        ];

        $userTypeEntity = new \CUserTypeEntity();
        $userTypeId = $userTypeEntity->Add($arUserTypeData);

        return $userTypeId;
    }

    public static function removeCountryField(int $fieldID): void
    {
        $userTypeEntity = new \CUserTypeEntity();
        $userTypeEntity->Delete($fieldID); 
    }

    private static function createHighloadIp(): int
    {
        $highloadBlockData = [
            'NAME' => self::HL_NAME_IP,
            'TABLE_NAME' => self::TABLE_IP
        ];

        //Making HL
        $obResult = HL\HighloadBlockTable::add($highloadBlockData);

        //Successful creation
        if ($obResult->isSuccess()) {
            //Foriming array of fields to add to HL
            $arFieldsCodeIp = [
                "BLOCK_BEGIN" => "integer",
                "BLOCK_END" => "integer",
                "CITY_ID" => "integer",
            ];

            $userTypeEntity = new \CUserTypeEntity();

            foreach ($arFieldsCodeIp as $nameField => $typeField) {
                $userTypeData = [
                    'ENTITY_ID' => "HLBLOCK_" . $obResult->getId(), /*highload block id*/
                    'FIELD_NAME' => "UF_" . $nameField,
                    'USER_TYPE_ID' => $typeField,
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'S',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL'   => [
                        'ru'    => $nameField,
                        'en'    => $nameField,
                    ],
                    'LIST_COLUMN_LABEL' => [
                        'ru'    => $nameField,
                        'en'    => $nameField,
                    ],
                    'LIST_FILTER_LABEL' => [
                        'ru'    => $nameField,
                        'en'    => $nameField,
                    ],
                ];

                if ($typeField == "boolean") {
                    $userTypeData["SETTINGS"]["DEFAULT_VALUE"] = 1;
                    $userTypeData["SETTINGS"]["DISPLAY"] = "CHECKBOX";
                }

                //Adding fields to HL
                $userTypeEntity->Add($userTypeData);
            }

            static::createIPRangeIndex();
            return $obResult->getId();
        } else {
            $errors = $obResult->getErrorMessages();
            return 0;
        }
    }

    private static function createHighloadCity(): int
    {
        $highloadBlockData = [
            'NAME' => self::HL_NAME_CITIES,
            'TABLE_NAME' => self::TABLE_CITIES
        ];

        //Making HL
        $obResult = HL\HighloadBlockTable::add($highloadBlockData);

        //Successful creation
        if ($obResult->isSuccess()) {
            //Forming tha array of fields for adding to HL
            $arFieldsCodeIp = [
                "XML_ID" => "integer",
                "NAME" => "string",
                "REGION_NAME" => "string",
                "COUNTRY_NAME" => "string",
            ];

            $userTypeEntity = new \CUserTypeEntity();

            foreach ($arFieldsCodeIp as $nameField => $typeField) {
                $userTypeData = [
                    'ENTITY_ID' => "HLBLOCK_" . $obResult->getId(), /*highload block id*/
                    'FIELD_NAME' => "UF_" . $nameField,
                    'USER_TYPE_ID' => $typeField,
                    'MANDATORY' => 'N',
                    'SHOW_FILTER' => 'S',
                    'IS_SEARCHABLE' => 'N',
                    'EDIT_FORM_LABEL'   => [
                        'ru'    => $nameField,
                        'en'    => $nameField,
                    ],
                    'LIST_COLUMN_LABEL' => [
                        'ru'    => $nameField,
                        'en'    => $nameField,
                    ],
                    'LIST_FILTER_LABEL' => [
                        'ru'    => $nameField,
                        'en'    => $nameField,
                    ],
                ];

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

                //Adding fields to HL
                $userTypeEntity->Add($userTypeData);
            }

            static::createCityXmlIndex();
            return $obResult->getId();
        }
    }

    public static function statusTableDB(): bool
    {
        $sqlConnection = \Bitrix\Main\Application::getConnection();
        $doTablesExist = false;

        if ($sqlConnection->isTableExists(self::TABLE_IP) && $sqlConnection->isTableExists(self::TABLE_CITIES)) {
            $doTablesExist = true;
        }

        return $doTablesExist;
    }

    private static function createIPRangeIndex(): void
    {
        $sqlConnection = \Bitrix\Main\Application::getConnection();
        if (!$sqlConnection->isIndexExists(self::TABLE_IP, ["UF_BLOCK_END"])) {
            $sqlConnection->createIndex(self::TABLE_IP, self::TABLE_IP . "_IP_BLOCK_END", "UF_BLOCK_END");
        }
    }

    private static function createCityXmlIndex(): void
    {
        $sqlConnection = \Bitrix\Main\Application::getConnection();
        if (!$sqlConnection->isIndexExists(self::TABLE_CITIES, ["UF_XML_ID"])) {
            $sqlConnection->createIndex(self::TABLE_CITIES, self::TABLE_CITIES . "_XML_ID", "UF_XML_ID");
        }
    }
}