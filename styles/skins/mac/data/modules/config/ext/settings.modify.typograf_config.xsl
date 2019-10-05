<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:php="http://php.net/xsl" xmlns:xslt="http://www.w3.org/1999/XSL/Transform">

	<xsl:template match="/result[@method = 'typograf_config']">
		<link type="text/css" rel="stylesheet" href="/styles/skins/mac/data/modules/config/css/ext_typograf_config/style.css?{$system-build}" />
		<script type="text/javascript">	$.getScript("/styles/skins/mac/data/modules/config/js/ext_typograf_config/script.js");</script>
		<form method="post" action="do/" id="typograf-config-form" enctype="multipart/form-data">
			<xsl:apply-templates select="." mode="settings.modify" />
			<div class="panel properties-group typograf-update-content">
				<div class="header">
					<span>
						<xsl:text>Обновить типографику на&nbsp;сайте</xsl:text>
					</span>
					<div class="l" /><div class="r" />
				</div>
				<div class="content">
					<p>После нажатия на&nbsp;кнопку &laquo;Обновить&raquo;, весь контент вашего сайта будет обработан при помощи Типографа.<br />Эта опция необходима, если вы&nbsp;хотите массово обновить типографику на&nbsp;сайте, где ранее не&nbsp;использовался Типограф.<br />Впоследствии, весь добавляемый контент будет обрабатываться при помощи Типографа сразу после его добавления на&nbsp;сайт.</p>
					<div class="buttons">
						<div>
							<input type="button" value="Обновить" id="typograf-mass-convert" class="config_tipograf_btn" />
							<span class="l"></span>
							<span class="r"></span>
						</div>
					</div>
				</div>
			</div>
		</form>
		<small>
			<a href="http://clean-code.ru/" target="_blank"><img src="/styles/skins/mac/data/modules/config/img/ext_typograf_config/logo.png" alt="Создание сайтов, модулей, расширений для UMI.CMS" align="absmiddle" />&nbsp;&nbsp;<xsl:text>Создание сайтов, модулей, расширений для&nbsp;UMI.CMS</xsl:text></a>
		</small>
	</xsl:template>
	
	<xsl:template match="/result[@method = 'typograf_config']//group" mode="settings.modify">
		<div class="panel properties-group">
			<div class="header">
				<span>
					<xsl:value-of select="@label" />
				</span>
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				<table class="tableContent">
					<tbody>
						<xsl:apply-templates select="option" mode="settings.modify" />
					</tbody>
				</table>
				
				<xsl:call-template name="std-save-button" />
			</div>
		</div>
	</xsl:template>

	<xsl:template match="/result[@method = 'typograf_config']//group[@name!='typograf_globals']/option[@type='boolean']" mode="settings.modify">
		<tr>
			<td class="eq-col">
				<label for="{@name}">
					<xsl:value-of select="value/@title" />
				</label>
			</td>
			<td>
				<xsl:apply-templates select="." mode="settings.modify-option" />
			</td>
		</tr>
	</xsl:template>
	
	<xsl:template match="option[@type = 'multiple_select']" mode="settings.modify-option">
		<input type="hidden" name="{@name}" value="" />
		<select id="{@name}" name="_{@name}" multiple="multiple">
			<xsl:apply-templates select="value/item" mode="settings.modify-option.multiple_select">
				<xsl:with-param name="value" select="value/@id"/>
			</xsl:apply-templates>
		</select>
	</xsl:template>
	
	<xsl:template match="item" mode="settings.modify-option.multiple_select">
		<option value="{@id}">
			<xsl:if test="@is_selected = '1'">
				<xsl:attribute name="selected"><xsl:text>selected</xsl:text></xsl:attribute>
			</xsl:if>
			<xsl:value-of select="." disable-output-escaping="yes" />
		</option>
	</xsl:template>

</xsl:stylesheet>