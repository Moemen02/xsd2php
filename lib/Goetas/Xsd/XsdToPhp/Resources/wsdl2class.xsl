<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0"
				xmlns:env="goetas:envelope"	
				xmlns:exslt="http://exslt.org/common"
				xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
				xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"
				xmlns:php="http://php.net/xsl"
				xmlns:xs2php="http://www.mercuriosistemi.com/mercurio/php/schema2php"
				xmlns:xsd="http://www.w3.org/2001/XMLSchema">
	<xsl:output omit-xml-declaration="yes" method="xml"/>


	<xsl:template match="/">
		<all>
			<xsl:apply-templates select="env:env/wsdl:definitions/wsdl:portType"/>
		</all>
	</xsl:template>
	
	<xsl:template  match="wsdl:portType">
		<class>
			<xsl:attribute name="name">
				<xsl:value-of select="@name"/>
			</xsl:attribute>
			<xsl:attribute name="ns">
			     <xsl:text>wsdl:portType#</xsl:text>
				<xsl:value-of select="ancestor::wsdl:definitions/@targetNamespace"/>
			</xsl:attribute>
			<xsl:apply-templates  select="wsdl:operation"/>
		</class>
	</xsl:template>
	
	<xsl:template match="wsdl:operation" >
	
	   <xsl:variable name="input_name" select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', wsdl:input, string(wsdl:input/@message),'name')"/>
	   <xsl:variable name="input_ns" select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', wsdl:input, string(wsdl:input/@message),'ns')"/>
	   
	   <xsl:variable name="output_name" select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', wsdl:output, string(wsdl:output/@message),'name')"/>
       <xsl:variable name="output_ns" select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart', wsdl:output, string(wsdl:output/@message),'ns')"/>

	   
	
		<method name="{@name}">
		  <xsl:if test="wsdl:documentation">
		      <doc><xsl:value-of select="wsdl:documentation" /></doc>
		  </xsl:if>
		      <params>
		          <xsl:apply-templates select="//wsdl:definitions[@targetNamespace=$input_ns]/wsdl:message[@name=$input_name]/wsdl:part" />
		      </params>
		      <return>
		          <xsl:apply-templates select="//wsdl:definitions[@targetNamespace=$output_ns]/wsdl:message[@name=$output_name]/wsdl:part" />
		      </return>
		</method>
	</xsl:template>
	
	<xsl:template match="wsdl:part" >
		<param name="{@name}">
		     
	      <xsl:if test="@type">
	          <xsl:attribute name="type-name">
	              <xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart',., string(@type),'name')"/>
	          </xsl:attribute>
	           <xsl:attribute name="type-ns">
                     <xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart',., string(@type),'ns')"/>
                 </xsl:attribute>
	        </xsl:if>
	        
	        <xsl:if test="@element">
                 <xsl:attribute name="element-name">
                     <xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart',., string(@element),'name')"/>
                 </xsl:attribute>
                  <xsl:attribute name="element-ns">
                     <xsl:value-of select="php:function('Goetas\Xsd\XsdToPhp\Xsd2PhpConverter::splitPart',., string(@element),'ns')"/>
                 </xsl:attribute>
             </xsl:if>
		</param>
	</xsl:template>
		

</xsl:stylesheet>
