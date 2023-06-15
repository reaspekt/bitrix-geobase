<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
CJSCore::Init(["popup"]);

if ($arResult["GEO_CITY"]) {
    ?>
    <div class="wrapGeoIpReaspekt">
        <?=Loc::getMessage("REASPEKT_GEOIP_TITLE_YOU_CITY");?>: <span class="cityLinkPopupReaspekt linkReaspekt"><?=$arResult["GEO_CITY"]["CITY"]?><?=(!empty($arResult["GEO_CITY"]["REGION"]) ? ($arResult["GEO_CITY"]["REGION"] != $arResult["GEO_CITY"]["CITY"] ? (", " . $arResult["GEO_CITY"]["REGION"]) : "") : "")?></span>
        <?if (
            $arParams["CHANGE_CITY_MANUAL"] == "Y"
            && $arResult["POPUP_HIDE"] == "N"
        ) {?>
        <div id="wrapQuestionReaspekt">
            <div class="questionYourCityReaspekt"><?=Loc::getMessage("REASPEKT_GEOIP_TITLE_YOU_CITY");?>:</div>
            <div class="questionCityReaspekt"><strong><?=$arResult["GEO_CITY"]["CITY"]?></strong></div>
            <div class="questionButtonReaspekt reaspekt_clearfix">
                <div class="questionNoReaspekt cityLinkPopupReaspekt"><?=Loc::getMessage("REASPEKT_GEOIP_BUTTON_N");?></div>
                <div class="questionYesReaspekt" onClick="JCReaspektGeobase.onClickReaspektSaveCity('N');"><?=Loc::getMessage("REASPEKT_GEOIP_BUTTON_Y");?></div>
            </div>
        </div>
        <?}?>
    </div>

    <script type="text/javascript">
        BX.ready(function() {
            if (typeof JCReaspektGeobase !== "undefined") {
                let popupUrl = "<?=$templateFolder?>/ajax_popup_city.php";
                let oPopup = new BX.PopupWindow('ReaspektPopupBody', window.body, {
                    autoHide : true,
                    offsetTop : 1,
                    offsetLeft : 0,
                    lightShadow : true,
                    closeIcon : true,
                    closeByEsc : true,
                    overlay: {
                        backgroundColor: 'black', opacity: '75'
                    }
                });

                popupContent = BX.ajax({
                    url: popupUrl,
                    onsuccess: function(data){
                        oPopup.setContent(data);
                    }
                });

                BX.bindDelegate(
                    document.body, 'click', {className: 'cityLinkPopupReaspekt'},
                    BX.proxy(function(e) {
                        if(!e)
                            e = window.event;
                        oPopup.show();
                        return BX.PreventDefault(e);
                    }, oPopup)
                );

                JCReaspektGeobase.init();
            }
        })
    </script>
    <?
}
?>