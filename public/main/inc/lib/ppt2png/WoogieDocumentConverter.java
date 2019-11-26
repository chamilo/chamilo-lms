//
//DokeosConverter using JODConverter - Java OpenDocument Converter
//Eric Marguin <e.marguin@elixir-interactive.com>
//
//This library is free software; you can redistribute it and/or
//modify it under the terms of the GNU Lesser General Public
//License as published by the Free Software Foundation; either
//version 2.1 of the License, or (at your option) any later version.
//
//This library is distributed in the hope that it will be useful,
//but WITHOUT ANY WARRANTY; without even the implied warranty of
//MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//Lesser General Public License for more details.
//http://www.gnu.org/copyleft/lesser.html
//

import java.util.Map;

import org.apache.commons.io.FilenameUtils;

import com.artofsolving.jodconverter.DocumentConverter;
import com.artofsolving.jodconverter.DocumentFormatRegistry;
import com.artofsolving.jodconverter.openoffice.connection.OpenOfficeConnection;
import com.artofsolving.jodconverter.openoffice.connection.OpenOfficeException;
import com.artofsolving.jodconverter.openoffice.converter.StreamOpenOfficeDocumentConverter;
import com.sun.star.beans.PropertyValue;
import com.sun.star.frame.XComponentLoader;
import com.sun.star.frame.XController;
import com.sun.star.frame.XDesktop;
import com.sun.star.frame.XModel;
import com.sun.star.frame.XStorable;
import com.sun.star.lang.XComponent;
import com.sun.star.text.XPageCursor;
import com.sun.star.text.XText;
import com.sun.star.text.XTextCursor;
import com.sun.star.text.XTextViewCursor;
import com.sun.star.text.XTextViewCursorSupplier;
import com.sun.star.uno.UnoRuntime;

/**
 * Default file-based {@link DocumentConverter} implementation.
 * <p>
 * This implementation passes document data to and from the OpenOffice.org
 * service as file URLs.
 * <p>
 * File-based conversions are faster than stream-based ones (provided by
 * {@link StreamOpenOfficeDocumentConverter}) but they require the
 * OpenOffice.org service to be running locally and have the correct
 * permissions to the files.
 * 
 * @see StreamOpenOfficeDocumentConverter
 */
public class WoogieDocumentConverter extends AbstractDokeosDocumentConverter {
	
	public WoogieDocumentConverter(OpenOfficeConnection connection, int width, int height) {		
		super(connection, width, height);
	}

	public WoogieDocumentConverter(OpenOfficeConnection connection, DocumentFormatRegistry formatRegistry, int width, int height) {
		super(connection, formatRegistry, width, height);
	}

	protected void loadAndExport(String inputUrl, Map/*<String,Object>*/ loadProperties, String outputUrl, Map/*<String,Object>*/ storeProperties) throws Exception {
		XComponentLoader desktop = openOfficeConnection.getDesktop();
		XComponent document = desktop.loadComponentFromURL(inputUrl, "_blank", 0, null);
		
        
		if (document == null) {
            throw new OpenOfficeException("conversion failed: input document is null after loading");
        }
		
		refreshDocument(document);
		
		try {
			
//			 filter
			PropertyValue[] loadProps = new PropertyValue[4];
			
			// type of image
			loadProps[0] = new PropertyValue();
			loadProps[0].Name = "MediaType";
			loadProps[0].Value = "image/png";
			
			// Height and width
			PropertyValue[] filterDatas = new PropertyValue[4];
			for(int i = 0; i<4 ; i++){
				filterDatas[i] = new PropertyValue();
			}
			
			filterDatas[0].Name = "PixelWidth";
			filterDatas[0].Value = new Integer(this.width);
			filterDatas[1].Name = "PixelHeight";
			filterDatas[1].Value = new Integer(this.height);
			filterDatas[2].Name = "LogicalWidth";
			filterDatas[2].Value = new Integer(2000);
			filterDatas[3].Name = "LogicalHeight";
			filterDatas[3].Value = new Integer(2000);
			filterDatas[3].Name = "CharacterSet";
			filterDatas[3].Value = "iso-8859-15";
//			 query its XDesktop interface, we need the current component

		    XDesktop xDesktop = (XDesktop)UnoRuntime.queryInterface(

		         XDesktop.class, desktop);
			

		    XModel xModel = (XModel)UnoRuntime.queryInterface(XModel.class, document);

		    // the model knows its controller

		    XController xController = xModel.getCurrentController();

		    XTextViewCursorSupplier xViewCursorSupplier = (XTextViewCursorSupplier) UnoRuntime.queryInterface(XTextViewCursorSupplier.class, xController);	
		    
		    // get the cursor 
		    XTextViewCursor xViewCursor = xViewCursorSupplier.getViewCursor();
		    

		    XPageCursor xPageCursor = (XPageCursor)UnoRuntime.queryInterface(

		        XPageCursor.class, xViewCursor);
		    

		    XText xDocumentText = xViewCursor.getText();

		    

		    XTextCursor xModelCursor = xDocumentText.createTextCursorByRange(xViewCursor);
		        
		        
		    do{ // swith to the next page
		    	
		    	// select the current page of document with the cursor
		    	xPageCursor.jumpToEndOfPage();
		    	xModelCursor.gotoRange(xViewCursor,false);
		    	xModelCursor.setString("||page_break||");

		    } while(xPageCursor.jumpToNextPage());

			
			
		} finally {

		   // store the document
		    XStorable storable = (XStorable) UnoRuntime.queryInterface(XStorable.class, document);
		    storeProperties.put("CharacterSet", "UTF-8");
			storable.storeToURL(outputUrl, toPropertyValues(storeProperties));
			document.dispose();
		}
	}
}
