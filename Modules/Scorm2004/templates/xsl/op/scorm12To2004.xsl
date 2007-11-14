<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
    xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
    xmlns:cpin="http://www.imsproject.org/xsd/imscp_rootv1p1p2"
    xmlns:imscp="http://www.imsglobal.org/xsd/imscp_v1p1"
    xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3"
    xmlns:adlcpin="http://www.adlnet.org/xsd/adlcp_rootv1p2"
    xmlns:imsss="http://www.imsglobal.org/xsd/imsss"
    xmlns:imsmd="http://www.imsglobal.org/xsd/imsmd_rootv1p2p1"
    exclude-result-prefixes="cpin adlcpin imsmd">
    
    <xsl:import href="SCORM12-SCORM2004-Metadata.xsl"/>
    
    <xsl:output encoding="us-ascii" method="xml" indent="yes"/>
    <!-- General Identity Transformation Templates -->
    <!-- Matches IMSCP nodes that are not covered by anything else -->
    <xsl:template match="cpin:*">
        <xsl:call-template name="namespaceTransformer">
            <xsl:with-param name="oldNamespace"
                select="'http://www.imsproject.org/xsd/imscp_rootv1p1p2'"/>
            <xsl:with-param name="newNamespace" select="'http://www.imsglobal.org/xsd/imscp_v1p1'"/>
            <xsl:with-param name="name" select="local-name()"/>
        </xsl:call-template>
    </xsl:template>
    <!-- Matches ADLCP nodes that are not covered by anything else -->
    <xsl:template match="adlcpin:*">
        <xsl:call-template name="namespaceTransformer">
            <xsl:with-param name="oldNamespace" select="'http://www.adlnet.org/xsd/adlcp_rootv1p2'"/>
            <xsl:with-param name="newNamespace" select="'http://www.adlnet.org/xsd/adlcp_v1p3'"/>
            <xsl:with-param name="name" select="local-name()"/>
        </xsl:call-template>
    </xsl:template>
    <!-- Matches all attributes, switching namespaces appropriately -->
    <xsl:template match="@*">
        <xsl:choose>
            <xsl:when test="namespace-uri() = 'http://www.adlnet.org/xsd/adlcp_rootv1p2'">
                <xsl:attribute name="{local-name()}"
                    namespace="http://www.adlnet.org/xsd/adlcp_v1p3">
                    <xsl:value-of select="."/>
                </xsl:attribute>
            </xsl:when>
            <xsl:when test="namespace-uri() = 'http://www.imsproject.org/xsd/imscp_rootv1p1p2'">
                <xsl:attribute name="{local-name()}"
                    namespace="http://www.imsglobal.org/xsd/imscp_v1p1">
                    <xsl:value-of select="."/>
                </xsl:attribute>
            </xsl:when>
            <xsl:otherwise>
                <xsl:attribute name="{local-name()}">
                    <xsl:value-of select="."/>
                </xsl:attribute>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- This template simply copies comments from the source document to the target document. -->
    <xsl:template match="comment()" name="comment">
        <xsl:comment>
            <xsl:value-of select="."/>
        </xsl:comment>
    </xsl:template>
    <xsl:template match="cpin:resources">
        <xsl:element name="resources">
            <xsl:for-each select="cpin:resource">
                <xsl:element name="resource">
                    <xsl:attribute name="adlcp:scormType">
                        <xsl:value-of select="@adlcpin:scormtype"/>
                    </xsl:attribute>
                    <xsl:attribute name="identifier">
                        <xsl:value-of select="@identifier"/>
                    </xsl:attribute>
                    <xsl:attribute name="type">
                        <xsl:value-of select="@type"/>
                    </xsl:attribute>
                    <xsl:if test="@xml:base != ''">
                        <xsl:attribute name="xml:base">
                            <xsl:value-of select="@xml:base"/>
                        </xsl:attribute>
                    </xsl:if>
                    <xsl:if test="@adlcpin:scormtype = 'sco'">
                        <xsl:attribute name="href">
                            <xsl:text>GenericRunTimeWrapper1.0_aadlc/GenericRunTimeWrapper.htm?scourl=</xsl:text>
                            <xsl:value-of select="@href"/>
                        </xsl:attribute>
                    </xsl:if>
                    <xsl:if test="@adlcpin:scormtype != 'sco' and @href!=''">
                        <xsl:attribute name="href">
                            <xsl:value-of select="@href"/>
                        </xsl:attribute>
                    </xsl:if>
                    <xsl:apply-templates/>
                </xsl:element>
            </xsl:for-each>
            <resource identifier="GenericRunTimeWrapper1.0_aadlc" type="webcontent"
                adlcp:scormType="sco"
                href="GenericRunTimeWrapper1.0_aadlc/GenericRunTimeWrapper.htm">
                <file href="GenericRunTimeWrapper1.0_aadlc/GenericRunTimeWrapper.htm"/>
                <file href="GenericRunTimeWrapper1.0_aadlc/SCOPlayerWrapper.js"/>
            </resource>
        </xsl:element>
    </xsl:template>
    <!-- This template removes the prerequisites from the source. -->
    <xsl:template match="adlcpin:prerequisites">
        <xsl:comment>The following prerequisites have been removed: </xsl:comment>
        <xsl:comment>
            <xsl:value-of select="."/>
        </xsl:comment>
        <xsl:comment>A sequencing rule should be written to replace them.</xsl:comment>
    </xsl:template>
    <!-- The following templates change the name of their elements -->
    <xsl:template match="adlcpin:timelimitaction">
        <adlcp:timeLimitAction>
            <xsl:value-of select="."/>
        </adlcp:timeLimitAction>
    </xsl:template>
    <xsl:template match="adlcpin:datafromlms">
        <adlcp:dataFromLMS>
            <xsl:value-of select="."/>
        </adlcp:dataFromLMS>
    </xsl:template>
    
