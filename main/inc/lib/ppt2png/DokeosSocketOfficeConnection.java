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
    
    public XComponentContext getComponentContext(){
    	
    	return componentContext;
    	
    }
    
    
}
