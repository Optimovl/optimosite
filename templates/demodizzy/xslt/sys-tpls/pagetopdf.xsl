<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet [ <!ENTITY nbsp "&#160;"> ]>

<xsl:stylesheet	version="1.0"
		xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
		xmlns:php="http://php.net/xsl"
		xsl:extension-element-prefixes="php"
		exclude-result-prefixes="php">

	<xsl:output encoding="utf-8" method="html" indent="yes"/>

	<xsl:template match="/">
		<xsl:apply-templates />
	</xsl:template>

	<xsl:template match="/udata/object">
		<html>
			<meta http-equiv="content-type" content="text/html; charset=utf-8" />
			<body>
				
				<p style="font-size: 20px;"><xsl:value-of select="//property[@name='h1']/value" disable-output-escaping="yes" /></p>
				<xsl:value-of select="//property[@name='content']/value" disable-output-escaping="yes" />
			</body>
		</html>
	</xsl:template>

</xsl:stylesheet>
