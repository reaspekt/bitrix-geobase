<?
/**
 * Company developer: REASPEKT
 * Developer: reaspekt
 * Site: https://www.reaspekt.ru
 * E-mail: info@reaspekt.ru
 * @copyright (c) 2016 REASPEKT
 */

use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
use \Reaspekt\Geobase\Repository\LocalRepo as Local;
use \Reaspekt\Geobase\DefaultCities;

CUtil::InitJSCore(["jquery", "window"]);
$documentRoot = \Bitrix\Main\Application::getDocumentRoot();

$module_id = "reaspekt.geobase";
$reaspekt_city_manual_default = Option::get($module_id, "reaspekt_city_manual_default");

$server = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getServer();
$currentFolder = "https://" . $server->getHttpHost() . str_replace($documentRoot, "", __DIR__);

Loc::loadMessages($documentRoot . BX_ROOT . "/modules/main/options.php");
Loc::loadMessages(__FILE__);

if ($APPLICATION->GetGroupRight($module_id) < "S") {
    $APPLICATION->AuthForm(Loc::getMessage("ACCESS_DENIED"));
}

$request = \Bitrix\Main\HttpApplication::getInstance()->getContext()->getRequest();
$arCityOption = [];

function ShowParamsHTMLByArray($arParams)
{
    foreach ($arParams as $Option) {
        __AdmSettingsDrawRow("reaspekt.geobase", $Option);
    }
}

$aTabs = [
    [
        'DIV' => 'edit1',
        'TAB' => Loc::getMessage('REASPEKT_GEOBASE_TAB_SETTINGS')
    ],
    [
        "DIV" => "edit2",
        "TAB" => Loc::getMessage("REASPEKT_GEOBASE_TAB_CITY_NAME")
    ],
    [
        "DIV" => "edit3",
        "TAB" => Loc::getMessage("REASPEKT_GEOBASE_TAB_CREATE_BD"),
        "TITLE" => (Local::statusTableDB() ? Loc::getMessage("REASPEKT_TAB_UPDATE_TITLE_DATA_UPDATE") : Loc::getMessage("REASPEKT_TAB_UPDATE_TITLE_DATA_CREATE"))
    ],
    [
        "DIV" => "edit4",
        "TAB" => Loc::getMessage("MAIN_TAB_RIGHTS"),
        "TITLE" => Loc::getMessage("MAIN_TAB_TITLE_RIGHTS")
    ],
];

$arAllOptions = [
    "edit1" => [
        ["reaspekt_set_apikey", Loc::getMessage("REASPEKT_GEOBASE_SET_API"), "", ["text"]],
        ["only_cis", Loc::getMessage("REASPEKT_GEOBASE_ONLY_CIS"), "", ["checkbox"]]
    ],
    "edit2" => $arCityOption,
    "edit3" => [],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);

