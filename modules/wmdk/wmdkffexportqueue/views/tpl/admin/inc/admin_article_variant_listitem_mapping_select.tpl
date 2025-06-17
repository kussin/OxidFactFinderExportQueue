[{if $is_new}]
    [{assign var="sCurrentMappingValue" value=""}]
[{else}]
    [{assign var="sCurrentMappingValue" value=$listitem->oxarticles__wmdkvarselectmapping->value|trim}]
[{/if}]

<select name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__wmdkvarselectmapping]" class="editinput wmdkffexportmapping" [{$readonly}] style="max-width: 100px">
    <option value="" [{if $sCurrentMappingValue == ""}]SELECTED[{/if}]>[{oxmultilang ident="WMDK_UNMAPPED"}]</option>

    [{foreach from=$oView->getMappingOptions() item=sColorOptionRaw}]
        [{assign var="sColorOption" value=$sColorOptionRaw|trim}]

        [{if $sColorOption}]
            <option value="[{$sColorOption}]" [{if $sCurrentMappingValue == $sColorOption}]SELECTED[{/if}]>[{$sColorOption}]</option>
        [{/if}]
    [{/foreach}]
</select>