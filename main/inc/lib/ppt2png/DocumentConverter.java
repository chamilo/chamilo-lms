import sun.text.Normalizer;

import com.enterprisedt.net.ftp.FTPClient;
import com.enterprisedt.net.ftp.FTPConnectMode;
import com.enterprisedt.net.ftp.FTPTransferType;
import com.sun.star.beans.PropertyValue;
import com.sun.star.beans.XPropertySet;
import com.sun.star.bridge.XBridge;
import com.sun.star.bridge.XBridgeFactory;
import com.sun.star.connection.NoConnectException;
import com.sun.star.connection.XConnection;
import com.sun.star.connection.XConnector;
import com.sun.star.container.XNamed;
import com.sun.star.document.XExporter;
import com.sun.star.document.XFilter;
import com.sun.star.drawing.XDrawPage;
import com.sun.star.drawing.XDrawPages;
import com.sun.star.drawing.XDrawPagesSupplier;
import com.sun.star.frame.XComponentLoader;
import com.sun.star.lang.XComponent;
import com.sun.star.lang.XMultiComponentFactory;
import com.sun.star.presentation.XPresentation;
import com.sun.star.presentation.XPresentationSupplier;
import com.sun.star.uno.UnoRuntime;
import com.sun.star.uno.XComponentContext;

/**
 * The class <CODE>DocumentConverter</CODE> allows you to convert all
 * documents in a given directory and in its subdirectories to a given type. A
 * converted document will be created in the same directory as the origin
 * document.
 * 
 */
public class DocumentConverter {
	/**
	 * Containing the loaded documents
	 */
	static XComponentLoader xcomponentloader = null;
 
