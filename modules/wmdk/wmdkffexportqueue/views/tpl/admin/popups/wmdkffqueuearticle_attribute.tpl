[{include file="popups/headitem.tpl" title="GENERAL_ADMIN_TITLE"|oxmultilangassign}]

<script type="text/javascript">
    initAoc = function()
    {
        YAHOO.oxid.container1 = new YAHOO.oxid.aoc(
            'container1',
            [ [{foreach from=$oxajax.container1 item=aItem key=iKey}]
                [{$sSep}][{strip}]{ key:'_[{$iKey}]', ident: [{if $aItem.4}]true[{else}]false[{/if}]
                    [{if !$aItem.4}],
                label: '[{oxmultilang ident="GENERAL_AJAX_SORT_"|cat:$aItem.0|oxupper}]',
                visible: [{if $aItem.2}]true[{else}]false[{/if}]
                    [{/if}]}
                [{/strip}]
                [{assign var="sSep" value=","}]
                [{/foreach}] ],
            '[{$oViewConf->getAjaxLink()}]cmpid=container1&container=article_attribute&synchoxid=[{$oxid}]'
        );

        [{assign var="sSep" value=""}]

        YAHOO.oxid.container2 = new YAHOO.oxid.aoc(
            'container2',
            [ [{foreach from=$oxajax.container2 item=aItem key=iKey}]
                [{$sSep}][{strip}]{ key:'_[{$iKey}]', ident: [{if $aItem.4}]true[{else}]false[{/if}]
                    [{if !$aItem.4}],
                label: '[{oxmultilang ident="GENERAL_AJAX_SORT_"|cat:$aItem.0|oxupper}]',
                visible: [{if $aItem.2}]true[{else}]false[{/if}],
                formatter: YAHOO.oxid.aoc.custFormatter
                    [{/if}]}
                [{/strip}]
                [{assign var="sSep" value=","}]
                [{/foreach}] ],
            '[{$oViewConf->getAjaxLink()}]cmpid=container2&container=article_attribute&oxid=[{$oxid}]'
        );

        YAHOO.oxid.container1.getDropAction = function() { return 'fnc=addattr'; };
        YAHOO.oxid.container2.getDropAction = function() { return 'fnc=removeattr'; };

        // show/hide the dropdown wrapper
        function wmdkToggleColorMapping(bShow) {
            var eWrap = $('wmdk_colormapping_wrap');
            if (!eWrap) return;
            $D.setStyle(eWrap, 'display', bShow ? '' : 'none');

            // If hiding, reset select
            if (!bShow) {
                var eSel = $('wmdk_colormapping');
                if (eSel) { eSel.selectedIndex = 0; }
            }
        }

        YAHOO.oxid.container2.subscribe("rowClickEvent", function(oEventParam)
        {
            var aSelectedRows = YAHOO.oxid.container2.getSelectedRows();
            if (aSelectedRows.length) {
                var oRecord = YAHOO.oxid.container2.getRecord(aSelectedRows[0]);

                var sAttrTitle = (oRecord && oRecord._oData && oRecord._oData._0) ? ('' + oRecord._oData._0) : '';

                $('_attrname').innerHTML = sAttrTitle;
                $('attr_value').value    = oRecord._oData._2;
                $('attr_oxid').value     = oRecord._oData._3;

                $D.setStyle($('arrt_conf'), 'visibility', '');

                // ▼ Show dropdown ONLY if "Farbe"
                var bIsFarbe = (sAttrTitle.replace(/^\s+|\s+$/g, '') === 'Farbe');
                wmdkToggleColorMapping(bIsFarbe);

                if (bIsFarbe) {
                    var ePresetSelect       = $('wmdk_colormapping');
                    var sArticleMapping     = ($('wmdk_article_colormapping') ? $('wmdk_article_colormapping').value : '') || '';
                    var sCurrentAttrValue   = $('attr_value').value || '';

                    if (ePresetSelect) {
                        var bMatched = false;

                        if (sArticleMapping !== '') {
                            for (var i = 0; i < ePresetSelect.options.length; i++) {
                                if (ePresetSelect.options[i].value === sArticleMapping) {
                                    ePresetSelect.selectedIndex = i;
                                    bMatched = true;
                                    break;
                                }
                            }
                        }

                        if (!bMatched && sCurrentAttrValue !== '') {
                            for (var j = 0; j < ePresetSelect.options.length; j++) {
                                if (ePresetSelect.options[j].value === sCurrentAttrValue) {
                                    ePresetSelect.selectedIndex = j;
                                    bMatched = true;
                                    break;
                                }
                            }
                        }

                        if (!bMatched) {
                            ePresetSelect.selectedIndex = 0; // "Unmapped"
                        }
                    }
                }
            } else {
                $D.setStyle($('arrt_conf'), 'visibility', 'hidden');
                wmdkToggleColorMapping(false);
            }
        });

        YAHOO.oxid.container2.subscribe("dataReturnEvent", function() {
            $D.setStyle($('arrt_conf'), 'visibility', 'hidden');
            wmdkToggleColorMapping(false);
        });

        YAHOO.oxid.container2.onSave = function()
        {
            YAHOO.oxid.container1.getDataSource().flushCache();
            YAHOO.oxid.container1.getPage(0);
            YAHOO.oxid.container2.getDataSource().flushCache();
            YAHOO.oxid.container2.getPage(0);
        };
        YAHOO.oxid.container2.onFailure = function() { /* currently does nothing */};

        YAHOO.oxid.container2.saveAttribute = function()
        {
            var oCallback = {
                success: YAHOO.oxid.container2.onSave,
                failure: YAHOO.oxid.container2.onFailure,
                scope:   YAHOO.oxid.container2
            };

            var sUrl =
                '[{$oViewConf->getAjaxLink()}]'
                + '&cmpid=container2&container=article_attribute'
                + '&fnc=saveAttributeValue'
                + '&oxid=[{$oxid}]'
                + '&attr_value=' + encodeURIComponent($('attr_value').value)
                + '&attr_oxid='  + encodeURIComponent($('attr_oxid').value);

            YAHOO.util.Connect.asyncRequest('GET', sUrl, oCallback);
        };

        $E.addListener($('saveBtn'), "click", YAHOO.oxid.container2.saveAttribute, $('saveBtn'));

        var ePresetSelectInit = $('wmdk_colormapping');
        if (ePresetSelectInit) {
            $E.addListener(ePresetSelectInit, "change", function() {
                var sSelectedValue = ePresetSelectInit.value || '';
                if (sSelectedValue !== '') {
                    $('attr_value').value = sSelectedValue;
                }
            }, ePresetSelectInit);
        }

        var ePresetSaveBtn = $('wmdk_savePresetBtn');
        if (ePresetSaveBtn) {
            $E.addListener(ePresetSaveBtn, "click", function() {
                var eWrap = $('wmdk_colormapping_wrap');
                if (!eWrap || $D.getStyle(eWrap, 'display') === 'none') { return; }

                var eSelect   = $('wmdk_colormapping');
                var sSelected = eSelect ? (eSelect.value || '') : '';

                var sUrl =
                    '[{$oViewConf->getAjaxLink()}]'
                    + '&cmpid=container2&container=article_attribute'
                    + '&fnc=saveColorMapping'
                    + '&oxid=[{$oxid}]'
                    + '&colormapping_value=' + encodeURIComponent(sSelected);

                var oCallback = {
                    success: function() {
                        var eHidden = $('wmdk_article_colormapping');
                        if (eHidden) { eHidden.value = sSelected; }
                        // Refresh like core
                        YAHOO.oxid.container2.onSave();
                    },
                    failure: function() { /* no-op */ },
                    scope:   YAHOO.oxid.container2
                };

                YAHOO.util.Connect.asyncRequest('GET', sUrl, oCallback);
            }, ePresetSaveBtn);
        }
    };

    $E.onDOMReady(initAoc);