if (
    $request->isPost() 
    && strlen($Update.$Apply.$RestoreDefaults) > 0 
    && check_bitrix_sessid()
) {
    if (strlen($RestoreDefaults) > 0) {
        Option::delete("reaspekt.geobase");
    } else {
        foreach ($aTabs as $aTab) {
            foreach ($arAllOptions[$aTab["DIV"]] as $arOption) {
                if (!is_array($arOption)) {
                    continue;
                }

                if ($arOption['note']) {
                    continue;
                }

                $optionName = $arOption[0];
                $optionValue = $request->getPost($optionName);
                Option::set($module_id, $optionName, is_array($optionValue) ? implode(",", $optionValue) : $optionValue);
            }
        }
    }

    if (strlen($Update) > 0 && strlen($_REQUEST["back_url_settings"]) > 0) {
        LocalRedirect($_REQUEST["back_url_settings"]);
    } else {
        LocalRedirect($APPLICATION->GetCurPage() . "?mid=" . urlencode($mid) . "&lang=" . urlencode(LANGUAGE_ID) . "&back_url_settings=" . urlencode($_REQUEST["back_url_settings"]) . "&" . $tabControl->ActiveTabParam());
    }
}
?>
<style type="text/css">
.reaspekt_option-main-box {
    display: none;
    padding-bottom: 10px;
    margin-bottom: 5px;
    position: relative;
    width: 100%;
}
.reaspekt_option-main-box span {
    display: inline-block;
}
.reaspekt_option-main-box > div {
    margin: 10px 0 5px 0;
    text-align: right;
}
.reaspekt_option-progress-bar {
    width: 100%;
    height: 15px
}
.reaspekt_option-progress-bar span {
    position: absolute;
}
.reaspekt_option-progress-bar > span {
    border: 1px solid silver;
    width: 95%;
    left: 2px;
    height: 15px;
    text-align: left;
}
.reaspekt_option-progress-bar > span + span {
    border: none;
    width: 4%;
    height: 15px;
    left: auto;
    right: 2px;
    text-align: right
}
#progress {
    height: 15px;
    background: #637f9c;
}
#progress_MM {
    height: 15px;
    background: #637f9c;
}
.reaspekt_geobase_light {
    color: #3377EE;
}
#reaspekt_geobase_info {
    display: none;
    margin-bottom: 15px;
    margin-top: 1px;
    width: 75%;
}
#reaspekt_geobase_info option {
    padding: 3px 6px;
}
#reaspekt_geobase_info option:hover {
    background-color: #D6D6D6;
}
td #reaspekt_geobase_btn {
    margin: 10px 0px 80px;
}
#reaspekt_description_full {
    display: none;
    transition: height 250ms;
}
#reaspekt_description_close_btn {
    display: none;
}
.reaspekt_description_open_text {
    border-bottom: 1px solid;
    color: #2276cc !important;
    cursor: pointer;
    transition: color 0.3s linear 0s;
}
.reaspekt_gb_uf_edit {
    background-color: #d7e3e7;
    background: -moz-linear-gradient(center bottom , #d7e3e7, #fff);
    background-image: url("/bitrix/images/reaspekt.geobase/correct.gif");
    background-position: right 20px center;
    background-repeat: no-repeat;
    color: #3f4b54;
    display: inline-block;
    font-size: 13px;
    margin: 2px;
    outline: medium none;
    vertical-align: middle;
    border: medium none;
    border-radius: 4px;
    box-shadow: 0 0 1px rgba(0, 0, 0, 0.3), 0 1px 1px rgba(0, 0, 0, 0.3), 0 1px 0 #fff inset, 0 0 1px rgba(255, 255, 255, 0.5) inset;
    cursor: pointer;
    font-family: "Helvetica Neue",Helvetica,Arial,sans-serif;
    font-weight: bold;
    position: relative;
    text-decoration: none;
    text-shadow: 0 1px rgba(255, 255, 255, 0.7);
    padding: 1px 13px 3px;
}
.reaspekt_gb_uf_edit:hover {
    background: #f3f6f7 -moz-linear-gradient(center top , #f8f8f9, #f2f6f8) repeat scroll 0 0;
    background-image: url("/bitrix/images/reaspekt.geobase/correct.gif");
    background-position: right 20px center;
    background-repeat: no-repeat;
}
#reaspekt_geobase_table_header td {
    text-align: left !important;
}
.hidden {
    display: none !important
}
</style>

<script language="JavaScript">
$(document).ready(function() {
    BX.ajax.runAction('reaspekt:geobase.admin.checkLatestVersion', {
        data: {}
    }).then(function(response) {
        if (response.data.LAST_VERSION == "Y") {
            document.getElementById('reaspektDBUpdateBtn').style.display = 'none';
            document.getElementsByClassName('reasp-gbase-main-box')[0].innerHTML = "<?=Loc::getMessage("REASPEKT_GEOBASE_LAST_VERSION")?>";
        }
    }, function(response) {
        console.log(response);
    });
})
</script>

