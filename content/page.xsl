<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
								xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
								xmlns:xhtml="http://www.w3.org/1999/xhtml">

<xsl:output method="html"/>

<!-- changing the default template to output all unknown tags -->
<xsl:template match="*">
  <xsl:copy-of select="."/>
</xsl:template>

<!-- dump MetaData -->
<xsl:template match="MetaData"/>

<!-- dummy node for output (this is necessary because all media
	objects follow in sequence to the page object, the page contains
	media aliases only (and their own layout information). the dummy
	node wraps the pageobject and the mediaobject tags. -->
<xsl:template match="dummy">
	<xsl:apply-templates/>
</xsl:template>

<!-- PageObject -->
<xsl:param name="mode"/>
<xsl:param name="pg_title"/>
<xsl:param name="pg_id"/>
<xsl:param name="link_params"/>
<xsl:param name="pg_frame"/>
<xsl:param name="webspace_path"/>
<xsl:param name="enlarge_path"/>
<xsl:param name="bib_id" />
<xsl:param name="citation" />
<xsl:template match="PageObject">
	<!-- <xsl:value-of select="@HierId"/> -->
	<xsl:if test="$pg_title != ''">
		<div class="ilc_PageTitle">
		<xsl:value-of select="$pg_title"/>
		</div>
	</xsl:if>
	<xsl:if test="$mode = 'edit'">
		<select size="1" class="ilEditSelect">
			<xsl:attribute name="name">command<xsl:value-of select="@HierId"/></xsl:attribute>
			<option value="insert_par">insert Paragr.</option>
			<option value="insert_tab">insert Table</option>
			<option value="insert_mob">insert Media</option>
			<option value="insert_list">insert List</option>
			<option value="insert_flst">insert File List</option>
		</select>
		<input class="ilEditSubmit" type="submit" value="Go">
			<xsl:attribute name="name">cmd[exec_<xsl:value-of select="@HierId"/>]</xsl:attribute>
		</input>
		<br/>
	</xsl:if>
      <xsl:if test="$citation = 1">
        <xsl:if test="count(//PageTurn) &gt; 0">
          <input type="checkbox" name="pgt_id[0]">
            <xsl:attribute name="value">
              <xsl:call-template name="getFirstPageNumber" />
            </xsl:attribute>
          </input>
          <xsl:call-template name="showCitationSelect">
            <xsl:with-param name="pos" select="0" />
          </xsl:call-template>
          <xsl:text> </xsl:text>
          <span class="ilc_Strong">[Page <xsl:call-template name="getFirstPageNumber"/>]</span>
        </xsl:if>
      </xsl:if>
    <xsl:apply-templates/>
    <!-- Footnote List -->
	<xsl:if test="count(//Footnote) > 0">
		<hr />
		<xsl:for-each select="//Footnote">
			<div class="ilc_Footnote">
			<a>
			<xsl:attribute name="name">fn<xsl:number count="Footnote" level="any"/></xsl:attribute>
			<span class="ilc_Strong">[<xsl:number count="Footnote" level="any"/>] </span>
			</a>
			<xsl:value-of select="."/>
			</div>
		</xsl:for-each>
	</xsl:if>

	<!-- Pageturn List -->
    <xsl:if test="count(//PageTurn) > 0">
      <hr />
      <xsl:variable name="entry_two"><xsl:call-template name="get_bib_item" /></xsl:variable>
      <xsl:for-each select="//PageTurn">
        <xsl:variable name="entry_one"><xsl:value-of select="./BibItemIdentifier/@Entry" /></xsl:variable>
        <xsl:if test="contains($entry_two,$entry_one)">
          <div class="ilc_PageTurn">
            <a>
              <xsl:attribute name="name">pt<xsl:number count="PageTurn" level="multiple"/></xsl:attribute>
              <span class="ilc_Strong">[Pagebreak <xsl:number count="PageTurn" level="multiple"/>] </span>
            </a>
            <xsl:call-template name="searchEdition">
              <xsl:with-param name="Entry">
                <xsl:value-of select="$entry_one" />
              </xsl:with-param>
            </xsl:call-template>
          </div>
        </xsl:if>
      </xsl:for-each>
      <xsl:if test="$citation = 1">
        <xsl:call-template name="showCitationSubmit" />
      </xsl:if>
    </xsl:if>
  </xsl:template>

<!-- SHOW SELECTBOX OF CITATIONS -->
<xsl:template name="showCitationSelect">
  <xsl:param name="pos" />
  <xsl:text> </xsl:text>
  <select class="ilEditSelect">
    <xsl:attribute name="name">ct_option[<xsl:value-of select="$pos" />]</xsl:attribute>
    <option value="single">Paragraph</option>
    <option value="from">From</option>
    <option value="to">To</option>
    <option value="f">F</option>
    <option value="ff">FF</option>
  </select>
</xsl:template>

<!-- SHOW CITATION SUBMIT BUTTON -->
<xsl:template name="showCitationSubmit">
  <br />
  <input class="ilEditSubmit" type="submit" name="cmd[citation]" value="Citate" />
</xsl:template>

<!-- GET BIB ITEM ENTRY BY BIB ID -->
<xsl:template name="get_bib_item">
  <xsl:for-each select="//Bibliography/BibItem">
    <xsl:if test="contains($bib_id,concat(',',position(),','))">
      <xsl:value-of select="./Identifier/@Entry" /><xsl:text>,</xsl:text>
    </xsl:if>
  </xsl:for-each>
</xsl:template>

<!-- GET PREDECESSOR OF FIRST PAGE NUMBER USED FOR CITATION -->
<xsl:template name="getFirstPageNumber">
  <xsl:variable name="entry_two"><xsl:call-template name="get_bib_item" /></xsl:variable>
  <xsl:for-each select="//PageTurn[contains($entry_two,./BibItemIdentifier/@Entry)]">
    <xsl:if test="position() = 1">
      <xsl:choose>
        <xsl:when test="@NumberingType = 'Roman'">
          <xsl:number format="i" value="@Number - 1" />
        </xsl:when>
        <xsl:when test="@NumberingType = 'Arabic'">
          <xsl:number format="1"  value="@Number - 1" />
        </xsl:when>
        <xsl:when test="@NumberingType = 'Alpanumeric'">
          <xsl:number format="A" value="@Number - 1" />
        </xsl:when>
      </xsl:choose>
    </xsl:if>
  </xsl:for-each>
</xsl:template> 

<!-- Sucht zu den Pageturns die Edition und das Jahr raus -->
<xsl:template name="searchEdition">
  <xsl:param name="Entry"/>
  <xsl:variable name="act_number">
    <xsl:value-of select="./@Number" />
  </xsl:variable>
  <xsl:for-each select="//Bibliography/BibItem">
    <xsl:variable name="entry_cmp"><xsl:value-of select="./Identifier/@Entry" /></xsl:variable>
    <xsl:if test="$entry_cmp=$Entry">
      <xsl:text> Page: </xsl:text><xsl:value-of select="$act_number" /><xsl:text>, </xsl:text>
    </xsl:if>
    <xsl:if test="$entry_cmp=$Entry">
      <xsl:value-of select="./Edition/."/><xsl:text>, </xsl:text><xsl:value-of select="./Year/."/>
    </xsl:if>
  </xsl:for-each>
</xsl:template>

<!-- Bibliography-Tag nie ausgeben -->
<xsl:template match="Bibliography"/>

<!-- PageContent -->
<xsl:template match="PageContent">
	<xsl:if test="$mode = 'edit'">
		<div class="il_editarea">
		<xsl:apply-templates/>
		</div>
	</xsl:if>
	<xsl:if test="$mode != 'edit'">
		<xsl:apply-templates/>
	</xsl:if>
</xsl:template>


<!-- Paragraph -->
<xsl:template match="Paragraph">
	<p>
		<xsl:if test="not(@Characteristic)">
		<xsl:attribute name="class">ilc_Standard</xsl:attribute>
		</xsl:if>
		<xsl:if test="@Characteristic">
		<xsl:attribute name="class">ilc_<xsl:value-of select="@Characteristic"/></xsl:attribute>
		</xsl:if>
		<!-- <xsl:value-of select="@HierId"/> -->
		<!-- checkbox -->
		<!--
		<xsl:if test="$mode = 'edit'">
			<input type="checkbox" name="target[]">
				<xsl:attribute name="value"><xsl:value-of select="@HierId"/>
				</xsl:attribute>
			</input>
		</xsl:if> -->
		<!-- content -->
		<xsl:apply-templates/>
		<!-- command selectbox -->
		<xsl:if test="$mode = 'edit'">
			<br />
			<!-- <xsl:value-of select="../@HierId"/> -->
			<input type="checkbox" name="target[]">
				<xsl:attribute name="value"><xsl:value-of select="../@HierId"/>
				</xsl:attribute>
			</input>
			<select size="1" class="ilEditSelect">
				<xsl:attribute name="name">command<xsl:value-of select="../@HierId"/>
				</xsl:attribute>
			<option value="edit">edit</option>
			<option value="insert_par">insert Paragr.</option>
			<option value="insert_tab">insert Table</option>
			<option value="insert_mob">insert Media</option>
			<option value="insert_list">insert List</option>
			<option value="insert_flst">insert File List</option>
			<option value="delete">delete</option>
			<option value="moveAfter">move after</option>
			<option value="moveBefore">move before</option>
			</select>
			<input class="ilEditSubmit" type="submit" value="Go">
				<xsl:attribute name="name">cmd[exec_<xsl:value-of select="../@HierId"/>]</xsl:attribute>
			</input>
		</xsl:if>
	</p>
</xsl:template>

<!-- Emph, Strong, Comment, Quotation -->
<xsl:template match="Emph|Strong|Comment|Quotation">
	<xsl:variable name="Tagname" select="name()"/>
	<span class="ilc_{$Tagname}"><xsl:apply-templates/></span>
</xsl:template>

<!-- Code -->
<xsl:template match="Code">
	<code><xsl:apply-templates/></code>
</xsl:template>

<!-- Footnote (Links) -->
<xsl:template match="Footnote"><a class="ilc_FootnoteLink"><xsl:attribute name="href">#fn<xsl:number count="Footnote" level="any"/></xsl:attribute>[<xsl:number count="Footnote" level="any"/>]
	</a>
</xsl:template>

<!-- PageTurn (Links) -->
<xsl:template match="PageTurn">
	<xsl:variable name="entry_one"><xsl:value-of select="./BibItemIdentifier/@Entry" /></xsl:variable>
	<xsl:variable name="entry_two"><xsl:call-template name="get_bib_item" /></xsl:variable>
	<xsl:if test="contains($entry_two,$entry_one)">
		<xsl:if test="$citation = 1">
			<br />
			<input type="checkbox">
				<xsl:attribute name="name">
				<xsl:text>pgt_id[</xsl:text><xsl:number count="PageTurn" level="multiple" /><xsl:text>]</xsl:text>
				</xsl:attribute>
				<xsl:attribute name="value">
				<xsl:value-of select="./@Number" />
				</xsl:attribute>
			</input>
			<xsl:call-template name="showCitationSelect">
			<xsl:with-param name="pos">
			<xsl:number level="multiple" count="PageTurn" />
			</xsl:with-param>
			</xsl:call-template>
		</xsl:if>
		<a class="ilc_PageTurnLink">
		<xsl:attribute name="href">#pt<xsl:number count="PageTurn" level="any"/></xsl:attribute>[Pagebreak <xsl:number count="PageTurn" level="multiple"/>]</a>
	</xsl:if>
</xsl:template>

<!-- IntLink -->
<xsl:template match="IntLink">
	<!-- all internal links except inline mob vris -->
	<xsl:if test="@Type != 'MediaObject' or @TargetFrame">
		<xsl:variable name="frametype" select="@TargetFrame"/>
		<a class="ilc_IntLink">
			<xsl:variable name="targetframe">
				<xsl:value-of select="//LinkTargets/LinkTarget[@Type=$frametype]/@Frame"/>
			</xsl:variable>
			<xsl:if test="$targetframe != ''">
				<xsl:attribute name="target"><xsl:value-of select="$targetframe"/></xsl:attribute>
			</xsl:if>
			<xsl:variable name="frame">
				<xsl:choose>
					<xsl:when test="$targetframe = ''">
						<xsl:value-of select="$pg_frame"/>
					</xsl:when>
					<xsl:otherwise>
						<xsl:value-of select="$targetframe"/>
					</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<!-- Page Objects -->
			<xsl:if test="@Type = 'PageObject'">
				<xsl:if test="$mode = 'edit'">
					<xsl:attribute name="href">lm_edit.php?cmd=view&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'pg_')"/></xsl:attribute>
				</xsl:if>
				<xsl:if test="$mode = 'preview'">
					<xsl:attribute name="href">lm_edit.php?cmd=preview&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'pg_')"/></xsl:attribute>
				</xsl:if>
				<xsl:if test="$mode = 'presentation'">
					<xsl:attribute name="href">lm_presentation.php?obj_type=<xsl:value-of select="@Type"/>&amp;cmd=layout&amp;frame=<xsl:value-of select="$frame"/>&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'pg_')"/></xsl:attribute>
				</xsl:if>
			</xsl:if>
			<!-- Structure Objects -->
			<xsl:if test="@Type = 'StructureObject'">
				<xsl:if test="$mode = 'edit'">
					<xsl:attribute name="href">lm_edit.php?cmd=view&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'st_')"/></xsl:attribute>
				</xsl:if>
				<xsl:if test="$mode = 'preview'">
					<xsl:attribute name="href">lm_edit.php?cmd=preview&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'st_')"/></xsl:attribute>
				</xsl:if>
				<xsl:if test="$mode = 'presentation'">
					<xsl:attribute name="href">lm_presentation.php?obj_type=<xsl:value-of select="@Type"/>&amp;cmd=layout&amp;frame=<xsl:value-of select="$frame"/>&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'st_')"/></xsl:attribute>
				</xsl:if>
			</xsl:if>
			<!-- Glossary Items -->
			<xsl:if test="@Type = 'GlossaryItem'">
				<xsl:if test="$mode = 'edit' or $mode = 'preview'">
					<xsl:attribute name="href">lm_presentation.php?obj_type=<xsl:value-of select="@Type"/>&amp;cmd=glossary&amp;frame=_new&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'pg_')"/></xsl:attribute>
				</xsl:if>
				<xsl:if test="$mode = 'presentation'">
					<xsl:attribute name="href">lm_presentation.php?obj_type=<xsl:value-of select="@Type"/>&amp;cmd=glossary&amp;frame=<xsl:value-of select="$frame"/>&amp;<xsl:value-of select="$link_params"/>&amp;obj_id=<xsl:value-of select="substring-after(@Target,'pg_')"/></xsl:attribute>
				</xsl:if>
			</xsl:if>
			<!-- Media Objects -->
			<xsl:if test="@Type = 'MediaObject'">
				<xsl:if test="$mode = 'edit' or $mode = 'preview'">
					<xsl:attribute name="href">lm_presentation.php?obj_type=<xsl:value-of select="@Type"/>&amp;cmd=media&amp;frame=_new&amp;<xsl:value-of select="$link_params"/>&amp;mob_id=<xsl:value-of select="substring-after(@Target,'mob_')"/></xsl:attribute>
				</xsl:if>
				<xsl:if test="$mode = 'presentation'">
					<xsl:attribute name="href">lm_presentation.php?obj_type=<xsl:value-of select="@Type"/>&amp;cmd=media&amp;frame=<xsl:value-of select="$frame"/>&amp;<xsl:value-of select="$link_params"/>&amp;mob_id=<xsl:value-of select="substring-after(@Target,'mob_')"/></xsl:attribute>
				</xsl:if>
			</xsl:if>
			<xsl:apply-templates/>
		</a>
	</xsl:if>
	<!-- inline mob vri -->
	<xsl:if test="@Type = 'MediaObject' and not(@TargetFrame)">
		<object>

		<!-- data / Location -->
		<xsl:variable name="cmobid" select="@Target"/>
		<xsl:variable name="curType" select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Location/@Type"/>
		<xsl:if test="$curType = 'LocalFile'">
			<xsl:attribute name="data"><xsl:value-of select="$webspace_path"/>/mobs/mm_<xsl:value-of select="substring-after($cmobid,'mob_')"/>/<xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Location"/></xsl:attribute>
		</xsl:if>
		<xsl:if test="$curType = 'Reference'">
			<xsl:attribute name="data"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Location"/></xsl:attribute>
		</xsl:if>
		<!-- type / Format -->
		<xsl:attribute name="type"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Format"/></xsl:attribute>

		<!-- width and height -->
		<xsl:choose>
			<xsl:when test="@Width != '' or @Height != ''">
				<xsl:attribute name="width"><xsl:value-of select="@Width"/></xsl:attribute>
				<xsl:attribute name="height"><xsl:value-of select="@Height"/></xsl:attribute>
			</xsl:when>
			<xsl:when test="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Layout[1]/@Width != '' or
				//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Layout[1]/@Height != ''">
				<xsl:attribute name="width"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Layout[1]/@Width"/></xsl:attribute>
				<xsl:attribute name="height"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Layout[1]/@Height"/></xsl:attribute>
			</xsl:when>
		</xsl:choose>

		</object>
	</xsl:if>