</script>

<table width="100%">
    <colgroup>
        <col span="2" width="40%" />
        <col width="20%" />
    </colgroup>
    <tr class="edittext">
        <td colspan="3">
            [{oxmultilang ident="GENERAL_AJAX_DESCRIPTION"}]<br>
            [{oxmultilang ident="GENERAL_FILTERING"}]<br /><br />
        </td>
    </tr>
    <tr class="edittext">
        <td align="center" valign="top"><b>[{oxmultilang ident="ARTICLE_ATTRIBUTE_NOATTRIBUTE"}]</b></td>
        <td align="center" valign="top"><b>[{oxmultilang ident="ARTICLE_ATTRIBUTE_ITEMSATTRIBUTE"}]</b></td>
        <td align="center" valign="top">[{oxmultilang ident="ARTICLE_ATTRIBUTE_SELECTONEATTR"}]:</td>
    </tr>
    <tr>
        <td valign="top" id="container1"></td>
        <td valign="top" id="container2"></td>

        <td valign="top" align="center" class="edittext" id="arrt_conf" style="visibility:hidden">
            <br><br>
            <b id="_attrname">[{$attr_name}]</b>:<br><br>
            <input id="attr_oxid" type="hidden">

            <!-- Hidden: current article mapping (for preselects) -->
            <input id="wmdk_article_colormapping" type="hidden" value="[{if $edit && $edit->oxarticles__wmdkvarselectmapping}][{$edit->oxarticles__wmdkvarselectmapping->value|escape:'html'}][{/if}]">

            <!-- Dropdown wrapper: shown only for title === "Farbe" -->
            <div id="wmdk_colormapping_wrap" style="display:none; margin-bottom:6px;">
                <select id="wmdk_colormapping" class="editinput" style="min-width:240px;">
                    <option value="">Unmapped</option>
                    [{assign var="oConfig" value=$oViewConf->getConfig()}]
                    [{assign var="aColorPresetOptions" value=$oConfig->getConfigParam('aWmdkFFClonedAttributeOxvarselectMapping')}]
                    [{if $aColorPresetOptions && is_array($aColorPresetOptions)}]
                        [{foreach from=$aColorPresetOptions item=sPresetOption}]
                            [{assign var="sPresetOptionTrimmed" value=$sPresetOption|trim}]
                            [{if $sPresetOptionTrimmed ne ''}]
                                <option value="[{$sPresetOptionTrimmed|escape:'html'}]">[{$sPresetOptionTrimmed|escape:'html'}]</option>
                            [{/if}]
                        [{/foreach}]
                    [{/if}]
                </select>
                <br><br>
                <input id="wmdk_savePresetBtn" type="button" class="edittext" value="[{oxmultilang ident='ARTICLE_ATTRIBUTE_SAVE'}] [{oxmultilang ident='WMDK_ARTICLE_VARIANT_MAPPING'}]">
                <br><br>
            </div>

            <input id="attr_value" class="editinput" type="text"><br><br>
            <input id="saveBtn" type="button" class="edittext" value="[{oxmultilang ident='ARTICLE_ATTRIBUTE_SAVE'}]">
        </td>
    </tr>
    <tr>
        <td class="oxid-aoc-actions">
            <input type="button" value="[{oxmultilang ident='GENERAL_AJAX_ASSIGNALL'}]" id="container1_btn">
        </td>
        <td class="oxid-aoc-actions">
            <input type="button" value="[{oxmultilang ident='GENERAL_AJAX_UNASSIGNALL'}]" id="container2_btn">
        </td>
        <td></td>
    </tr>
</table>

</body>
</html>
