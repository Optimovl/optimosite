<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common" [
	<!ENTITY sys-module	'formslite'>
]>

<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version="1.0">

	<xsl:template match="data[@type = 'list' and @action = 'view']">

		<div class="imgButtonWrapper">
				<a id="addTemplates" href="{$lang-prefix}/admin/&sys-module;/template_add/">&label-template-add;</a>
		</div>
		<xsl:call-template name="ui-smc-table">
			<xsl:with-param name="control-params" select="'templates'" />
			<xsl:with-param name="content-type" select="'objects'" />
			<xsl:with-param name="show-toolbar" select="'1'" />
			<xsl:with-param name="control-type-id" select="$param0" />
		</xsl:call-template>
	</xsl:template>

</xsl:stylesheet>