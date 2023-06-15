<?php
namespace Reaspekt\Geobase\Controller;
use \Reaspekt\Geobase\DefaultCities;
use \Bitrix\Main\Error;
use \Bitrix\Main\Engine\ActionFilter;

class Api extends \Bitrix\Main\Engine\Controller
{
     public function configureActions(): array
     {
          return [
               'setCity' => [
                    'prefilters' => [
                         new ActionFilter\HttpMethod(
                              [ActionFilter\HttpMethod::METHOD_POST]
                         ),
                         new ActionFilter\Csrf(),
                    ],
               ],
               'saveCity' => [
                    'prefilters' => [
                         new ActionFilter\HttpMethod(
                              [ActionFilter\HttpMethod::METHOD_POST]
                         ),
                         new ActionFilter\Csrf(),
                    ],
               ],
               'showSearchedCity' => [
                    'prefilters' => [
                         new ActionFilter\HttpMethod(
                              [ActionFilter\HttpMethod::METHOD_POST]
                         ),
                         new ActionFilter\Csrf(),
                    ],
               ],
          ];
     }

     public function setCityAction($cityID): ?array
     {
          $result = DefaultCities::setCityManual($cityID);
          return $result;
     }

     public function saveCityAction()
     {
          $result = DefaultCities::setCityYes();
          return $result;
     }

     public function showSearchedCityAction($cityName)
     {
          $result = DefaultCities::showSearchedCity($cityName);
          return $result;
     }
}