</xsl:template>


<!-- ExtLink -->
<xsl:template match="ExtLink">
	<a class="ilc_ExtLink" target="_new">
		<xsl:attribute name="href"><xsl:value-of select="@Href"/></xsl:attribute>
		<xsl:apply-templates/>
	</a>
</xsl:template>


<!-- Tables -->
<xsl:template match="Table">
	<!-- <xsl:value-of select="@HierId"/> -->
	<xsl:if test="$mode = 'edit'">
		<!--<input type="checkbox" name="target[]">
			<xsl:attribute name="value"><xsl:value-of select="@HierId"/>
			</xsl:attribute>
		</input> -->
		<br/>
	</xsl:if>

	<table>
	<xsl:attribute name="width"><xsl:value-of select="@Width"/></xsl:attribute>
	<xsl:attribute name="border"><xsl:value-of select="@Border"/></xsl:attribute>
	<xsl:attribute name="cellspacing"><xsl:value-of select="@CellSpacing"/></xsl:attribute>
	<xsl:attribute name="cellpadding"><xsl:value-of select="@CellPadding"/></xsl:attribute>
	<!--<xsl:for-each select="HeaderCaption">
		<caption align="top">
		<xsl:value-of select="."/>
		</caption>
	</xsl:for-each>-->
	<xsl:for-each select="Caption">
		<caption>
		<xsl:attribute name="align"><xsl:value-of select="@Align"/></xsl:attribute>
		<xsl:value-of select="."/>
		</caption>
	</xsl:for-each>
	<xsl:for-each select="TableRow">
		<tr valign="top">
			<xsl:for-each select="TableData">
				<td>
					<xsl:attribute name="class"><xsl:value-of select="@Class"/></xsl:attribute>
					<xsl:attribute name="width"><xsl:value-of select="@Width"/></xsl:attribute>
					<!-- insert commands -->
					<!-- <xsl:value-of select="@HierId"/> -->
					<xsl:if test="$mode = 'edit' or $mode = 'table_edit'">
						<!-- checkbox -->
						<input type="checkbox" name="target[]">
							<xsl:attribute name="value"><xsl:value-of select="@HierId"/>
							</xsl:attribute>
						</input>
						<!-- insert select list -->
						<xsl:if test="$mode = 'edit'">
							<select size="1" class="ilEditSelect">
								<xsl:attribute name="name">command<xsl:value-of select="@HierId"/>
								</xsl:attribute>
								<option value="insert_par">insert Paragr.</option>
								<option value="insert_tab">insert Table</option>
								<option value="insert_mob">insert Media</option>
								<option value="insert_list">insert List</option>
								<option value="insert_flst">insert File List</option>
								<option value="newRowAfter">new Row after</option>
								<option value="newRowBefore">new Row before</option>
								<option value="newColAfter">new Col after</option>
								<option value="newColBefore">new Col before</option>
								<option value="deleteRow">delete Row</option>
								<option value="deleteCol">delete Col</option>
							</select>
							<input class="ilEditSubmit" type="submit" value="Go">
								<xsl:attribute name="name">cmd[exec_<xsl:value-of select="@HierId"/>]</xsl:attribute>
							</input>
							<br/>
						</xsl:if>
					</xsl:if>
					<!-- class and width output for table edit -->
					<xsl:if test="$mode = 'table_edit'">
					<br />
					<b>Class: <xsl:value-of select="@Class"/></b><br />
					<b>Width: <xsl:value-of select="@Width"/></b><br />
					</xsl:if>
					<!-- content -->
					<xsl:apply-templates/>
				</td>
			</xsl:for-each>
		</tr>
	</xsl:for-each>
	</table>
	<!-- command selectbox -->
	<xsl:if test="$mode = 'edit'">
		<!-- <xsl:value-of select="../@HierId"/> -->
		<input type="checkbox" name="target[]">
			<xsl:attribute name="value"><xsl:value-of select="../@HierId"/>
			</xsl:attribute>
		</input>
		<select size="1" class="ilEditSelect">
			<xsl:attribute name="name">command<xsl:value-of select="../@HierId"/>
			</xsl:attribute>
		<option value="edit">edit properties</option>
		<option value="insert_par">insert Paragr.</option>
		<option value="insert_tab">insert Table</option>
		<option value="insert_mob">insert Media</option>
		<option value="insert_list">insert List</option>
		<option value="insert_flst">insert File List</option>
		<option value="delete">delete</option>
		<option value="moveAfter">move after</option>
		<option value="moveBefore">move before</option>
		</select>
		<input class="ilEditSubmit" type="submit" value="Go">
			<xsl:attribute name="name">cmd[exec_<xsl:value-of select="../@HierId"/>]</xsl:attribute>
		</input>
		<br/>
	</xsl:if>
