//
// DokeosConverter using JODConverter - Java OpenDocument Converter
// Eric Marguin <e.marguin@elixir-interactive.com>
//
// This library is free software; you can redistribute it and/or
// modify it under the terms of the GNU Lesser General Public
// License as published by the Free Software Foundation; either
// version 2.1 of the License, or (at your option) any later version.
//
// This library is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
// Lesser General Public License for more details.
// http://www.gnu.org/copyleft/lesser.html
//

import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.util.HashMap;
import java.util.Map;

import org.apache.commons.io.FilenameUtils;
import org.apache.commons.io.IOUtils;

import com.artofsolving.jodconverter.DocumentConverter;
import com.artofsolving.jodconverter.DocumentFormat;
import com.artofsolving.jodconverter.DocumentFormatRegistry;
import com.artofsolving.jodconverter.openoffice.connection.OpenOfficeConnection;
import com.artofsolving.jodconverter.openoffice.connection.OpenOfficeException;
import com.artofsolving.jodconverter.openoffice.converter.AbstractOpenOfficeDocumentConverter;
import com.artofsolving.jodconverter.openoffice.converter.StreamOpenOfficeDocumentConverter;
import com.sun.star.beans.PropertyValue;
import com.sun.star.container.XNamed;
import com.sun.star.document.XExporter;
import com.sun.star.document.XFilter;
import com.sun.star.drawing.XDrawPage;
import com.sun.star.drawing.XDrawPages;
import com.sun.star.drawing.XDrawPagesSupplier;
import com.sun.star.frame.XComponentLoader;
import com.sun.star.lang.XComponent;
import com.sun.star.lang.XMultiComponentFactory;
import com.sun.star.ucb.XFileIdentifierConverter;
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
public abstract class AbstractDokeosDocumentConverter extends AbstractOpenOfficeDocumentConverter {
	
	
	int width;
	int height;
	

	public AbstractDokeosDocumentConverter(OpenOfficeConnection connection, int width, int height) {		
		super(connection);
		this.width = width;
		this.height = height;
	}

	public AbstractDokeosDocumentConverter(OpenOfficeConnection connection, DocumentFormatRegistry formatRegistry, int width, int height) {
		super(connection, formatRegistry);
		this.width = width;
		this.height = height;
	}

	/**
	 * In this file-based implementation, streams are emulated using temporary files.
	 */
	protected void convertInternal(InputStream inputStream, DocumentFormat inputFormat, OutputStream outputStream, DocumentFormat outputFormat) {
		File inputFile = null;
		File outputFile = null;
		try {
			inputFile = File.createTempFile("document", "." + inputFormat.getFileExtension());
			OutputStream inputFileStream = null;
			try {
				inputFileStream = new FileOutputStream(inputFile);
				IOUtils.copy(inputStream, inputFileStream);
			} finally {
				IOUtils.closeQuietly(inputFileStream);
			}
			
			outputFile = File.createTempFile("document", "." + outputFormat.getFileExtension());
			convert(inputFile, inputFormat, outputFile, outputFormat);
			InputStream outputFileStream = null;
			try {
				outputFileStream = new FileInputStream(outputFile);
				IOUtils.copy(outputFileStream, outputStream);
			} finally {
				IOUtils.closeQuietly(outputFileStream);
			}
		} catch (IOException ioException) {
			throw new OpenOfficeException("conversion failed", ioException);
		} finally {
			if (inputFile != null) {
				inputFile.delete();
			}
			if (outputFile != null) {
				outputFile.delete();
			}
		}
	}

	protected void convertInternal(File inputFile, DocumentFormat inputFormat, File outputFile, DocumentFormat outputFormat) {
        Map/*<String,Object>*/ loadProperties = new HashMap();
        loadProperties.putAll(getDefaultLoadProperties());
        loadProperties.putAll(inputFormat.getImportOptions());

        Map/*<String,Object>*/ storeProperties = outputFormat.getExportOptions(inputFormat.getFamily());

        synchronized (openOfficeConnection) {
			XFileIdentifierConverter fileContentProvider = openOfficeConnection.getFileContentProvider();
			String inputUrl = fileContentProvider.getFileURLFromSystemPath("", inputFile.getAbsolutePath());
			String outputUrl = fileContentProvider.getFileURLFromSystemPath("", outputFile.getAbsolutePath());
            
			try {
				loadAndExport(inputUrl, loadProperties, outputUrl, storeProperties);
            } catch (OpenOfficeException openOfficeException) {
                throw openOfficeException;
			} catch (Throwable throwable) {
				// difficult to provide finer grained error reporting here;
				// OOo seems to throw ErrorCodeIOException most of the time
				throw new OpenOfficeException("conversion failed", throwable);
			}
		}
	}

	abstract protected void loadAndExport(String inputUrl, Map/*<String,Object>*/ loadProperties, String outputUrl, Map/*<String,Object>*/ storeProperties) throws Exception;
		
	
	
	protected DocumentFormat guessDocumentFormat(File file) {
		String extension = FilenameUtils.getExtension(file.getName());
		DocumentFormat format = getDocumentFormatRegistry().getFormatByFileExtension(extension);
		if (format == null) {
			//throw new IllegalArgumentException("unknown document format for file: " + file);
		}
		return format;
	}
}
