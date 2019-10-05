<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns:php="http://php.net/xsl" xmlns:xslt="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="/result[@method = 'typograf_config']">
        <div class="tabs-content module">
            <div class="section selected">
                <div class="location">
                    <div class="save_size"></div>
                    <a class="btn-action loc-right infoblock-show">
                        <i class="small-ico i-info"></i>
                        <xsl:text>&help;</xsl:text>
                    </a>
                </div>
                <div class="layout">
                    <div class="column">
                        <form method="post" action="do/" id="typograf-config-form" enctype="multipart/form-data">
                            <xsl:apply-templates select="." mode="settings.modify" />
                            <div class="panel-settings typograf-update-content">
                                <div class="title">
                                    <h3>
                                        <xsl:text>Обновить типографику на&nbsp;сайте</xsl:text>
                                    </h3>
                                </div>
                                <div class="content">
                                    <p>
                                        <xsl:text>После нажатия на&nbsp;кнопку </xsl:text>
                                        <strong>
                                            <xsl:text>&laquo;Обновить&raquo;</xsl:text>
                                        </strong>
                                        <xsl:text>, весь контент вашего сайта будет обработан при помощи Типографа.</xsl:text>
                                        <br />
                                        <xsl:text>Эта опция необходима, если вы&nbsp;хотите массово обновить типографику на&nbsp;сайте, где ранее не&nbsp;использовался Типограф.</xsl:text>
                                        <br />
                                        <xsl:text>Впоследствии, весь добавляемый контент будет обрабатываться при помощи Типографа сразу после его добавления на&nbsp;сайт.</xsl:text>
                                    </p>
                                    
                                    <a href="#" id="typograf-mass-convert" class="config_tipograf_btn btn color-blue">
                                        <xsl:text>Обновить</xsl:text>
                                    </a>
                                </div>
                            </div>
                            <div class="row">
                                <xsl:call-template name="std-form-buttons-settings"/>
                            </div>
                        </form>
                    </div>
                    <div class="column">
                        <div  class="infoblock">
                            <h3>
                                <xsl:text>&label-quick-help;</xsl:text>
                            </h3>
                            <div class="content" title="./man/ru/config/typograf_config.html">
                            </div>
                            <div class="infoblock-hide"></div>
                        </div>
                    </div>
                </div>
            </div>
            <small>
                <a href="http://clean-code.ru/" target="_blank">
                    <img src="/styles/skins/modern/data/modules/config/img/ext_typograf_config/logo.png" alt="Создание сайтов, модулей, расширений для UMI.CMS" align="absmiddle" />&nbsp;&nbsp;
                    <xsl:text>Создание сайтов, модулей, расширений для&nbsp;UMI.CMS</xsl:text>
                </a>
            </small>
        </div>
        
        <link type="text/css" rel="stylesheet" href="/styles/skins/modern/data/modules/config/css/ext_typograf_config/style.css?{$system-build}" />
        <script type="text/javascript">	$.getScript("/styles/skins/modern/data/modules/config/js/ext_typograf_config/script.js");</script>
    </xsl:template>
	
    <xsl:template match="/result[@method = 'typograf_config']//group" mode="settings.modify">
        <div class="panel-settings">
            <div class="title">
                <h3>
                    <xsl:value-of select="@label" />
                </h3>
            </div>
            <div class="content">
                <table class="btable btable-striped middle-align">
                    <tbody>
                        <xsl:apply-templates select="option" mode="settings.modify" />
                    </tbody>
                </table>
            </div>
        </div>
    </xsl:template>

    <xsl:template match="/result[@method = 'typograf_config']//option[@type='boolean']" mode="settings.modify">
        <tr>
            <td class="eq-col">
                <label for="{@name}">
                    <xsl:choose>
                        <xsl:when test="@name = 'typograf_is_enabled'">
                            <xsl:value-of select="@label" />
                        </xsl:when>
                        <xsl:otherwise>
                            <xsl:value-of select="value/@title" />
                        </xsl:otherwise>
                    </xsl:choose>
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
                <xsl:attribute name="selected">
                    <xsl:text>selected</xsl:text>
                </xsl:attribute>
            </xsl:if>
            <xsl:value-of select="." disable-output-escaping="yes" />
        </option>
    </xsl:template>

</xsl:stylesheet>