</xsl:template>


<!-- Lists -->
<xsl:template match="List">
	<!-- <xsl:value-of select="..@HierId"/> -->
	<xsl:if test="@Type = 'Ordered'">
		<ol>
		<xsl:choose>
			<xsl:when test="@NumberingType = 'Roman'"><xsl:attribute name="type">I</xsl:attribute></xsl:when>
			<xsl:when test="@NumberingType = 'roman'"><xsl:attribute name="type">i</xsl:attribute></xsl:when>
			<xsl:when test="@NumberingType = 'Alphabetic'"><xsl:attribute name="type">A</xsl:attribute></xsl:when>
			<xsl:when test="@NumberingType = 'alphabetic'"><xsl:attribute name="type">a</xsl:attribute></xsl:when>
		</xsl:choose>
		<xsl:apply-templates/>
		</ol>
	</xsl:if>
	<xsl:if test="@Type = 'Unordered'">
		<ul>
		<xsl:apply-templates/>
		</ul>
	</xsl:if>
	<!-- command selectbox -->
	<xsl:if test="$mode = 'edit'">
		<!-- <xsl:value-of select="../@HierId"/> -->
		<input type="checkbox" name="target[]">
			<xsl:attribute name="value"><xsl:value-of select="../@HierId"/>
			</xsl:attribute>
		</input>
		<select size="1" class="ilEditSelect">
			<xsl:attribute name="name">command<xsl:value-of select="../@HierId"/>
			</xsl:attribute>
		<option value="edit">edit properties</option>
		<option value="insert_par">insert Paragr.</option>
		<option value="insert_tab">insert Table</option>
		<option value="insert_mob">insert Media</option>
		<option value="insert_list">insert List</option>
		<option value="insert_flst">insert File List</option>
		<option value="delete">delete</option>
		<option value="moveAfter">move after</option>
		<option value="moveBefore">move before</option>
		</select>
		<input class="ilEditSubmit" type="submit" value="Go">
			<xsl:attribute name="name">cmd[exec_<xsl:value-of select="../@HierId"/>]</xsl:attribute>
		</input>
		<br/>
	</xsl:if>
