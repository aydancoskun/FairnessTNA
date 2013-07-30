{include file="header.tpl"}
<div id="rowContent">
  <div id="titleTab"><div class="textTitle"><span class="textTitleSub">{$title} {$APPLICATION_NAME}</span></div>
</div>
<div id="rowContentInner">
		<table class="editTable">
		<form method="get" action="{$smarty.server.SCRIPT_NAME}">
				<tr>
					<td class="tblPagingLeft" colspan="7" align="right">
						<br>
					</td>
				</tr>

				<tr class="tblDataWhiteNH">
					<td colspan="2">
						<br>
						<a href="http://{$ORGANIZATION_URL}"><img src="{$BASE_URL}/send_file.php?object_type=primary_company_logo" style="width:auto; height:42px;" alt="Time And Attendance"></a>
						<br>
						<br>
					</td>
				</tr>

				{if $data.new_version == 1}
				<tr class="tblDataWarning">
					<td colspan="2">
						<br>
						{t escape="no"}<b>NOTICE:</b> There is a new version of <b>{$APPLICATION_NAME}</b> available.{/t}
						<br>
						{t}This version may contain tax table updates necessary for accurate payroll calculation, we recommend that you upgrade as soon as possible.{/t}
						<br>
						{t escape="no"}The latest version can be downloaded from:{/t} <a href="https://github.com/Aydan/fairness" target="_blank"><b>https://github.com/Aydan/fairness</b></a>
						<br>
						<br>
					</td>
				</tr>
				{/if}
				<tr>
					<td colspan="2" class="cellLeftEditTable">
						<div align="center">{t}System Information{/t}</div>
					</td>
				</tr>

				<tr onClick="showHelpEntry('version')">
					<td class="cellLeftEditTable">
						{t}Version:{/t}
					</td>
					<td class="cellRightEditTable">
						{$data.system_version}
						{if DEPLOYMENT_ON_DEMAND == FALSE}
							<input type="submit" name="action:Check_For_Updates" value="{t}Check For Updates{/t}"/>
						{/if}
					</td>
				</tr>

				<tr onClick="showHelpEntry('tax_engine_version')">
					<td class="cellLeftEditTable">
						{t}Tax Engine Version:{/t}
					</td>
					<td class="cellRightEditTable">
						{$data.tax_engine_version}
					</td>
				</tr>

				<tr onClick="showHelpEntry('tax_data_version')">
					<td class="cellLeftEditTable">
						{t}Tax Data Version:{/t}
					</td>
					<td class="cellRightEditTable">
						{$data.tax_data_version}
					</td>
				</tr>

				<tr onClick="showHelpEntry('version')">
					<td class="cellLeftEditTable">
						{t}Maintenance Jobs Last Ran:{/t}
					</td>
					<td class="cellRightEditTable">
						{if $data.cron.last_run_date != ''}
							{getdate type="DATE+TIME" epoch=$data.cron.last_run_date}
						{else}
							{t}Never{/t}
						{/if}
					</td>
				</tr>
				<tr>
					<td colspan="2" class="cellLeftEditTable">
						<div align="center">{t}Schema Version{/t}</div>
					</td>
				</tr>

				{if $data.schema_version_group_A != '' }
				<tr onClick="showHelpEntry('schema_version')">
					<td class="cellLeftEditTable">
						{t}Group A:{/t}
					</td>
					<td class="cellRightEditTable">
						{$data.schema_version_group_A}
					</td>
				</tr>
				{/if}

				{if $data.schema_version_group_B != '' }
				<tr onClick="showHelpEntry('schema_version')">
					<td class="cellLeftEditTable">
						{t}Group B:{/t}
					</td>
					<td class="cellRightEditTable">
						{$data.schema_version_group_B}
					</td>
				</tr>
				{/if}

				{if $data.schema_version_group_T != '' }
				<tr onClick="showHelpEntry('schema_version')">
					<td class="cellLeftEditTable">
						{t}Group T:{/t}
					</td>
					<td class="cellRightEditTable">
						{$data.schema_version_group_T}
					</td>
				</tr>
				{/if}

				<tr>
					<td colspan="2" class="cellLeftEditTable">
						<div align="center">{t}License{/t}</div>
					</td>
				</tr>

				<tr>
					<td class="cellLeftEditTable">
						{t}Attribution:{/t}
					</td>
					<td class="cellRightEditTable">
						Credit is given and thanks is extended to Timetrex for the original codebase of this project.<br><br>

						This software is licensed under the GNU Affero General Public License version 3, as is theirs.<br><br>
					</td>
				</tr>


				<tr>
					<td class="cellLeftEditTable">
						{t}Source Code:{/t}
					</td>
					<td class="cellRightEditTable">
						The source code of Fairness is publicly hosted on <a href="https://github.com/Aydan/fairness" target="_blank"><strong>Github</strong></a>.
						<br>
						<br>
					</td>
				</tr>
				<tr>
					<td class="cellLeftEditTable">
						{t}License:{/t}
					</td>
					<td class="cellRightEditTable">
						{$data.license}
					</td>
				</tr>

				<tr>
					<td class="tblPagingLeft" colspan="7" align="right">
						<br>
					</td>
				</tr>
			</table>
		</form>
	</div>
</div>
{include file="footer.tpl"}
