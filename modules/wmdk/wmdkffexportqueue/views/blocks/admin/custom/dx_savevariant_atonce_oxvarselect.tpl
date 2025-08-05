<td class="[{$listclass}]" data-placeholder="oxarticles__oxvarselect"></td>
<td class="[{$listclass}]" data-column="wmdkffexport__mapping">
    <select name="variantOnce[mapping]" class="editinput wmdkffexportmapping" style="max-width: 100px;" [{$readonly}]>

        <option value="">--</option>

        <option value="UNMAPPED" [{if $variantOnceMapping == "UNMAPPED"}]SELECTED[{/if}]>
            [{oxmultilang ident="WMDK_UNMAPPED"}]
        </option>

        [{foreach from=$oView->getMappingOptions() item=sOptionRaw}]
            [{assign var="sOption" value=$sOptionRaw|trim}]
                [{if $sOption}]
                    <option value="[{$sOption}]" [{if $variantOnceMapping == $sOption}]SELECTED[{/if}]>[{$sOption}]</option>
                [{/if}]
        [{/foreach}]
    </select>
</td>