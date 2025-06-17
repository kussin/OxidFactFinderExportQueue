[{assign var="listclass" value=listitem$blWhite}]
[{assign var="hasvariants" value=true}]
<td class="[{$listclass}]"><a href="Javascript:editThis('[{$listitem->oxarticles__oxid->value}]');" class="[{$listclass}]" [{include file="help.tpl" helpid=editvariant}]><img src="[{$oViewConf->getImageUrl()}]/editvariant.gif" width="15" height="15" alt="" border="0" align="absmiddle"></a></td>
<td class="[{$listclass}]" align="center"><input class="edittext" type="checkbox" name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__oxactive]" value='1' [{if $listitem->oxarticles__oxactive->value == 1}]checked[{/if}] [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="15" maxlength="[{$listitem->oxarticles__oxvarselect->fldmax_length}]" name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__oxvarselect]" value="[{$listitem->oxarticles__oxvarselect->value}]" [{$readonly}]></td>
<td class="[{$listclass}]" data-column="wmdkffexport__mapping">[{* TODO: #67042 OXVARSELECT Mapping *}]</td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="10" maxlength="[{$listitem->oxarticles__oxartnum->fldmax_length}]" name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__oxartnum]" value="[{$listitem->oxarticles__oxartnum->value}]" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$listitem->oxarticles__oxprice->fldmax_length}]" name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__oxprice]" value="[{$listitem->oxarticles__oxprice->value}]" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$listitem->oxarticles__oxsort->fldmax_length}]" name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__oxsort]" value="[{$listitem->oxarticles__oxsort->value}]" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$listitem->oxarticles__oxstock->fldmax_length}]" name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__oxstock]" value="[{$listitem->oxarticles__oxstock->value}]" [{$readonly}]></td>
<td class="[{$listclass}]">
    <select name="editval[[{$listitem->oxarticles__oxid->value}]][oxarticles__oxstockflag]" class="editinput" [{$readonly}]>
        <option value="1" [{if $listitem->oxarticles__oxstockflag->value == 1}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_STANDARD"}]</option>
        <option value="4" [{if $listitem->oxarticles__oxstockflag->value == 4}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_EXTERNALSTOCK"}]</option>
        <option value="2" [{if $listitem->oxarticles__oxstockflag->value == 2}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_OFFLINE"}]</option>
        <option value="3" [{if $listitem->oxarticles__oxstockflag->value == 3}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_NONORDER"}]</option>
    </select>
</td>
<td class="[{$listclass}]">
    <a href="Javascript:deleteThis('[{$listitem->oxarticles__oxid->value}]');" class="delete"[{include file="help.tpl" helpid=item_delete}]></a>
</td>