<script language="JavaScript">
    var reaspekt_geobase = new Object();
    reaspekt_geobase = {'letters':'', 'timer':'0'};

    function reaspekt_geobase_delete_click(cityid) {
        var id = '';
        if (typeof cityid !== 'undefined')
            id = cityid;
        else
            return false;

            let obData = {
            'sessid': BX.message('bitrix_sessid'),
            'cityId': id,
            'action': 'delete'
        };
        BX.ajax.runAction('reaspekt:geobase.admin.updateSelectedCities', {
            data: {obData},
            timeout: 10000,
        }).then(function(response) {
            console.log(response);
            reaspekt_geobase_update_table();
        }, function(response) {
            console.log(response);
        });
    }

    function reaspekt_geobase_update_table() {
        let obData = {
            'sessid': BX.message('bitrix_sessid'),
            'action': 'update'
        };
        BX.ajax.runAction('reaspekt:geobase.admin.updateSelectedCities', {
            data: {obData},
            timeout: 10000,
        }).then(function(response) {
            $('#reaspekt_geobase_cities_table .reaspekt_geobase_city_line').empty().remove();
            $('#reaspekt_geobase_cities_table').append(response.data.HTML);
        }, function(response) {
            console.log(response);
        });
    }

    function reaspekt_geobase_onclick(cityid) { // click button "Add"
        var id = '';
        if (typeof cityid == 'undefined' && $('#reaspekt_geobase_btn').prop('disabled') == true && cityid != 'Enter') {
            return false;
        }

        if (typeof cityid !== 'undefined' && cityid != 'Enter') {
            id = cityid;
        } else if (typeof reaspekt_geobase.selected_id !== 'undefined') {
            id = reaspekt_geobase.selected_id;
        }

        let obData = {
            'sessid': BX.message('bitrix_sessid'),
            'cityId': id,
            'action': 'add'
        };
        BX.ajax.runAction('reaspekt:geobase.admin.updateSelectedCities', {
            data: {obData},
            timeout: 10000,
        }).then(function(response) {
                var list = $('select#reaspekt_geobase_info');
                list.html('');
                if (response == '' || response == null) {
                    list.animate({ height: 'hide' }, "fast");
                } else {
                    if (response.data >= 0) {
                        $('#reaspekt_geobase_btn').prop('disabled',true);
                        $('input#reaspekt_geobase_search').val('');
                    }
                }
                reaspekt_geobase_update_table();
        }, function(response) {
            console.log(response);
        });
        return false;
    }

    function reaspekt_geobase_select_change(event) {
        t = event.target || event.srcElement;
        var sel = t.options[t.selectedIndex];
        $('input#reaspekt_geobase_search').val(reaspekt_geobase.letters = BX.util.trim(sel.value));
        var id = sel.id.substr(20);
        reaspekt_geobase.selected_id = id;
    }

    function reaspekt_geobase_select_sizing() {
        var count = $("select#reaspekt_geobase_info option").size();
        if (count < 2)
            $("select#reaspekt_geobase_info").attr('size', count+1);
        else if (count < 20)
            $("select#reaspekt_geobase_info").attr('size', count);
        else
            $("select#reaspekt_geobase_info").attr('size', 20);
    }

    $(function() {
        $(document).click(function(event) {
            var search = $('input#reaspekt_geobase_search');
            if ($(event.target).closest("#reaspekt_geobase_info").length) return;
            $("#reaspekt_geobase_info").animate({ height: 'hide' }, "fast");
            if (search.val() == '' && !$('#reaspekt_geobase_btn').prop('disabled'))
                $('#reaspekt_geobase_btn').prop('disabled', true);

            if ($(event.target).closest("#reaspekt_geobase_search").length) return;
            search.val('');
            event.stopPropagation();
        });
        var  reaspektOption_obtn = $('#reaspekt_description_open_btn'),
            reaspektOption_cbtn = $('#reaspekt_description_close_btn'),
            full = $('#reaspekt_description_full');

        reaspektOption_obtn.click(function(event) {
            full.show(175);
            $(this).hide();
            reaspektOption_cbtn.show();
        });

        reaspektOption_cbtn.click(function(event) {
            full.hide(175);
            $(this).hide();
            reaspektOption_obtn.show();
        });
        document.getElementById("reaspekt_geobase_search").addEventListener('keydown', function(event) {
            if (event.keyCode == 13) {
                event.preventDefault();
            }
        });
    });

    function reaspekt_geobase_add_city() { // on click Select
        $('#reaspekt_geobase_btn').prop('disabled', false);
        $("#reaspekt_geobase_info").animate({ height: 'hide' }, "fast");
    }

    function reaspekt_geobase_load() {
        reaspekt_geobase.timer = 0;

        let obData = {
            'sessid': BX.message('bitrix_sessid'),
            'lang': BX.message('LANGUAGE_ID'),
            'cityName': reaspekt_geobase.letters,
            'action': 'search'
        };

        BX.ajax.runAction('reaspekt:geobase.admin.updateSelectedCities', {
            data: {obData},            
            timeout: 10000,
        }).then(function(response) {
            let list = $('select#reaspekt_geobase_info');
            let citiesList = response.data;

            list.html('');
            if (citiesList == '' || citiesList == null) {
                list.animate({ height: 'hide' }, "fast");
            } else {
                let arOut = '';
                for (let i = 0; i < citiesList.length; i++) {
                    let sOptVal = 
                    citiesList[i]['CITY'] 
                        + (typeof(citiesList[i]['REGION']) == "undefined" || citiesList[i]['REGION'] == null ? '' : ', ' 
                        + citiesList[i]['REGION'])
                        + (typeof(citiesList[i]['OKRUG']) == "undefined" || citiesList[i]['OKRUG'] == ' ' || citiesList[i]['OKRUG'] == null ? '' : ', ' 
                        + citiesList[i]['OKRUG'])
                    ;
                    arOut += 
                        '<option id="reaspekt_geobase_inp' 
                        + (typeof(citiesList[i]['ID']) == "undefined" ? citiesList[i]['ID'] : citiesList[i]['ID']) 
                        + '"'
                        + 'value = "' 
                        + sOptVal 
                        + '">' 
                        + sOptVal 
                        + '</option>\n'
                    ;
                }
                list.html(arOut);
                list.reaspekt_geobase_light(reaspekt_geobase.letters);
                reaspekt_geobase_select_sizing();
                list.animate({ height: 'show' }, "fast");
            }
        }, function(response) {
            console.log(response);
        });
    }

    function reaspekt_geobase_selKey(e) { // called when a key is pressed in Select
        e = e || window.event;
        t = (window.event) ? window.event.srcElement : e.currentTarget; // The object which caused

        if (e.keyCode == 13) { // Enter
            reaspekt_geobase_onclick('Enter');
            $("#reaspekt_geobase_info").animate({ height: 'hide' }, "fast");
            return;
        }
        if (e.keyCode == 38 && t.selectedIndex == 0) { // up arrow
            $('.reaspekt_geobase_find input[name=reaspekt_geobase_search]').focus();
            $("#reaspekt_geobase_info").animate({ height: 'hide' }, "fast");
        }
    }

    function reaspekt_geobase_inpKey(e) { // input search
        e = e || window.event;
        t = (window.event) ? window.event.srcElement : e.currentTarget; // The object which caused
        var list = $('select#reaspekt_geobase_info');

        if (e.keyCode==40) {    // down arrow
            if (list.html() != '') {
                list.animate({ height: 'show' }, "fast");
            }
            list.focus();
            return;
        }
        var sFind = BX.util.trim(t.value);

        if (reaspekt_geobase.letters == sFind)
            return; // prevent frequent requests to the server
        reaspekt_geobase.letters = sFind;
        if (reaspekt_geobase.timer) {
            clearTimeout(reaspekt_geobase.timer);
            reaspekt_geobase.timer = 0;
        }
        if (reaspekt_geobase.letters.length < 2) {
            list.animate({ height: 'hide' }, "fast");
            return;
        }
        reaspekt_geobase.timer = window.setTimeout('reaspekt_geobase_load()', 190); // Load through 70ms after the last keystroke
    }

    jQuery.fn.reaspekt_geobase_light = function(pat) {
        function reaspekt_geobase_innerLight(node, pat) {
            var skip = 0;
            if (node.nodeType == 3) {
                var pos = node.data.toUpperCase().indexOf(pat);
                if (pos >= 0) {
                    var spannode = document.createElement('span');
                    spannode.className = 'reaspekt_geobase_light';
                    var middlebit = node.splitText(pos);
                    var endbit = middlebit.splitText(pat.length);
                    var middleclone = middlebit.cloneNode(true);
                    spannode.appendChild(middleclone);
                    middlebit.parentNode.replaceChild(spannode, middlebit);
                    skip = 1;
                }
            }
            else if (node.nodeType == 1 && node.childNodes && !/(script|style)/i.test(node.tagName)) {
                for (var i = 0; i < node.childNodes.length; ++i) {
                    i += reaspekt_geobase_innerLight(node.childNodes[i], pat);
                }
            }
            return skip;
        }
        return this.each(function() {
            reaspekt_geobase_innerLight(this, pat.toUpperCase());
        });
    };

    jQuery.fn.reaspekt_geobase_removeLight = function() {
        return this.find("span.reaspekt_geobase_light").each(function() {
            this.parentNode.firstChild.nodeName;
            with(this.parentNode) {
                replaceChild(this.firstChild, this);
                normalize();
            }
        }).end();
    };