</xsl:template>

<!-- List Item -->
<xsl:template match="ListItem">
	<li>
	<!-- insert commands -->
	<!-- <xsl:value-of select="@HierId"/> -->
	<xsl:if test="$mode = 'edit'">
		<!-- checkbox -->
		<input type="checkbox" name="target[]">
			<xsl:attribute name="value"><xsl:value-of select="@HierId"/>
			</xsl:attribute>
		</input>
		<select size="1" class="ilEditSelect">
			<xsl:attribute name="name">command<xsl:value-of select="@HierId"/>
			</xsl:attribute>
			<option value="insert_par">insert Paragr.</option>
			<option value="insert_tab">insert Table</option>
			<option value="insert_mob">insert Media</option>
			<option value="insert_list">insert List</option>
			<option value="insert_flst">insert File List</option>
			<option value="newItemAfter">new Item after</option>
			<option value="newItemBefore">new Item before</option>
			<option value="deleteItem">delete Item</option>
		</select>
		<input class="ilEditSubmit" type="submit" value="Go">
			<xsl:attribute name="name">cmd[exec_<xsl:value-of select="@HierId"/>]</xsl:attribute>
		</input>
		<br/>
	</xsl:if>

	<xsl:apply-templates/>
	</li>
</xsl:template>

<!-- FileList -->
<xsl:template match="FileList">
	<table class="ilc_FileList">
		<th class="ilc_FileList">
		<xsl:value-of select="./Title"/>
		</th>
		<xsl:apply-templates/>
	</table>
	<!-- command selectbox -->
	<xsl:if test="$mode = 'edit'">
		<!-- <xsl:value-of select="../@HierId"/> -->
		<input type="checkbox" name="target[]">
			<xsl:attribute name="value"><xsl:value-of select="../@HierId"/>
			</xsl:attribute>
		</input>
		<select size="1" class="ilEditSelect">
			<xsl:attribute name="name">command<xsl:value-of select="../@HierId"/>
			</xsl:attribute>
		<option value="edit">edit properties</option>
		<option value="insert_par">insert Paragr.</option>
		<option value="insert_tab">insert Table</option>
		<option value="insert_mob">insert Media</option>
		<option value="insert_list">insert List</option>
		<option value="insert_flst">insert File List</option>
		<option value="delete">delete</option>
		<option value="moveAfter">move after</option>
		<option value="moveBefore">move before</option>
		</select>
		<input class="ilEditSubmit" type="submit" value="Go">
			<xsl:attribute name="name">cmd[exec_<xsl:value-of select="../@HierId"/>]</xsl:attribute>
		</input>
		<br/>
	</xsl:if>