<!--    <xsl:template match="cpin:manifest">
        <manifest identifier="{@identifier}" version="1.1"
            xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
            xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation="http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://www.adlnet.org/xsd/adlcp_v1p3 adlcp_v1p3.xsd http://ltsc.ieee.org/xsd/LOM LOM_SCHEMA_FOR_VALIDATION">
            <metadata>
                <schema>ADL SCORM</schema>
                <schemaversion>CAM 1.3</schemaversion>
                <xsl:if test="cpin:metadata">
                    <xsl:apply-templates select="cpin:metadata/*"/>
                </xsl:if>
            </metadata>
            <xsl:apply-templates select="*"/>
        </manifest>
    </xsl:template>-->
    
    <!-- The following was added by Roy by commenting out the above -->
    <xsl:template match="cpin:manifest">
        <manifest identifier="{@identifier}" version="1.1"
            xmlns="http://www.imsglobal.org/xsd/imscp_v1p1"
            xmlns:adlcp="http://www.adlnet.org/xsd/adlcp_v1p3"
            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
            xsi:schemaLocation=" http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://ltsc.ieee.org/xsd/LOM
            lom.xsd http://www.adlnet.org/xsd/adlcp_v1p3 adlcp_v1p3.xsd
            http://www.imsglobal.org/xsd/imsss imsss_v1p0.xsd http://www.adlnet.org/xsd/adlseq_v1p3
            adlseq_v1p3.xsd http://www.adlnet.org/xsd/adlnav_v1p3 adlnav_v1p3.xsd">
            <metadata>
                <schema>ADL SCORM</schema>
                <schemaversion>CAM 1.3</schemaversion>
                <xsl:if test="cpin:metadata">
                    <xsl:apply-templates select="cpin:metadata/*"/>
                </xsl:if>
            </metadata>
            <xsl:apply-templates select="*"/>
        </manifest>
    </xsl:template>
    
    
    <xsl:template match="cpin:organizations">
        <organizations default="{@default}">
            <xsl:apply-templates select="*"/>
        </organizations>
    </xsl:template>
    <xsl:template match="cpin:item">
        <xsl:element name="item" namespace="http://www.imsglobal.org/xsd/imscp_v1p1">    
            <xsl:apply-templates select="@*"/>
            <xsl:variable name="masteryScore">
                <xsl:if test="adlcpin:masteryscore">
                    <xsl:value-of select="adlcpin:masteryscore"/>
                </xsl:if>
            </xsl:variable>
            <xsl:variable name="maxTime">
                <xsl:if test="adlcpin:maxtimeallowed">
                    <xsl:value-of select="adlcpin:maxtimeallowed"/>
                </xsl:if>
            </xsl:variable>
            <xsl:apply-templates/>
            <xsl:if test="$maxTime != '' or $masteryScore != ''">
                <imsss:sequencing>
                    <xsl:if test="$maxTime != ''">
                        <imsss:limitConditions>
                            <xsl:attribute name="attemptAbsoluteDurationLimit">
                                <xsl:value-of select="$maxTime"/>
                            </xsl:attribute>
                        </imsss:limitConditions>
                    </xsl:if>
                    <xsl:if test="$masteryScore != ''">
                        <imsss:objectives>
                            <imsss:primaryObjective>
                                <imsss:minNormalizedMeasure>
                                    <xsl:value-of select="$masteryScore"/>
                                </imsss:minNormalizedMeasure>
                            </imsss:primaryObjective>
                        </imsss:objectives>
                    </xsl:if>
                </imsss:sequencing>
            </xsl:if>
        </xsl:element>
    </xsl:template>
    <!-- The following templates suppress items that do not appear in the result. -->
    <xsl:template match="cpin:schema"/>
    <xsl:template match="cpin:schemaversion"/>
    <xsl:template match="adlcpin:maxtimeallowed"/>
    <xsl:template match="adlcpin:masteryscore"/>
    <xsl:template match="cpin:metadata">
        <xsl:choose>
            <xsl:when test="../cpin:manifest"/>
            <xsl:when test="parent::cpin:manifest"/>
            <xsl:when test="not(child::node())"/>
            <xsl:otherwise>
                <metadata>
                    <xsl:apply-templates select="*"/>
                </metadata>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
    <!-- delete this and include LRM-LOM.xsl for metadata transformation -->
    <!-- <xsl:template match="imsmd:lom">
        <xsl:copy-of select="."/>
    </xsl:template>
    -->
    <xsl:template name="namespaceTransformer">
        <xsl:param name="oldNamespace"/>
        <xsl:param name="newNamespace"/>
        <xsl:param name="elementName"/>
        <xsl:element name="{local-name()}" namespace="{$newNamespace}">
            <xsl:apply-templates select="@*"/>
            <xsl:for-each select="text()">
                <xsl:value-of select="."/>
            </xsl:for-each>
            <xsl:apply-templates select="*"/>
            <xsl:apply-templates select="comment()"/>
        </xsl:element>
    </xsl:template>
    <!--This part for doing Metadata conversion to LOM using CMU XSLT sheet: SCORM12-SCORM2004-Metadata.xsl   Roy-->
    <xsl:template match="metadata">
        <xsl:apply-imports/>
    </xsl:template>
  
    <!-- The following for fixing the schema location attriubute in the output document  Roy-->
    <!--<xsl:template match="@schemaLocation" xmlns="http://www.w3.org/2001/XMLSchema-instance">
        <xsl:attribute name="schemaLocation" xmlns="http://www.w3.org/2001/XMLSchema-instance">
            http://www.imsglobal.org/xsd/imscp_v1p1 imscp_v1p1.xsd http://ltsc.ieee.org/xsd/LOM
            lom.xsd http://www.adlnet.org/xsd/adlcp_v1p3 adlcp_v1p3.xsd
            http://www.imsglobal.org/xsd/imsss imsss_v1p0.xsd http://www.adlnet.org/xsd/adlseq_v1p3
            adlseq_v1p3.xsd http://www.adlnet.org/xsd/adlnav_v1p3 adlnav_v1p3.xsd</xsl:attribute>
    </xsl:template>-->
    
</xsl:stylesheet>
