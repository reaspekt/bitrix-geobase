<?php
namespace Reaspekt\Geobase\Contract;
use \Reaspekt\Geobase\Type\Conditions;
use \Bitrix\Main\Type\Dictionary;

interface DatabaseInterface
{
     public static function getIpData(Conditions $conditions): Dictionary;

     public static function getCityData(Conditions $conditions): Dictionary;

     public static function addIpData(Dictionary $dictionary): void;

     public static function addCityData(Dictionary $dictionary): void;
}