</xsl:template>

<!-- FileItem -->
<xsl:template match="FileItem">
	<tr class="ilc_FileItem">
		<td class="ilc_FileItem">
		<a href="lm_presentation.php?cmd=downloadFile&amp;file_id=">
			<xsl:attribute name="href">lm_presentation.php?cmd=downloadFile&amp;file_id=<xsl:value-of select="./Identifier/@Entry"/></xsl:attribute>
			<xsl:value-of select="./Location"/>
		</a>
		<!-- <xsl:value-of select="@HierId"/> -->
		<xsl:if test="$mode = 'edit'">
			<!-- checkbox -->
			<br />
			<select size="1" class="ilEditSelect">
				<xsl:attribute name="name">command<xsl:value-of select="@HierId"/>
				</xsl:attribute>
				<option value="newItemAfterForm">new Item after</option>
				<option value="newItemBeforeForm">new Item before</option>
				<option value="deleteItem">delete Item</option>
			</select>
			<input class="ilEditSubmit" type="submit" value="Go">
				<xsl:attribute name="name">cmd[exec_<xsl:value-of select="@HierId"/>]</xsl:attribute>
			</input>
			<br/>
		</xsl:if>

		</td>
	</tr>
</xsl:template>

<!-- MediaAlias -->
<xsl:template match="MediaAlias">

	<!-- Alignment Part 1 (Left, Center, Right)-->
	<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'Left'
		and $mode != 'fullscreen' and $mode != 'media'">
		<div align="left" style="clear:both;">
		<xsl:call-template name="MOBTable"/>
		</div>
	</xsl:if>
	<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'Center'
		or $mode = 'fullscreen' or $mode = 'media'">
		<div align="center" style="clear:both;">
		<xsl:call-template name="MOBTable"/>
		</div>
	</xsl:if>
	<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'Right'
		and $mode != 'fullscreen' and $mode != 'media'">
		<div align="right" style="clear:both;">
		<xsl:call-template name="MOBTable"/>
		</div>
	</xsl:if>
	<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'RightFloat'
		and $mode != 'fullscreen' and $mode != 'media'">
		<xsl:call-template name="MOBTable"/>
	</xsl:if>
	<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'LeftFloat'
		and $mode != 'fullscreen' and $mode != 'media'">
		<xsl:call-template name="MOBTable"/>
	</xsl:if>
	<xsl:if test="count(../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign) = 0
		and $mode != 'fullscreen' and $mode != 'media'">
		<div align="left" style="clear:both;">
		<xsl:call-template name="MOBTable"/>
		</div>
	</xsl:if>
