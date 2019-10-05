<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/seo">
<xsl:stylesheet version="1.0"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:xlink="http://www.w3.org/TR/xlink">

    <xsl:param name="domain_id" />

    <xsl:template match="result[@module = 'seo' and @method = 'robots']">
        <xsl:variable name="domains-list" select="document('udata://core/getDomainsList')/udata/domains/domain" />

        <div id="webo_in">
            <div class="panel">
                <div class="header">
                    <span><xsl:text>&label-site-robots;</xsl:text></span>
                    <div class="l"></div>
                    <div class="r"></div>
                </div>
                <div class="content">

                    <form action="" method="get">
                        <div class="field">
                            <label for="host">
                                <span class="label">
                                    <acronym>
                                        &label-site-address;
                                    </acronym>
                                </span>
                            </label>
                            <select name="domain_id" id="domain-selector">
                                <xsl:apply-templates select="$domains-list" mode="domain-selector" />
                            </select>
                        </div>
                        <div class="buttons" style="padding-top:5px;">
                            <div class="button">
                                <input type="submit" value="&label-robots-retrieve;" /><span class="l" /><span class="r" />
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <form method="post" action="do/" enctype="multipart/form-data">
                <input type="hidden" name="domain_id" value="{$domain_id}" />

                <xsl:apply-templates select="data/group[@name = 'robots']" mode="settings.modify" />
            </form>
        </div>
    </xsl:template>

    <xsl:template match="domain" mode="domain-selector">
        <option value="{@id}">
            <xsl:if test="@id = $domain_id"><xsl:attribute name="selected">selected</xsl:attribute></xsl:if>
            <xsl:value-of select="@host"/>
        </option>
    </xsl:template>

    <xsl:template match="option[@type = 'text']" mode="settings.modify-option">
        <textarea name="{@name}" id="{@name}">
            <xsl:value-of select="value" disable-output-escaping="yes" />
        </textarea>
    </xsl:template>

</xsl:stylesheet>