	/**
	 * Connecting to the office with the component UnoUrlResolver and calling
	 * the static method traverse
	 * 
	 * @param args
	 *            The array of the type String contains the directory, in which
	 *            all files should be converted, the favoured converting type
	 *            and the wanted extension
	 */
	public static void main(String args[]) {
		
		String cnx, host, port, url, ftpPasswd, destinationFolder, remoteFolderFullPath, remoteFolder;
		
		try {
			host = args[0];
			port = args[1];
			url = args[2];
			destinationFolder = args[3];		
			if(args.length == 5){
				ftpPasswd = args[4];
			}
			else{
				ftpPasswd = "";
			}
			if(host.equals("localhost")){
				url = "file://"+url;
				remoteFolder = destinationFolder;
				remoteFolderFullPath = "file:///";
			}
			else {
				remoteFolderFullPath = "file:///home/elixir/";					
				remoteFolder = url.replace('/','_');
				remoteFolder = removeAccents(remoteFolder);
			}			
			
			cnx = "socket,host="+host+",port="+port;
		
			XComponentContext xComponentContext = com.sun.star.comp.helper.Bootstrap
					.createInitialComponentContext(null);
			
			
			XComponentContext xRemoteContext = xComponentContext;

			Object x = xRemoteContext
					.getServiceManager()
					.createInstanceWithContext(
							"com.sun.star.connection.Connector", xRemoteContext);

			XConnector xConnector = (XConnector) UnoRuntime.queryInterface(
					XConnector.class, x);
			
			XConnection connection = xConnector.connect(cnx);

			//if (connection == null)
				//System.out.println("Connection is null");
			x = xRemoteContext.getServiceManager().createInstanceWithContext(
					"com.sun.star.bridge.BridgeFactory", xRemoteContext);

			
			XBridgeFactory xBridgeFactory = (XBridgeFactory) UnoRuntime
					.queryInterface(XBridgeFactory.class, x);
			
			
			// this is the bridge that you will dispose
			XBridge bridge = xBridgeFactory.createBridge("", "urp", connection,null);
			
			/*XComponent xComponent = (XComponent) UnoRuntime.queryInterface(
					XComponent.class, bridge);*/
			// get the remote instance
			x = bridge.getInstance("StarOffice.ServiceManager");
			// Query the initial object for its main factory interface
			XMultiComponentFactory xMultiComponentFactory = (XMultiComponentFactory) UnoRuntime
					.queryInterface(XMultiComponentFactory.class, x);
			XPropertySet xProperySet = (XPropertySet) UnoRuntime
					.queryInterface(XPropertySet.class, xMultiComponentFactory);
			
			// Get the default context from the office server.
			Object oDefaultContext = xProperySet
					.getPropertyValue("DefaultContext");

			// Query for the interface XComponentContext.
			xComponentContext = (XComponentContext) UnoRuntime.queryInterface(
					XComponentContext.class, oDefaultContext);
			
			/*Object objectUrlResolver = xMultiComponentFactory
					.createInstanceWithContext(
							"com.sun.star.bridge.UnoUrlResolver",
							xComponentContext);*/
			
			//System.out.print("Trying to connect");
			while (xcomponentloader == null) {
				try {	
					
					//System.out.print(".");
					//Object objectInitial = xurlresolver.resolve(cnx);
					//System.out.println("\r\nConnection opened");

					xcomponentloader = (XComponentLoader) UnoRuntime
							.queryInterface(
									XComponentLoader.class,
									xMultiComponentFactory
											.createInstanceWithContext(
													"com.sun.star.frame.Desktop",
													xComponentContext));
					
					//System.out.println("Loading document "+url);
									
					FTPClient ftp = new FTPClient();
					if(!host.equals("localhost")){
						//ftp connexion						
						ftp.setRemoteHost(host);
						ftp.connect();
						ftp.login("elixir", ftpPasswd);
						ftp.setConnectMode(FTPConnectMode.PASV);
			            ftp.setType(FTPTransferType.ASCII);
			            try{
			            	ftp.mkdir(remoteFolder);
			            }catch(Exception e){}
			            ftp.chdir(remoteFolder);
			            ftp.put(url,"presentation.ppt");			            
			            url = remoteFolderFullPath+"/"+remoteFolder+"/presentation.ppt";
			            ftp.setType(FTPTransferType.BINARY);
			            
					}
					
					PropertyValue[] loadProps = new PropertyValue[1];
					loadProps[0] = new PropertyValue();
				    loadProps[0].Name = "Hidden";
				    loadProps[0].Value = new Boolean(true);
					// open the document
					XComponent component = xcomponentloader
							.loadComponentFromURL(url,
									"_blank", 0, loadProps);
					//System.out.println("Document Opened");
					
					// filter
					loadProps = new PropertyValue[2];
					loadProps[0] = new PropertyValue();
					loadProps[0].Name = "MediaType";
					loadProps[0].Value = "image/png";
					
					XDrawPagesSupplier pagesSupplier = (XDrawPagesSupplier) UnoRuntime
							.queryInterface(XDrawPagesSupplier.class, component);
					//System.out.println(pagesSupplier.toString());				
					XDrawPages pages = pagesSupplier.getDrawPages();
					int nbPages = pages.getCount();
		            
					
					for (int i = 0; i < nbPages; i++) {
											
						XDrawPage page = (XDrawPage) UnoRuntime.queryInterface(
								com.sun.star.drawing.XDrawPage.class, pages
										.getByIndex(i));
						
						XNamed xPageName = (XNamed)UnoRuntime.queryInterface(XNamed.class,page);
						xPageName.setName(xPageName.getName().replace(' ','_'));
						xPageName.setName(removeAccents(xPageName.getName()));
						Object GraphicExportFilter = xMultiComponentFactory
								.createInstanceWithContext(
										"com.sun.star.drawing.GraphicExportFilter",
										xComponentContext);
						XExporter xExporter = (XExporter) UnoRuntime
								.queryInterface(XExporter.class,
										GraphicExportFilter);

						XComponent xComp = (XComponent) UnoRuntime
								.queryInterface(XComponent.class, page);

						xExporter.setSourceDocument(xComp);

						loadProps[1] = new PropertyValue();
						loadProps[1].Name = "URL";
						loadProps[1].Value = remoteFolderFullPath+remoteFolder+"/"+(i+1) + "-"+xPageName.getName()+".png";
						XFilter xFilter = (XFilter) UnoRuntime.queryInterface(XFilter.class, GraphicExportFilter);

						xFilter.filter(loadProps);
						System.out.println((i+1) + "-"+xPageName.getName()+".png");
						//System.out.println("Page saved to url "+loadProps[1].Value);
						
					}
					
					if(!host.equals("localhost")){
						String[] files = ftp.dir();
			            for (int i = 0; i < files.length; i++){
			            	//System.out.println("Transfer of "+files[i]+ "to "+destinationFolder+"/"+files[i]);
			            	if(!files[i].equals("presentation.ppt"))
			            		ftp.get(destinationFolder+"/"+files[i],files[i]);
			            	ftp.delete(files[i]);
			            }
						ftp.chdir("..");
						ftp.rmdir(remoteFolder);
						ftp.quit();
					}
					
					//System.out.println("Closing Document");
					component.dispose();
					//System.out.println("Document close");

					System.exit(0);
				} 
				catch (NoConnectException e) {
					System.out.println(e.toString());
					e.printStackTrace();
					System.exit(255);
				} 
				catch (Exception e) {
					System.out.println(e.toString());
					e.printStackTrace();
					System.exit(255);
				}

			}
		} 
		catch (Exception e) {
			System.out.println(e.toString());
			e.printStackTrace();
			System.exit(255);
		}

	}
	
	public static String removeAccents(String text) {
	    return Normalizer.decompose(text, false, 0)
	                     .replaceAll("\\p{InCombiningDiacriticalMarks}+", "");
	}
}