</xsl:template>

<!-- MOBTable: display multimedia objects within a layout table> -->
<xsl:template name="MOBTable">
	<xsl:variable name="cmobid" select="@OriginId"/>

	<table class="ilc_Media" width="1">
		<!-- Alignment Part 2 (LeftFloat, RightFloat) -->
		<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'LeftFloat'
			and $mode != 'fullscreen' and $mode != 'media'">
			<xsl:attribute name="style">float:left; clear:both; margin-left: 0px;</xsl:attribute>
		</xsl:if>
		<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'RightFloat'
			and $mode != 'fullscreen' and $mode != 'media'">
			<xsl:attribute name="style">float:right; clear:both; margin-right: 0px;</xsl:attribute>
		</xsl:if>

		<!-- make object fit to left/right border -->
		<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'Left'
			and $mode != 'fullscreen' and $mode != 'media'">
			<xsl:attribute name="style">margin-left: 0px;</xsl:attribute>
		</xsl:if>
		<xsl:if test="../MediaAliasItem[@Purpose='Standard']/Layout[1]/@HorizontalAlign = 'Right'
			and $mode != 'fullscreen' and $mode != 'media'">
			<xsl:attribute name="style">margin-right: 0px;</xsl:attribute>
		</xsl:if>

		<!-- determine purpose -->
		<xsl:variable name="curPurpose"><xsl:choose>
			<xsl:when test="$mode = 'fullscreen'">Fullscreen</xsl:when>
			<xsl:otherwise>Standard</xsl:otherwise>
		</xsl:choose></xsl:variable>

		<!-- build object tag -->
		<tr><td class="ilc_Mob"><object>
			<xsl:for-each select="../MediaAliasItem[@Purpose = $curPurpose]">

				<!-- data / Location -->
				<xsl:variable name="curItemNr"><xsl:number count="MediaItem" from="MediaAlias"/></xsl:variable>
				<xsl:choose>
					<xsl:when test="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = $curPurpose]/Location != ''">
						<xsl:variable name="curType" select="//MediaObject[@Id=$cmobid]//MediaItem[@Purpose = $curPurpose]/Location/@Type"/>
						<xsl:if test="$curType = 'LocalFile'">
							<xsl:attribute name="data"><xsl:value-of select="$webspace_path"/>/mobs/mm_<xsl:value-of select="substring-after($cmobid,'mob_')"/>/<xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = $curPurpose]/Location"/></xsl:attribute>
						</xsl:if>
						<xsl:if test="$curType = 'Reference'">
							<xsl:attribute name="data"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = $curPurpose]/Location"/></xsl:attribute>
						</xsl:if>
						<!-- type / Format -->
						<xsl:attribute name="type"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = $curPurpose]/Format"/></xsl:attribute>
					</xsl:when>
					<xsl:otherwise>
						<xsl:variable name="curType" select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Location/@Type"/>
						<xsl:if test="$curType = 'LocalFile'">
							<xsl:attribute name="data"><xsl:value-of select="$webspace_path"/>/mobs/mm_<xsl:value-of select="substring-after($cmobid,'mob_')"/>/<xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Location"/></xsl:attribute>
						</xsl:if>
						<xsl:if test="$curType = 'Reference'">
							<xsl:attribute name="data"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Location"/></xsl:attribute>
						</xsl:if>
						<!-- type / Format -->
						<xsl:attribute name="type"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose = 'Standard']/Format"/></xsl:attribute>
					</xsl:otherwise>
				</xsl:choose>

				<!-- width and height -->
				<xsl:choose>
					<xsl:when test="../MediaAliasItem[@Purpose=$curPurpose]/Layout[1]/@Width != '' or ../MediaAliasItem[@Purpose=$curPurpose]/Layout[1]/@Height != ''">
						<xsl:attribute name="width"><xsl:value-of select="../MediaAliasItem[@Purpose=$curPurpose]/Layout[1]/@Width"/></xsl:attribute>
						<xsl:attribute name="height"><xsl:value-of select="../MediaAliasItem[@Purpose=$curPurpose]/Layout[1]/@Height"/></xsl:attribute>
					</xsl:when>
					<xsl:when test="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose=$curPurpose]/Layout[1]/@Width != '' or
						//MediaObject[@Id=$cmobid]/MediaItem[@Purpose=$curPurpose]/Layout[1]/@Height != ''">
						<xsl:attribute name="width"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose=$curPurpose]/Layout[1]/@Width"/></xsl:attribute>
						<xsl:attribute name="height"><xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose=$curPurpose]/Layout[1]/@Height"/></xsl:attribute>
					</xsl:when>
				</xsl:choose>

				<!-- parameter -->
				<xsl:for-each select="../MediaAliasItem[@Purpose = $curPurpose]/Parameter">
					<param>
					<xsl:attribute name="name"><xsl:value-of select="@Name"/></xsl:attribute>
					<xsl:attribute name="value"><xsl:value-of select="@Value"/></xsl:attribute>
					</param>
				</xsl:for-each>

			</xsl:for-each>
		</object></td></tr>

		<!-- mob caption -->
		<xsl:choose>			<!-- derive -->
			<xsl:when test="count(../MediaAliasItem[@Purpose=$curPurpose]/Caption[1]) != 0">
				<tr><td class="ilc_MediaCaption">
				<xsl:call-template name="FullscreenLink">
					<xsl:with-param name="cmobid" select="$cmobid"/>
				</xsl:call-template>
				<xsl:value-of select="../MediaAliasItem[@Purpose=$curPurpose]/Caption[1]"/>
				</td></tr>
			</xsl:when>
			<xsl:when test="count(//MediaObject[@Id=$cmobid]/MediaItem[@Purpose=$curPurpose]/Caption[1]) != 0">
				<tr><td class="ilc_MediaCaption">
				<xsl:call-template name="FullscreenLink">
					<xsl:with-param name="cmobid" select="$cmobid"/>
				</xsl:call-template>
				<xsl:value-of select="//MediaObject[@Id=$cmobid]/MediaItem[@Purpose=$curPurpose]/Caption[1]"/>
				</td></tr>
			</xsl:when>
			<xsl:otherwise>
				<xsl:if test="count(../MediaAliasItem[@Purpose='Fullscreen']) = 1">
					<tr><td class="ilc_MediaCaption">
					<xsl:call-template name="FullscreenLink">
						<xsl:with-param name="cmobid" select="$cmobid"/>
					</xsl:call-template>
					</td></tr>
				</xsl:if>
			</xsl:otherwise>
		</xsl:choose>

		<!-- command selectbox -->
		<xsl:if test="$mode = 'edit'">
			<tr><td>
				<!-- <xsl:value-of select="../../@HierId"/> -->
				<input type="checkbox" name="target[]">
					<xsl:attribute name="value"><xsl:value-of select="../../@HierId"/>
					</xsl:attribute>
				</input>
				<select size="1" class="ilEditSelect">
					<xsl:attribute name="name">command<xsl:value-of select="../../@HierId"/>
					</xsl:attribute>
				<option value="editAlias">edit properties</option>
				<option value="insert_par">insert Paragr.</option>
				<option value="insert_tab">insert Table</option>
				<option value="insert_mob">insert Media</option>
				<option value="insert_list">insert List</option>
				<option value="insert_flst">insert File List</option>
				<option value="delete">delete</option>
				<option value="moveAfter">move after</option>
				<option value="moveBefore">move before</option>
				<option value="leftAlign">align: left</option>
				<option value="rightAlign">align: right</option>
				<option value="centerAlign">align: center</option>
				<option value="leftFloatAlign">align: left float</option>
				<option value="rightFloatAlign">align: right float</option>
				<option value="copyToClipboard">copy to clipboard</option>
				</select>
				<input class="ilEditSubmit" type="submit" value="Go">
					<xsl:attribute name="name">cmd[exec_<xsl:value-of select="../../@HierId"/>]</xsl:attribute>
				</input>
			</td></tr>
		</xsl:if>

	</table>
