<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE xsl:stylesheet SYSTEM "ulang://common">
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	
	<xsl:param name="p"/>
	 
	<xsl:template match="/result[@method = 'sitemap']/data[@type = 'list' and @action = 'view']">
<div class="location">
            
            <a class="btn-action loc-right infoblock-show"><i class="small-ico i-info"></i><xsl:text>&help;</xsl:text></a>
        </div>

        <div class="layout">
            <div class="column">
                
                
           
		<style>
			.showin {
			text-align:center;
			}
			thead td {
			font-weight:bold;
			}
			.sitemap_table td{
			padding:0;
			margin:0;
			}
			.sitemap_table tbody  tr:hover{
			background-color:white;
			}
#sitemap{
	margin:10px;
}
			#sitemap input[type='submit']{
			float:right;
			margin:20 200px  ;
			}
			.pagination a{
				margin-right:10px;
			}
			.pagination a.active{
				font-size:11pt;
			}
		</style>
		<script>
			
			$(document).ready(function(){
				$(".checkall").click(function(){
					$("input[name^='sm']").each(function(){
						$(this).attr("checked","checked");
					});
					return false;
				});
				$(".uncheckall").click(function(){
					$("input[name^='sm']").each(function(){
						$(this).removeAttr("checked");
					});
					return false;
				});
			});
		</script>
		<form id="sitemap" action="do/" method="post">
			<input type="hidden" value="{$p}" name="p"/>
			<table cellpadding="0" cellspacing="0" class="sitemap_table" width="100%">
				<thead>
				<tr>
					<td>&address;</td>
					<td class="types">
						&type;
						<xsl:if test="not($param0=0)">
						(<a href="/admin/sitemap_lenetnet/sitemap/">
							&show_all_types;
						</a>)
						</xsl:if>
					</td>
					<td class="showin">&show_in_sitemap;<br/>
				<a class="checkall" href="" title="">&select_all;</a>&nbsp;<a class="uncheckall" href="">&unselect_all;</a></td></tr>
				</thead>
				<tbody>
				<xsl:apply-templates select="page" mode="list-view"/>
				</tbody>
			</table>
			<input type="submit" value="&save;"/>
		</form>
		
		
		<xsl:apply-templates select="document(concat('udata://system/numpages/',./@total,'/',./@limit,'///10000/'))" mode="sitemap_paging"/>
		
 </div>
            <div class="column">
                <div  class="infoblock">
                    <h3><xsl:text>&label-quick-help;</xsl:text></h3>
                    <div class="content" title="{$context-manul-url}">
                    </div>
                    <div class="infoblock-hide"></div>
                </div>
            </div>
        </div>
	</xsl:template>
	
	<xsl:template match='udata' mode="sitemap_paging">
		<div class="pagination">
			<!--<xsl:if test="tobegin_link">
				<a href=".">1</a>
			</xsl:if>-->
			
			<xsl:apply-templates select="items/item" mode="sitemap_paging_item"/>
		
		</div>
	</xsl:template>
	
	<xsl:template match="item" mode="sitemap_paging_item">
		
		<a href="{@link}">
			<xsl:if test="@is-active">
				<xsl:attribute name="class">active</xsl:attribute>
			</xsl:if>
			<xsl:value-of select="."/></a>
		
	</xsl:template>


	<xsl:template match="page" mode="list-view">
		<tr>
			<td>
				<a title="&show_page_in_new_window;" href="{@link}" target="_blank">
					<xsl:value-of select="@link"/>
				</a>
			</td>
			<td>
				<a title="&show_only_this_type;" href="/admin/sitemap_lenetnet/sitemap/{basetype/@id}/">
					<xsl:value-of select="basetype"/>					
				</a>
			</td>
			<td class="showin">
			<xsl:choose>
				<xsl:when test="document(concat('upage://',@id,'.robots_deny'))//error">
					Не добавляется
				</xsl:when>
				<xsl:otherwise>
					<input type="checkbox" name="sm[{@id}]">
			
					<xsl:if test="not(document(concat('upage://',@id,'.robots_deny?show-empty'))//value=1)">
						<xsl:attribute name="checked">checked</xsl:attribute>
					</xsl:if>
					</input>
				</xsl:otherwise>
			</xsl:choose>
				
		</td></tr>
	</xsl:template>
	
	
</xsl:stylesheet>