</script>
<style>
.reasp-main {
    width: 510px;
    padding: 10px;
    border: none;
    margin: auto;
    margin-bottom: 15px;
}
.reasp-gbase-main-box {
    display: block;
    padding-bottom: 10px;
    margin-bottom: 5px;
    position: relative;
    width: 100%;
}
.reasp-gbase-main-box span {
    display: inline-block;
}
.reasp-gbase-main-box > div {
    margin: 10px 0 5px 0;
    text-align: right;
}
.reasp-gbase-progress-bar {
    width: 100%;
    height: 14px
}
.reasp-gbase-progress-bar span {
    position: absolute;
}
.reasp-gbase-progress-bar > span {
    border: 1px solid silver;
    width: 92%;
    left: 2px;
    height: 14px;
    text-align: left;
}
.reasp-gbase-progress-bar > span + span {
    padding-left: 2px;
    border: none;
    width: 7%;
    height: 14px;
    left: auto;
    right: 0;
    text-align: right
}
#progressLoad {
    height: 14px;
    background: #637f9c;
}
#reaspektShowInstructionBtn {
    margin: auto;
}
#reaspektMaxMindInstruction img {
    max-width: 100%
}
</style>

<script language="JavaScript">
function updateDB(dataIncome) {
    var progress, value, title, send;
    if ( document.getElementsByName('reaspekt_set_apikey')[0].value == "" || document.getElementsByName('reaspekt_set_apikey')[0].value == "undefined" ) {
        document.getElementById('reaspektOptionNoticesLoad').innerHTML = "<?=Loc::getMessage("REASPEKT_GEOBASE_NO_API")?>";
        document.getElementById('reaspektOptionNoticesLoad').style.display = "block";
    } else {
        document.getElementById('reaspektOptionNoticesLoad').innerHTML = "";
        document.getElementById('reaspektOptionNoticesLoad').style.display = "none";

        progress = document.getElementById('progressLoad');
        title = document.getElementById('titleLoad');
        value = document.getElementById('valueLoad');
        notices = document.getElementById('reaspektOptionNoticesLoad');

        if (dataIncome.PROGRESS == undefined) {
            dataIncome.PROGRESS = 0;
        }
        if (dataIncome.PROGRESS < 100) {
            document.getElementById('reaspektDBUpdateBtn').disabled = true;
        }

        title.innerHTML = "<?=Loc::getMessage("REASPEKT_PROCESSING")?>";

        progress.style.width = dataIncome.PROGRESS + '%';
        value.innerHTML = dataIncome.PROGRESS + '%';

        if (dataIncome.FINISHED == "Y") {
            document.getElementById('reaspektOptionLoaderUILoad').style.display = 'none';
            document.getElementById('reaspektDBUpdateBtn').style.display = 'none';
            notices.innerHTML = "<?=Loc::getMessage("REASPEKT_NOTICE_DBUPDATE_SUCCESSFUL")?>";

            notices.style.display = 'block';
        } else {
            sendUpdateRequest();
        }
    }
}

