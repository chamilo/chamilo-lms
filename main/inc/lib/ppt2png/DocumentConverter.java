import java.awt.Event;

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
		
		String cnx, ftpuser, host, port, url, ftpPasswd, destinationFolder, remoteFolderFullPath, remoteFolder;
		int width, height;
		
		try {			
			host = args[0];
			port = args[1];
			url = args[2];
			destinationFolder = args[3];
			width = Integer.parseInt(args[4]);
			height = Integer.parseInt(args[5]);
			if(args.length == 8){
				ftpuser = args[6];
				ftpPasswd = args[7];
			}
			else{
				ftpuser = "";
				ftpPasswd = "";
			}
			
			
			if(host.equals("localhost")){
				String prefix = "file://";
				if(url.charAt(0)!='/')
					prefix += '/';
				url = prefix+url;
				remoteFolder = destinationFolder; 
				remoteFolderFullPath = prefix;
			}
			else {
				remoteFolderFullPath = "file:///home/"+ftpuser+"/";					
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
			
			
			while (xcomponentloader == null) {
				try {	
					
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
						ftp.login(ftpuser, ftpPasswd);
						ftp.setConnectMode(FTPConnectMode.PASV);
						ftp.setType(FTPTransferType.BINARY);
			            try{
			            	ftp.mkdir(remoteFolder);
			            }catch(Exception e){}
			            ftp.chdir(remoteFolder);
			            ftp.put(url,"presentation.ppt");			            
			            url = remoteFolderFullPath+"/"+remoteFolder+"/presentation.ppt";
			            
			            
					}
					
					PropertyValue[] loadProps = new PropertyValue[2];
					loadProps[0] = new PropertyValue();
				    loadProps[0].Name = "Hidden";
				    loadProps[0].Value = new Boolean(true);
				    
					// open the document
					XComponent component = xcomponentloader
							.loadComponentFromURL(url,
									"_blank", 0, loadProps);
					
		          
					//System.out.println("Document Opened");
					
					// filter
					loadProps = new PropertyValue[4];
					
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
					filterDatas[0].Value = new Integer(width);
					filterDatas[1].Name = "PixelHeight";
					filterDatas[1].Value = new Integer(height);
					filterDatas[2].Name = "LogicalWidth";
					filterDatas[2].Value = new Integer(2000);
					filterDatas[3].Name = "LogicalHeight";
					filterDatas[3].Value = new Integer(2000);
					
					
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
						
						xPageName.setName("slide"+(i+1));
						//if(!xPageName.getName().equals("slide"+(i+1)) && !xPageName.getName().equals("page"+(i+1)))
							//xPageName.setName((i+1)+"-"+xPageName.getName());
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
						loadProps[1].Value = remoteFolderFullPath+remoteFolder+"/"+xPageName.getName()+".png";
						loadProps[2] = new PropertyValue();
						loadProps[2].Name = "FilterData";
						loadProps[2].Value = filterDatas;
						loadProps[3] = new PropertyValue(); 
						loadProps[3].Name = "Quality"; 
						loadProps[3].Value = new Integer(100);
						
						XFilter xFilter = (XFilter) UnoRuntime.queryInterface(XFilter.class, GraphicExportFilter);

						xFilter.filter(loadProps);
						System.out.println(xPageName.getName()+".png");

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
	    String newText =  Normalizer.decompose(text, false, 0)
	                     .replaceAll("\\p{InCombiningDiacriticalMarks}+", "");
	    /*
	    newText = newText.replace('\u00B4','_');
	    newText = newText.replace('\u02CA','_');
	    newText = newText.replace('\u02B9','_');
	    newText = newText.replace('\u02BC','_');	    
	    newText = newText.replace('\u02B9','_');
	    newText = newText.replace('\u03D8','_');
	    newText = newText.replace('\u0374','_');
	    newText = newText.replace('\u0384','_');
	    newText = newText.replace('\u055A','_');
	    */
	    newText = newText.replace('\u2019','_');
	    newText = newText.replace('\u00B4','_');
	    newText = newText.replace('\u055A','_');
	    newText = newText.replace('?','_');
	    newText = newText.replace('\'','_');
	    newText = newText.replace(' ','_');
	    return newText;
	}
	
	public boolean handleEvent(Event evt) {
        // Traitement de l'evenement de fin de programme
         if ( evt.id == evt.WINDOW_DESTROY ) {
              System.exit(0) ;
              return true ;
         }
         return false ;
  }
}
