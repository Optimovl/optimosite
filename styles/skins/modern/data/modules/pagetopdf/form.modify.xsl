<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common/"[
	<!ENTITY sys-module 'data'>
	<!ENTITY sys-module 'pagetopdf'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">



	<xsl:template match="/result[@method = 'docs']">
		<div class="location">
			<a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"></i><xsl:text>&help;</xsl:text></a>
		</div>

		<div class="layout">
			<div class="column">
				<div class="panel-settings">
					<div class="title">

					</div>
					<div class="content">
					<div class="tabs-content module">
						<div class="section selected">
			<p>
<a href="http://screator.kz/" target="_blank">SCreator</a> - предоставляет комплексные услуги по разработке сайтов, индивидуального программного обеспечения, автоматизации вашего бизнеса построенного на платформе 1С Предприятие.
</p><br />
<p>
<strong>Для более корректной работы модуля рекомендуем пропатчить файл .htaccess</strong>
</p>
<form method="post" action="/admin/pagetopdf/docs/do/" enctype="multipart/form-data">
			<input class="btn color-blue" type="submit" value="Установить/Удалить патч"/>
</form>
<p>
<strong>Внимание: модуль работает без данного патча. Но для лучшей индексации файлов поисковыми системами лучше установить патч.</strong>
</p>
			<h1>Дикументация по модулю</h1>

            <h2>API модуля</h2>
            <div style="background-color: #C1D3E8;padding:5px;">
            <strong>getLinkPDF(pageId, size, orientation)</strong> - Формирование ссылки на PDF файл<br />
pageId - ID страницы.
size - Размер страницы ('letter', 'A4', 'legal' и тд.). Не обязательный параметр, по умолчанию A4.
orientation - Ориентация ('portrait' или 'landscape'). Не обязательный параметр, по умолчанию portrait.
            </div>
            <h2>PHP-шаблонизатор</h2>

<div style="background-color: #C1D3E8;padding:5px;">
<code>&lt;?=$this-&gt;render($this-&gt;macros(&#39;pagetopdf&#39;, &#39;getLinkPDF&#39;, array($variables[&#39;full:page&#39;]-&gt;getId(), &#39;A4&#39;, &#39;portrait&#39;)), &#39;pagetopdf/linkpdf&#39;)?&gt;</code>
</div>

<strong>Шаблон</strong>

<div style="background-color: #C1D3E8;padding:5px;">
<code>&lt;a href=&quot;&lt;?=$variables[&#39;link&#39;]?&gt;&quot;&gt;Скачать в PDF&lt;/a&gt;&lt;br /&gt;</code>
</div>

<strong>Для примера пропишите вызов макроса в файл /templates/demodizzy/php/news/item.phtml и создайте шаблон /templates/demodizzy/php/pagetopdf/linkpdf.phtml для ссылки</strong>

<h2>XSLT-шаблонизатор</h2>

<strong>Пример вызова</strong>
<div style="background-color: #C1D3E8;padding:5px;">
<code>udata://pagetopdf/getLinkPDF/132/A4/portrait</code>
</div>

<strong>XML-ответ UData</strong>
<div style="background-color: #C1D3E8;padding:5px;">
<code>&lt;udata module=&quot;pagetopdf&quot; method=&quot;getLinkPDF&quot; generation-time=&quot;0.007504&quot;&gt;
	&lt;link&gt;/pagetopdf/file/132/A4/portrait/ne_speshite_ona_zakonchilas.pdf&lt;/link&gt;
&lt;/udata&gt;</code>
</div>

<h2>Шаблон PDF файла</h2>
Расположение шаблона для pdf /templates/{ваш_шаблон}/xslt/sys-tpls/pagetopdf.xsl, если у вас старый формат шаблонов то /xsltTpls/sys-tpls/pagetopdf.xsl<br /> <br />
<strong>Простой пример для Новости</strong>
<div style="background-color: #C1D3E8;padding:5px;">
<code>&lt;?xml version=&quot;1.0&quot; encoding=&quot;utf-8&quot;?&gt;
	&lt;!DOCTYPE xsl:stylesheet [ &lt;!ENTITY nbsp &quot;&amp;#160;&quot;&gt; ]&gt;

	&lt;xsl:stylesheet version=&quot;1.0&quot;
		xmlns:xsl=&quot;http://www.w3.org/1999/XSL/Transform&quot;
		xmlns:php=&quot;http://php.net/xsl&quot;
		xsl:extension-element-prefixes=&quot;php&quot;
		exclude-result-prefixes=&quot;php&quot;&gt;

	&lt;xsl:output encoding=&quot;utf-8&quot; method=&quot;html&quot; indent=&quot;yes&quot;/&gt;

	&lt;xsl:template match=&quot;/&quot;&gt;
		&lt;xsl:apply-templates /&gt;
	&lt;/xsl:template&gt;

	&lt;xsl:template match=&quot;/udata/object&quot;&gt;
		&lt;html&gt; &lt;meta http-equiv=&quot;content-type&quot; content=&quot;text/html; charset=utf-8&quot; /&gt;
			&lt;body&gt;

				&lt;p style=&quot;font-size: 20px;&quot;&gt;&lt;xsl:value-of select=&quot;//property[@name=&#39;h1&#39;]/value&quot; disable-output-escaping=&quot;yes&quot; /&gt;&lt;/p&gt;
				&lt;xsl:value-of select=&quot;//property[@name=&#39;content&#39;]/value&quot; disable-output-escaping=&quot;yes&quot; /&gt;
			&lt;/body&gt;
		&lt;/html&gt;
	&lt;/xsl:template&gt;

&lt;/xsl:stylesheet&gt;</code>
</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<div class="column">
				<div id="info_block" class="infoblock">
					<h3><xsl:text>&label-quick-help;</xsl:text></h3>
					<div class="content" title="{$context-manul-url}">
					</div>
					<div class="infoblock-hide"></div>
				</div>
			</div>
		</div>
	</xsl:template>

</xsl:stylesheet>
