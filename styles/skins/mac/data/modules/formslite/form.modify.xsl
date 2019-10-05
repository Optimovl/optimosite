<?xml version="1.0" encoding="UTF-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common"[
	<!ENTITY sys-module 'data'>
	<!ENTITY sys-module 'formslite'>
]>

<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:template match="properties/group[@name='forma']" mode="form-modify">
    	<xsl:variable name="label_name">
			<xsl:text>&label-name;</xsl:text>
		</xsl:variable>
		<div class="panel properties-group">
			<div class="header">
				<span>
					<xsl:value-of select="@title" />
				</span>
				<div class="l" /><div class="r" />
			</div>
			<div class="content">
				<xsl:call-template name="std-form-name">
						<xsl:with-param name="value" select="../../@name" />
						<xsl:with-param name="label" select="$label_name" />
						<xsl:with-param name="show-tip" select="0" />
				</xsl:call-template>
				<xsl:apply-templates select="field" mode="form-modify" />
				<xsl:choose>
					<xsl:when test="$data-action = 'create'">
						<xsl:call-template name="std-form-buttons-add" />
					</xsl:when>
					<xsl:otherwise>
						<xsl:call-template name="std-form-buttons" />
					</xsl:otherwise>
				</xsl:choose>
			</div>
		</div>
	</xsl:template>



	<xsl:template match="/result[@method = 'docs']">
			<p>
<a href="http://screator.pro/" target="_blank">SCreator</a> - предоставляет комплексные услуги по разработке сайтов, индивидуального программного обеспечения, автоматизации вашего бизнеса построенного на платформе 1С Предприятие.
</p>
		<h1>Отправка формы</h1>
			Форму необходимо отправлять на <strong>/formslite/send/</strong>
			<pre style="background-color: #C1D3E8;padding:5px;"><code>&lt;form action=&quot;/formslite/send/&quot; method=&quot;post&quot;&gt;</code></pre>
			<p><strong>Пример формы</strong>
            	<pre style="background-color: #C1D3E8;padding:5px;"><code>&lt;form action=&quot;/formslite/send/&quot; method=&quot;post&quot;&gt;
&nbsp;&nbsp;&nbsp; &lt;input name=&quot;system_form&quot; type=&quot;hidden&quot; value=&quot;test&quot;/&gt;
&nbsp;&nbsp;&nbsp; &lt;input name=&quot;ref_onsuccess&quot; type=&quot;hidden&quot; value=&quot;/formslite/posted/test/&quot; /&gt;
&nbsp;&nbsp;&nbsp; &lt;input name=&quot;test&quot; type=&quot;text&quot; /&gt;
&nbsp;&nbsp;&nbsp; &lt;input class=&quot;textinputs&quot; value=&quot;Отправить&quot; type=&quot;submit&quot; /&gt;
&lt;/form&gt;</code></pre></p>
<p><strong>Пример шаблонна formslite/posted на XSLT</strong>
            	<pre style="background-color: #C1D3E8;padding:5px;"><code>&lt;?xml version=&quot;1.0&quot; encoding=&quot;UTF-8&quot;?&gt;
