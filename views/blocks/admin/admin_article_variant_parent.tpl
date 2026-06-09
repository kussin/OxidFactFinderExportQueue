<td class="[{$listclass}]">&nbsp;</td>
<td class="[{$listclass}]">&nbsp;</td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="15" maxlength="[{$edit->oxarticles__oxvarselect->fldmax_length}]" name="editval[oxarticles__oxvarselect]" value="[{$edit->oxarticles__oxvarselect->value}]" [{$readonly}]></td>
<td class="[{$listclass}]" data-column="wmdkffexport__mapping"></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="16" maxlength="[{$edit->oxarticles__oxartnum->fldmax_length}]" name="editval[oxarticles__oxartnum]" value="[{$edit->oxarticles__oxartnum->value}]" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="16" maxlength="[{$edit->oxarticles__oxean->fldmax_length}]" name="editval[oxarticles__oxean]" value="[{$edit->oxarticles__oxean->value}]" [{$readonly}]></td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$edit->oxarticles__oxprice->fldmax_length}]" name="editval[oxarticles__oxprice]" value="[{$edit->oxarticles__oxprice->value}]" [{$readonly}]></td>
<td class="[{$listclass}]">&nbsp;</td>
<td class="[{$listclass}]"><input type="text" class="editinput" size="7" maxlength="[{$edit->oxarticles__oxstock->fldmax_length}]" name="editval[oxarticles__oxstock]" value="[{$edit->oxarticles__oxstock->value}]" [{$readonly}]></td>
<td class="[{$listclass}]">
    <select name="editval[oxarticles__oxstockflag]" class="editinput" [{$readonly}]>
        <option value="1" [{if $edit->oxarticles__oxstockflag->value == 1}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_STANDARD"}]</option>
        <option value="4" [{if $edit->oxarticles__oxstockflag->value == 4}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_EXTERNALSTOCK"}]</option>
        <option value="2" [{if $edit->oxarticles__oxstockflag->value == 2}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_OFFLINE"}]</option>
        <option value="3" [{if $edit->oxarticles__oxstockflag->value == 3}]SELECTED[{/if}]>[{oxmultilang ident="GENERAL_NONORDER"}]</option>
    </select>
</td>