function sendUpdateRequest() {
    BX.ajax.runAction('reaspekt:geobase.admin.update').then(function(response) {
        console.log(response);
        updateDB(response.data);
    }, function(response) {
        console.log(response);
    });
}
function toggleDisplayInstruction() {
    BX.toggleClass(BX("reaspektMaxMindInstruction"), ["", "hidden"]);
}
</script>
<?
$incMod = CModule::IncludeModuleEx($module_id);
if ($incMod == '0') {
    CAdminMessage::ShowMessage(Array("MESSAGE" => Loc::getMessage("REASPEKT_GEOBASE_NF", Array("#MODULE#" => $module_id)), "HTML" => true, "TYPE"=>"ERROR"));
} elseif ($incMod == '2') {
    ?><span class="errortext"><?=Loc::getMessage("REASPEKT_GEOBASE_DEMO_MODE", Array("#MODULE#" => $module_id))?></span><br/><?
} elseif ($incMod == '3') {
    CAdminMessage::ShowMessage(Array("MESSAGE" => Loc::getMessage("REASPEKT_GEOBASE_DEMO_EXPIRED", Array("#MODULE#" => $module_id)), "HTML" => true, "TYPE"=>"ERROR"));
}
?>
<form method='POST' action='<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($request['mid'])?>&amp;lang=<?=$request['lang']?>' name='reaspekt_geobase_settings'><?
    $tabControl->Begin();
    $tabControl->BeginNextTab();
        ShowParamsHTMLByArray($arAllOptions["edit1"]);
        ?>
        <input type="button" id="reaspektShowInstructionBtn" onclick="toggleDisplayInstruction(); return false;" value="<?=Loc::getMessage("REASPEKT_GEOBASE_INSTRUCTION_API_BTN")?>">
        <div id="reaspektMaxMindInstruction" class="adm-info-message hidden"><?=str_replace("#FOLDER#", $currentFolder, Loc::getMessage("REASPEKT_GEOBASE_INSTRUCTION_API"))?></div><?

    $tabControl->BeginNextTab();
        ?>
        <tr class="heading">
            <td colspan="2"><?=Loc::getMessage("REASPEKT_INP_CITY_LIST")?></td>
        </tr>

        <tr>
            <td colspan="2">
                <table class="internal" width="100%">
                    <tbody id="reaspekt_geobase_cities_table">
                    <tr class="heading" id="reaspekt_geobase_table_header">
                    <td><?=Loc::getMessage("REASPEKT_GEOBASE_TABLE_DEFAULT_CITY_TD1")?></td>
                    <td><?=Loc::getMessage("REASPEKT_GEOBASE_TABLE_DEFAULT_CITY_TD2")?></td>
                    <td><?=Loc::getMessage("REASPEKT_GEOBASE_TABLE_DEFAULT_CITY_TD4")?></td>
                    <td><?=Loc::getMessage("REASPEKT_GEOBASE_TABLE_DEFAULT_CITY_TD5")?></td>
                    <td><?=Loc::getMessage("REASPEKT_GEOBASE_TABLE_DEFAULT_CITY_TD6")?></td>
                    <td><?=Loc::getMessage("REASPEKT_GEOBASE_TABLE_DEFAULT_CITY_TD7")?></td>
                    </tr>
                    <?
                    if ($incMod != '0' && $incMod != '3') {
                        DefaultCities::updateCityRows();
                    }
                    ?>
                    </tbody>
                </table>
            </td>
        </tr>
        <tr class="heading">
            <td colspan="2"><?=Loc::getMessage("REASPEKT_INP_CITY_ADD")?></td>
        </tr>
        <tr>
            <td>
                <input type="hidden" value="<?=$reaspekt_city_manual_default?>" name="reaspekt_city_manual_default" />
                <input type="text" size="100" maxlength="255" id="reaspekt_geobase_search" onkeyup="reaspekt_geobase_inpKey(event);" autocomplete="off" placeholder="<?=Loc::getMessage("REASPEKT_INP_ENTER_CITY");?>" name="reaspekt_geobase_search" value="">
                <br/>
                <select id="reaspekt_geobase_info" ondblclick="reaspekt_geobase_onclick();" onkeyup="reaspekt_geobase_selKey(event);" onchange="reaspekt_geobase_select_change(event);" onclick="reaspekt_geobase_add_city();" size="2" style="display: none;">
                </select>
            </td>
        </tr>
        <tr>
            <td>
                <input type="submit" id="reaspekt_geobase_btn" value="<?=Loc::getMessage("REASPEKT_TABLE_CITY_ADD");?>" onclick="reaspekt_geobase_onclick(); return false;" disabled="true">
            </td>
        </tr>
    <?

    $tabControl->BeginNextTab();
        ?>
        <tr class="heading">
            <td colspan="2"><?=(Local::statusTableDB() ? Loc::getMessage("REASPEKT_GEOBASE_DB_UPDATE_IPGEOBASE") : Loc::getMessage("REASPEKT_GEOBASE_DB_LOAD_IPGEOBASE"))?></td>
        </tr>
        <tr>
            <td colspan="2">
                <div class="reasp-main" style="text-align: center">
                    <div id="reaspektOptionNoticesLoad" class="adm-info-message" style="display: none">
                        <br><br>
                    </div>
                    <div class="reaspekt_option-main-box reasp-gbase-main-box" id="reaspektOptionLoaderUILoad">
                        <h3 id="titleLoad"><?=Loc::getMessage("REASPEKT_TITLE_LOAD_FILE")?></h3>
                        <span class="reasp-gbase-progress-bar">
                            <span>
                                <span id="progressLoad"></span>
                            </span>
                            <span id="valueLoad">0%</span>
                        </span>
                    </div>
                    <input type="button" id="reaspektDBUpdateBtn" onclick="updateDB({PROGRESS: 0, FINISHED: 'N'}); return false;" value="<?=(Local::statusTableDB() ? Loc::getMessage("REASPEKT_GEOBASE_UPLOAD_UPDATE") : Loc::getMessage("REASPEKT_GEOBASE_UPLOAD"))?>">
                </div>
            </td>
        </tr>
        <?ShowParamsHTMLByArray($arAllOptions["edit4"]);

    $tabControl->BeginNextTab();
        require_once($documentRoot . "/bitrix/modules/main/admin/group_rights.php");

    $tabControl->Buttons(); ?>
        <input type="submit" name="Update" value="<?echo Loc::getMessage('MAIN_SAVE')?>" class="adm-btn-save" title="<?=GetMessage("MAIN_OPT_SAVE_TITLE")?>">
        <input type="submit" name="Apply" value="<?echo Loc::getMessage('MAIN_OPT_APPLY')?>" title="<?=GetMessage("MAIN_OPT_APPLY_TITLE")?>" >
        <input type="reset" name="reset" value="<?echo Loc::getMessage('MAIN_RESET')?>">
        <input type="submit" name="RestoreDefaults" title="<?echo GetMessage("MAIN_HINT_RESTORE_DEFAULTS")?>" OnClick="return confirm('<?echo AddSlashes(GetMessage("MAIN_HINT_RESTORE_DEFAULTS_WARNING"))?>')" value="<?echo GetMessage("MAIN_RESTORE_DEFAULTS")?>">
        <?=bitrix_sessid_post();?>
    <? $tabControl->End(); ?>
</form>