&lt;xsl:stylesheet version=&quot;1.0&quot;
xmlns:umi=&quot;http://www.umi-cms.ru/TR/umi&quot;
xmlns:xsl=&quot;http://www.w3.org/1999/XSL/Transform&quot;&gt;
&lt;xsl:template match=&quot;result[@module = &#39;formslite&#39; and @method = &#39;posted&#39;]&quot;&gt;
&lt;xsl:apply-templates
select=&quot;document(concat(&#39;udata://formslite/posted&#39;,param0))/udata&quot; /&gt;
&lt;/xsl:template&gt;
&lt;xsl:template match=&quot;udata[@module = &#39;formslite&#39; and @method = &#39;posted&#39;]&quot;&gt;
&lt;xsl:value-of select=&quot;.&quot; disable-output-escaping=&quot;yes&quot; /&gt;
&lt;/xsl:template&gt;
&lt;/xsl:stylesheet&gt;</code></pre></p>

  			<p><strong>Пример шаблонна formslite/posted на PHP</strong>
            	<pre style="background-color: #C1D3E8;padding:5px;"><code>&lt;h1&gt;&lt;?= $variables[&#39;@header&#39;] ?&gt;&lt;/h1&gt;
&lt;div&gt;&lt;?=$this-&gt;macros(&#39;formslite&#39;, &#39;posted&#39;, array($this-&gt;getRequest(&#39;param0&#39;)))?&gt;&lt;/div&gt;</code></pre></p>
Создайте файл в <strong>/templates/Ваш_шаблон/xslt/mail/default.xsl</strong> с содержимым
			<pre style="background-color: #C1D3E8;padding:5px;"><code>&lt;?xml version=&quot;1.0&quot; encoding=&quot;utf-8&quot;?&gt;
&lt;!--DOCTYPE xsl:stylesheet SYSTEM &quot;ulang://i18n/constants.dtd:file&quot;--&gt;

&lt;xsl:stylesheet version=&quot;1.0&quot; xmlns:xsl=&quot;http://www.w3.org/1999/XSL/Transform&quot;&gt;

&lt;xsl:output method=&quot;html&quot; /&gt;

	&lt;xsl:template match=&quot;body&quot;&gt;
		&lt;html&gt;
			&lt;head&gt;
			&lt;/head&gt;
			&lt;body&gt;
				&lt;xsl:value-of select=&quot;content&quot; disable-output-escaping=&quot;yes&quot; /&gt;
			&lt;/body&gt;
		&lt;/html&gt;
	&lt;/xsl:template&gt;

&lt;/xsl:stylesheet&gt;</code></pre>
			<h1>Шаблоны писем</h1>
            Шаблоны писем предназначены для формирования писем из тех данных, которые отправляются пользователем при помощи форм обратной связи. Т.е. шаблон писем позволяет оформить данные, посланные пользователем, в произвольном виде. Например, добавить дополнительные пояснения к данным.
            <p style="background-color: #C1D3E8;padding:5px;">
            <strong>Примечание.</strong> Шаблон письма не является обязательным. Если вы создадите форму, но не создадите шаблон письма, то данные из формы все равно будут получены адресатом. Шаблон лишь позволяет оформить эти данные в более удобном для восприятия виде.
            </p>
            <h2>Добавление шаблона письма</h2>
            <p>Для того, чтобы создать новый шаблон письма, нажмите на ссылку <strong>Добавить шаблон</strong> над списком шаблонов.</p>
            <h3>Форма</h3>
            <p>Здесь нужно ввести следующие данные:</p>
            	<p><strong>Название формы</strong></p>
            	<p><strong>Идентификатор формы</strong>, к которой будет применяться данный шаблон писем. <br />
            	Для привязке шаблонна к формы, в форме должна быть такая строка
            	<pre style="background-color: #C1D3E8;padding:5px;"><code>&lt;input name=&quot;system_form&quot; type=&quot;hidden&quot; value=&quot;<strong>Идентификатор формы</strong>&quot;/&gt;</code></pre>
            	</p>
            	<h3>Письмо</h3>
            <p>Здесь нужно ввести следующие данные:</p>
            <p><strong>Адрес от</strong>. E-mail, который будет указан как адрес отправителя при получении письма получателем. В данном случае адрес отправителя может быть либо указан вами непосредственно (например, user@example.ru), либо вы можете указать в этом поле макрос, выводящий  в форме специальное поле для ввода пользователем своего e-mail адреса. Для этого предварительно нужно создать поле для ввода e-mail-а в форме, для которой создается шаблон.</p>
            <p><strong>Имя от</strong>. Имя, которое будет указано как имя отправителя при получении письма получателем. Порядок заполнения этого поля аналогичен описанному выше для поля "Адрес от".</p>
            <p><strong>Тема письма</strong>. Тема письма.</p>
            <p><strong>Шаблон тела письма</strong>. В тексте письма нужно при помощи макросов вставить содержимое полей формы. Если вы добавили в форму поле с идентификатором <strong>name</strong>, то значение этого поля при отправке письма можно получить через макрос <strong>%name%</strong>. Также можно добавить любое желаемое содержимое.</p>

            <h3>Сообщения</h3>
            <p>Здесь нужно ввести следующие данные:</p>
            <p><strong>Сообщение об отправке</strong>. Сообщение об успешной отправке письма из формы.</p>
            <p>Для корректной работы этого функционала, необходимо в макрос formslite posted передавать <strong>Идентификатор формы</strong>.</p>
            <pre style="background-color: #C1D3E8;padding:5px;"><code>&lt;input name=&quot;ref_onsuccess&quot; type=&quot;hidden&quot; value=&quot;<strong>{$lang-prefix}/formslite/posted/Идентификатор формы/</strong>&quot; /&gt;</code></pre>
            <h2>Редактирование шаблона письма</h2>
            <p>Для того чтобы отредактировать существующий шаблон письма, в списке шаблонов  нажмите на пиктограмму в колонке <strong>Редактировать</strong>, либо нажмите на название  шаблона в колонке <strong>Название</strong>.</p>
			<p>Форма редактирования шаблона аналогична форме добавления, описанной выше.</p>
            <br /><br />
            <p style="background-color: #C1D3E8;padding:5px;">При создании данного документа использовались материалы сайта <a href="http://dev.docs.umi-cms.ru" target="_blank">http://dev.docs.umi-cms.ru</a>.</p>
	</xsl:template>

</xsl:stylesheet>
