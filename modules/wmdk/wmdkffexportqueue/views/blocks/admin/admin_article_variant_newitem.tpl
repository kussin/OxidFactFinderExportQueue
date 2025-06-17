<td class="[{$listclass}]" colspan="2">&nbsp;</td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="15" maxlength="[{$listitem->oxarticles__oxvarselect->fldmax_length}]" name="editval[oxarticles__oxvarselect]" value="" [{$readonly}]></td>
<td class="[{$listclass}]" data-column="wmdkffexport__mapping">
    [{include file="admin_article_variant_listitem_mapping_select.tpl" is_new=true}]
</td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="10" maxlength="[{$listitem->oxarticles__oxartnum->fldmax_length}]" name="editval[oxarticles__oxartnum]" value="" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$listitem->oxarticles__oxprice->fldmax_length}]" name="editval[oxarticles__oxprice]" value="" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$listitem->oxarticles__oxsort->fldmax_length}]" name="editval[oxarticles__oxsort]" value="" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$listitem->oxarticles__oxstock->fldmax_length}]" name="editval[oxarticles__oxstock]" value="" [{$readonly}]></td>
<td class="[{$listclass}]">

    <select name="editval[oxarticles__oxstockflag]" class="editinput" [{$readonly}]>
        <option value="1">[{oxmultilang ident="GENERAL_STANDARD"}]</option>
        <option value="4">[{oxmultilang ident="GENERAL_EXTERNALSTOCK"}]</option>
        <option value="2">[{oxmultilang ident="GENERAL_OFFLINE"}]</option>
        <option value="3">[{oxmultilang ident="GENERAL_NONORDER"}]</option>
    </select>

</td>