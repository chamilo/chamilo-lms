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
import java.net.ConnectException;

import org.apache.commons.cli.CommandLine;
import org.apache.commons.cli.CommandLineParser;
import org.apache.commons.cli.HelpFormatter;
import org.apache.commons.cli.Option;
import org.apache.commons.cli.Options;
import org.apache.commons.cli.PosixParser;
import org.apache.commons.io.FilenameUtils;

import com.artofsolving.jodconverter.DocumentConverter;
import com.artofsolving.jodconverter.openoffice.connection.OpenOfficeConnection;
import com.artofsolving.jodconverter.openoffice.connection.SocketOpenOfficeConnection;
import com.artofsolving.jodconverter.openoffice.converter.OpenOfficeDocumentConverter;

/**
 * Command line tool to convert a document into a different format.
 * <p>
 * Usage: can convert a single file
 * 
 * <pre>
 * ConvertDocument test.odt test.pdf
 * </pre>
 * 
 * or multiple files at once by specifying the output format
 * 
 * <pre>
 * ConvertDocument -f pdf test1.odt test2.odt
 * ConvertDocument -f pdf *.odt
 * </pre>
 */
public class DokeosConverter {

    private static final Option OPTION_OUTPUT_FORMAT = new Option("f", "output-format", true, "output format (e.g. pdf)");
    private static final Option OPTION_PORT = new Option("p", "port", true, "OpenOffice.org port");
    private static final Option OPTION_VERBOSE = new Option("v", "verbose", false, "verbose");
    private static final Option OPTION_DOKEOS_MODE = new Option("d", "dokeos-mode", true, "use oogie or woogie");
    private static final Option OPTION_WIDTH = new Option("w", "width", true, "width");
    private static final Option OPTION_HEIGHT = new Option("h", "height", true, "height");
    private static final Options OPTIONS = initOptions();

    private static final int EXIT_CODE_CONNECTION_FAILED = 1;
    private static final int EXIT_CODE_CONVERSION_FAILED = 2;
    private static final int EXIT_CODE_TOO_FEW_ARGS = 255;

    private static Options initOptions() {
        Options options = new Options();
        options.addOption(OPTION_OUTPUT_FORMAT);
        options.addOption(OPTION_PORT);
        options.addOption(OPTION_VERBOSE);
        options.addOption(OPTION_DOKEOS_MODE);
        options.addOption(OPTION_WIDTH);
        options.addOption(OPTION_HEIGHT);
        return options;
    }

    public static void main(String[] arguments) throws Exception {
        CommandLineParser commandLineParser = new PosixParser();
        CommandLine commandLine = commandLineParser.parse(OPTIONS, arguments);

        int port = SocketOpenOfficeConnection.DEFAULT_PORT;
        if (commandLine.hasOption(OPTION_PORT.getOpt())) {
            port = Integer.parseInt(commandLine.getOptionValue(OPTION_PORT.getOpt()));
        }

        String outputFormat = null;
        if (commandLine.hasOption(OPTION_OUTPUT_FORMAT.getOpt())) {
            outputFormat = commandLine.getOptionValue(OPTION_OUTPUT_FORMAT.getOpt());
        }

        boolean verbose = false;
        if (commandLine.hasOption(OPTION_VERBOSE.getOpt())) {
            verbose = true;
        }
        
        String dokeosMode = "woogie";
        if (commandLine.hasOption(OPTION_DOKEOS_MODE.getOpt())) {
        	dokeosMode = commandLine.getOptionValue(OPTION_DOKEOS_MODE.getOpt());
        }
        int width = 800;
        if (commandLine.hasOption(OPTION_WIDTH.getOpt())) {
        	width = Integer.parseInt(commandLine.getOptionValue(OPTION_WIDTH.getOpt()));
        }
        
        int height = 600;
        if (commandLine.hasOption(OPTION_HEIGHT.getOpt())) {
        	height = Integer.parseInt(commandLine.getOptionValue(OPTION_HEIGHT.getOpt()));
        }

        String[] fileNames = commandLine.getArgs();
        if ((outputFormat == null && fileNames.length != 2 && dokeosMode!=null) || fileNames.length < 1) {
        	String syntax = "convert [options] input-file output-file; or\n"
                    + "[options] -f output-format input-file [input-file...]";
            HelpFormatter helpFormatter = new HelpFormatter();
            helpFormatter.printHelp(syntax, OPTIONS);
            System.exit(EXIT_CODE_TOO_FEW_ARGS);
        }

        OpenOfficeConnection connection = new DokeosSocketOfficeConnection(port);
        try {
            if (verbose) {
                System.out.println("-- connecting to OpenOffice.org on port " + port);
            }
            connection.connect();
        } catch (ConnectException officeNotRunning) {
            System.err
                    .println("ERROR: connection failed. Please make sure OpenOffice.org is running and listening on port "
                            + port + ".");
            System.exit(EXIT_CODE_CONNECTION_FAILED);
        }
        try {
        	
        	
        	// choose the good constructor to deal with the conversion
        	DocumentConverter converter;
        	if(dokeosMode.equals("oogie")){
        		converter = new OogieDocumentConverter(connection, new DokeosDocumentFormatRegistry(), width, height);
        	}
        	else if(dokeosMode.equals("woogie")){
        		converter = new WoogieDocumentConverter(connection, new DokeosDocumentFormatRegistry(), width, height);
        	}
        	else {
        		converter = new OpenOfficeDocumentConverter(connection);
        	}
        	
        	
            if (outputFormat == null) {
                File inputFile = new File(fileNames[0]);
                File outputFile = new File(fileNames[1]);
                convertOne(converter, inputFile, outputFile, verbose);
            } else {
                for (int i = 0; i < fileNames.length; i++) {
                    File inputFile = new File(fileNames[i]);
                    File outputFile = new File(FilenameUtils.getFullPath(fileNames[i])
                            + FilenameUtils.getBaseName(fileNames[i]) + "." + outputFormat);
                    convertOne(converter, inputFile, outputFile, verbose);
                }
            }
        } 
        catch (com.artofsolving.jodconverter.openoffice.connection.OpenOfficeException e)
        {
        	connection.disconnect();
        	System.err.println("ERROR: conversion failed.");
        	System.exit(EXIT_CODE_CONVERSION_FAILED);
        }
        finally {
            if (verbose) {
                System.out.println("-- disconnecting");
            }
            connection.disconnect();
        }
    }

    private static void convertOne(DocumentConverter converter, File inputFile, File outputFile, boolean verbose) {
        if (verbose) {
            System.out.println("-- converting " + inputFile + " to " + outputFile);
        }
        converter.convert(inputFile, outputFile);
    }
}
