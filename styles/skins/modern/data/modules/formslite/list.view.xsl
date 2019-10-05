<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common" [
	<!ENTITY sys-module	'formslite'>
]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">
	<xsl:template match="result" mode="tabs">
		<xsl:choose>
			<xsl:when test="count($tabs/items/item)">
				<div class="tabs module">
					<xsl:apply-templates select="$tabs" />
				</div>
				<div class="tabs-content module">
					<div id="page" class="section selected">
						<xsl:apply-templates select="." />
					</div>
				</div>
			</xsl:when>
			
			<xsl:otherwise>
				<xsl:apply-templates select="." />
			</xsl:otherwise>
		</xsl:choose>
		<style>
			.info_mod{
				height:50px;
				padding:0 30px;
			}

			.screator{
				float:right;
			}
			.screator a{
				line-height:30px;
				color:#000;
			}
		</style>
		<div class="info_mod">

			<div class="screator"><a href="http://screator.pro/" target="_blank">Разработка <img alt="Разработка SCREATOR" src="/images/cms/screator.png" /></a></div>
		</div>
	</xsl:template>

	<xsl:template match="data[@type = 'list' and @action = 'view']">
		<div class="tabs-content module">
		<div class="section selected">
		<div class="location" xmlns:umi="http://www.umi-cms.ru/TR/umi">
			<div class="imgButtonWrapper loc-left">
        		<a id="addTemplates" class="btn color-blue" href="{$lang-prefix}/admin/&sys-module;/template_add/">&label-template-add;</a>
        </div>
			<a class="btn-action loc-right infoblock-show">
				<i class="small-ico i-info"></i>
				<xsl:text>&help;</xsl:text>
			</a>
		</div>

		<div class="layout">
		<div class="column">
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="control-params" select="'templates'" />
			<xsl:with-param name="content-type" select="'objects'" />
			<xsl:with-param name="show-toolbar" select="'1'" />
			<xsl:with-param name="control-type-id" select="$param0" />
		</xsl:call-template>
		</div>
			<div class="column">
				<div id="info_block" class="infoblock">
					<h3>
						<xsl:text>&label-quick-help;</xsl:text>
					</h3>
					<div class="content" title="{$context-manul-url}">
					</div>
					<div class="infoblock-hide"></div>
				</div>
			</div>
		</div>
		</div>
		</div>
	</xsl:template>

</xsl:stylesheet>