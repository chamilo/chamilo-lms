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

import com.sun.star.bridge.XBridge;
import com.sun.star.lang.XMultiComponentFactory;
import com.sun.star.uno.XComponentContext;



public class DokeosSocketOfficeConnection extends AbstractDokeosOpenOfficeConnection {
	
	public static final String DEFAULT_HOST = "localhost";
    public static final int DEFAULT_PORT = 8100;

    public DokeosSocketOfficeConnection() {
        this(DEFAULT_HOST, DEFAULT_PORT);
    }

    public DokeosSocketOfficeConnection(int port) {
    	this(DEFAULT_HOST, port);
    }

    public DokeosSocketOfficeConnection(String host, int port) {
        super("socket,host=" + host + ",port=" + port + ",tcpNoDelay=1");
    }
    
    public XMultiComponentFactory getServiceManager(){
    	
    	return serviceManager;
    	
    }
    
    public XMultiComponentFactory getRemoteServiceManager(){
    	
    	return serviceManager;
    	
    }
    
    public XBridge getBridge(){
    	
    	return bridge;
    	
    }
    
    public XComponentContext getComponentContext(){
    	
    	return componentContext;
    	
    }
    
    
}