</xsl:template>

<!-- Fullscreen Link -->
<xsl:template name="FullscreenLink">
	<xsl:param name="cmobid"/>
	<xsl:if test="count(../MediaAliasItem[@Purpose='Fullscreen']) = 1 and $mode != 'fullscreen'">
		<a target="_new">
		<xsl:attribute name="href">lm_presentation.php?cmd=fullscreen&amp;mob_id=<xsl:value-of select="substring-after($cmobid,'mob_')"/>&amp;<xsl:value-of select="$link_params"/>&amp;pg_id=<xsl:value-of select="$pg_id"/></xsl:attribute>
		<img border="0" align="right">
		<xsl:attribute name="src"><xsl:value-of select="$enlarge_path"/></xsl:attribute>
		</img>
		</a>
	</xsl:if>
</xsl:template>


<!-- MediaObject -->
<xsl:template match="MediaObject">
	<xsl:apply-templates select="MediaAlias"/>
</xsl:template>


<!--
<xsl:template match="Item/Paragraph">
	<xsl:apply-templates/>
</xsl:template>

<xsl:template match="Definition/Paragraph">
	<xsl:apply-templates/>
</xsl:template>

<xsl:template match="Text">
	<xsl:apply-templates/>
</xsl:template>-->

</xsl:stylesheet>
