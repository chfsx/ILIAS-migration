/*
        +-----------------------------------------------------------------------------+
        | ILIAS open source                                                           |                                                    
        +-----------------------------------------------------------------------------+
        | Copyright (c) 1998-2001 ILIAS open source, University of Cologne            |
        |                                                                             |
        | This program is free software; you can redistribute it and/or               |
        | modify it under the terms of the GNU General Public License                 |
        | as published by the Free Software Foundation; either version 2              |
        | of the License, or (at your option) any later version.                      |
        |                                                                             |
        | This program is distributed in the hope that it will be useful,             |
        | but WITHOUT ANY WARRANTY; without even the implied warranty of              |
        | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the               |
        | GNU General Public License for more details.                                |
        |                                                                             |
        | You should have received a copy of the GNU General Public License           |
        | along with this program; if not, write to the Free Software                 |
        | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307, USA. |
        +-----------------------------------------------------------------------------+
*/

package de.ilias.services.object;

import java.io.File;
import java.io.IOException;
import java.util.Vector;

import javax.xml.parsers.DocumentBuilder;
import javax.xml.parsers.DocumentBuilderFactory;
import javax.xml.parsers.ParserConfigurationException;

import org.apache.log4j.Logger;
import org.jdom.Element;
import org.xml.sax.SAXException;

import de.ilias.services.settings.ClientSettings;
import de.ilias.services.settings.ConfigurationException;
import de.ilias.services.settings.LocalSettings;

/**
 * Parser for  Lucene object definitions.
 *
 * @author Stefan Meyer <smeyer.ilias@gmx.de>
 * @version $Id$
 */
public class ObjectDefinitionParser {

	protected Logger logger = Logger.getLogger(ObjectDefinition.class);
	private Vector<File> objectPropertyFiles = new Vector<File>();
	private ClientSettings settings;
	private ObjectDefinitions definitions;
	
	
	/**
	 * @throws ConfigurationException 
	 * 
	 */
	public ObjectDefinitionParser() throws ConfigurationException {

		settings = ClientSettings.getInstance(LocalSettings.getClientKey());
		definitions = ObjectDefinitions.getInstance(settings.getAbsolutePath());
	}

	/**
	 * @param objectPropertyFiles
	 * @throws ConfigurationException 
	 */
	public ObjectDefinitionParser(Vector<File> objectPropertyFiles) throws ConfigurationException {

		this();
		this.objectPropertyFiles = objectPropertyFiles;
		
	}
	
	/**
	 * 
	 * @return
	 * @throws ObjectDefinitionException
	 */
	public boolean parse() throws ObjectDefinitionException {
		
		logger.debug("Start parsing object definitions"); 
		for(int i = 0; i < objectPropertyFiles.size(); i++) {
			
			logger.debug("File nr. " + i);
			parseFile(objectPropertyFiles.get(i));
		}
		return true;
	}

	/**
	 * @param file
	 * @throws ObjectDefinitionException 
	 */
	private void parseFile(File file) throws ObjectDefinitionException {

		try {
			
			DocumentBuilderFactory builderFactory = DocumentBuilderFactory.newInstance();
			builderFactory.setNamespaceAware(true);
			builderFactory.setXIncludeAware(true);
			builderFactory.setIgnoringElementContentWhitespace(true);
			
			DocumentBuilder builder = builderFactory.newDocumentBuilder();
			org.w3c.dom.Document document = builder.parse(file);
			
			// JDOM does not understand x:include but has a more comfortable API.
			org.jdom.Document jdocument = convertToJDOM(document);
			
			definitions.addDefinition(parseObjectDefinition(jdocument));
			
			logger.debug("Start logging");
			//logger.debug(definitions.toString());
			
			
		} 
		catch (IOException e) {
			logger.error("Cannot handle file: " + file.getAbsolutePath());
			throw new ObjectDefinitionException(e);
		}
		catch (ParserConfigurationException e) {
			e.printStackTrace();
		} 
		catch (SAXException e) {
			e.printStackTrace();
		} 
		catch (ClassCastException e) {
			e.printStackTrace();
		}
		catch (Exception e) {
			e.printStackTrace();
		}
	}

	/**
	 * @param document
	 * @return
	 */
	private org.jdom.Document convertToJDOM(org.w3c.dom.Document document) {

		org.jdom.input.DOMBuilder builder = new org.jdom.input.DOMBuilder();
		return builder.build(document);
	}

	/**
	 * @param jdocument
	 * @return
	 * @throws ObjectDefinitionException 
	 */
	private ObjectDefinition parseObjectDefinition(org.jdom.Document jdocument) throws ObjectDefinitionException {

		ObjectDefinition definition;
		
		org.jdom.Element root = jdocument.getRootElement();
		
		if(!root.getName().equals("ObjectDefinition")) {
			throw new ObjectDefinitionException("Cannot find root element 'ObjectDefinition'");
		}
		
		definition = new ObjectDefinition(root.getAttributeValue("type"));
		
		// parse documents
		for(Object element : root.getChildren()) {
			
			definition.addDocumentDefinition(parseDocument((Element) element));
		}

		return definition;
	}

	/**
	 * @param document
	 * @return
	 * @throws ObjectDefinitionException 
	 */
	private DocumentDefinition parseDocument(Element element) throws ObjectDefinitionException {

		if(!element.getName().equals("Document")) {
			throw new ObjectDefinitionException("Cannot find element 'Document'");
		}
		
		DocumentDefinition definition = new DocumentDefinition(element.getAttributeValue("type"));
		
		for(Object source : element.getChildren("DataSource")) {
			
			definition.addDataSource(parseDataSource((Element) source));
		}
		return definition;
	}

	/**
	 * @param source
	 * @return
	 * @throws ObjectDefinitionException 
	 */
	private DataSource parseDataSource(Element source) throws ObjectDefinitionException {

		DataSource ds;
		
		if(!source.getName().equals("DataSource")) {
			throw new ObjectDefinitionException("Cannot find element 'DataSource'");
		}
		
		if(source.getAttributeValue("type").equalsIgnoreCase("JDBC")) {
	
			ds = new JDBCDataSource(DataSource.TYPE_JDBC);
			((JDBCDataSource)ds).setQuery(source.getChildText("Query").trim());
			
			// Set parameters
			for(Object param : source.getChildren("Param")) {
				
				ParameterDefinition parameter = new ParameterDefinition(
						((Element) param).getAttributeValue("format"),
						((Element) param).getAttributeValue("type"),
						((Element) param).getAttributeValue("value"));
				((JDBCDataSource) ds).addParameter(parameter);
			}
			
			
		}
		else if(source.getAttributeValue("File").equalsIgnoreCase("File")) {
			
			ds = new FileDataSource(DataSource.TYPE_FILE);
			
		}
		else
			throw new ObjectDefinitionException("Invalid type for element 'DataSource' type=" + 
					source.getAttributeValue("type"));

	
		// Add fields
		for(Object field : source.getChildren("Field")) {
			
			FieldDefinition fieldDef = new FieldDefinition(
					((Element) field).getAttributeValue("store"),
					((Element) field).getAttributeValue("index"),
					((Element) field).getAttributeValue("name"),
					((Element) field).getAttributeValue("column"));
			
			// Add transformers to field definitions
			for(Object transformer : ((Element) field).getChildren("Transformer")) {
			
				TransformerDefinition transDef = new TransformerDefinition(
						((Element) transformer).getAttributeValue("name"));
				
				fieldDef.addTransformer(transDef);
			}
			ds.addField(fieldDef);
		}
		logger.debug(ds);
		return ds;
	}
}