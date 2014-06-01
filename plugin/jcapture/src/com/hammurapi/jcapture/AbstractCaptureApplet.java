package com.hammurapi.jcapture;

import java.awt.Component;
import java.io.Closeable;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.ProxySelector;
import java.text.MessageFormat;
import java.util.ArrayList;
import java.util.Collection;
import java.util.Iterator;
import java.util.Properties;
import java.util.StringTokenizer;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Executors;
import java.util.concurrent.ThreadFactory;

import javax.swing.JApplet;
import javax.swing.JOptionPane;
import javax.swing.ProgressMonitorInputStream;
import javax.swing.SwingUtilities;

import org.apache.commons.codec.DecoderException;
import org.apache.commons.codec.binary.Hex;
import org.apache.commons.codec.net.URLCodec;
import org.apache.http.HttpResponse;
import org.apache.http.client.methods.HttpUriRequest;
import org.apache.http.entity.mime.content.InputStreamBody;
import org.apache.http.impl.client.DefaultHttpClient;
import org.apache.http.impl.conn.ProxySelectorRoutePlanner;

/**
 * Base class for capture applets.
 * @author Pavel
 *
 */
public abstract class AbstractCaptureApplet extends JApplet {
	
	private static final String OUTPUT_DIR_PARAMETER = "outputDir";
	
	private CaptureFrame captureFrame;

	@Override
	public void stop() {
		if (captureFrame!=null) {
			captureFrame.dispose();
			captureFrame = null;
		}
		backgroundProcessor.shutdown();
		synchronized (closeables) {
			Iterator<Closeable> cit = closeables.iterator();
			while (cit.hasNext()) {
				try {
					cit.next().close();
				} catch (Exception e) {
					e.printStackTrace();
				}
				cit.remove();
			}
		}
		super.stop();
	}
	
	/**
	 * Adds closeable to close in applet stop() method.
	 * @param closeable
	 */
	public void addCloseable(Closeable closeable) {
		synchronized (closeables) {
			closeables.add(closeable);
		}
	}
	
	private Collection<Closeable> closeables = new ArrayList<Closeable>();

	public void showCaptureFrame() {
		System.out.println("Showing capture frame");
		SwingUtilities.invokeLater(new Runnable() {
			
			@Override
			public void run() {
				try {
					if (captureFrame==null) {
						createCaptureFrame();
					}
					captureFrame.setVisible(true);
				} catch (Exception e) {
					e.printStackTrace();
				}
			}
			
		});
	}
	
	private ExecutorService backgroundProcessor;
	
	@Override
	public void start() {		
		super.start();
		
		ThreadFactory threadFactory =  new ThreadFactory() {
			
			@Override
			public Thread newThread(Runnable r) {
				Thread th=new Thread(r, "Background processor");
				th.setPriority(Thread.NORM_PRIORITY);
				return th;
			}
		};
		backgroundProcessor = Executors.newSingleThreadExecutor(threadFactory);
		
		SwingUtilities.invokeLater(new Runnable() {
	
			public void run() {
				createCaptureFrame();
			}
			
		});
		
		try {
			// Proxy configuration - requires java.net.NetPermission getProxySelector
			proxySelector = ProxySelector.getDefault();
		} catch (Exception e) {
			System.err.println("Can't obtain proxy information: "+e);
			e.printStackTrace();				
		}
	}

	public ExecutorService getBackgroundProcessor() {
		return backgroundProcessor;
	}
	
	protected void createCaptureFrame() {
		try {
			captureFrame = new CaptureFrame(this);
			captureFrame.setVisible(true);
		} catch (Exception e) {
			JOptionPane.showMessageDialog(
					null,
					"Error: "+e, 
					"Cannot create capture window",
					JOptionPane.ERROR_MESSAGE);
			e.printStackTrace();
		}
	}
	
	public static String formatByteSize(long bytes) {
		if (bytes<1024) {
			return bytes + "bytes";
		}
		if (bytes<1024*1024) {
			return MessageFormat.format("{0,number,0.0} Kb", new Object[] {(double) bytes/1024.0});
		}
		if (bytes<1024*1024*1024) {
			return MessageFormat.format("{0,number,0.00} Mb", new Object[] {(double) bytes/(double) (1024.0*1024.0)});
		}
		return MessageFormat.format("{0,number,0.00} Gb", new Object[] {(double) bytes/(double) (1024.0*1024.0*1024.0)});
	}
	
	
	protected File preferencesFile = new File(System.getProperty("user.home")+File.separator+"."+getClass().getName()+".properties");
	 
	public Properties loadConfig() {
		try {
			if (preferencesFile.isFile()) {
				InputStream configStream = new FileInputStream(preferencesFile);				
				Properties ret = new Properties();
				ret.load(configStream);
				configStream.close();
				return ret;
			}
		} catch (Exception e) {
			e.printStackTrace();
		}
		return null;
	}
	
	public void storeConfig(Properties properties) {
		try {
			FileOutputStream out = new FileOutputStream(preferencesFile);
			properties.store(out, "Config");
			out.close();
		} catch (Exception e) {
			e.printStackTrace();
		}		
	}
					
	protected String getCookies() throws DecoderException {
		String cookiesStr = getParameter("cookies");
		if (cookiesStr==null) {
			return null;
		}
		
		StringBuilder ret = new StringBuilder();
		StringTokenizer st = new StringTokenizer(cookiesStr, ";");
		while (st.hasMoreTokens()) {
			String tok = st.nextToken();
			int idx = tok.indexOf("=");
			ret.append(hex2urlEncoded(tok.substring(0, idx)));
			ret.append("=");
			ret.append(hex2urlEncoded(tok.substring(idx+1)));
			if (st.hasMoreElements()) {
				ret.append(";");
			}
		}
		
		return ret.toString();
	}
	
	private String hex2urlEncoded(String hexStr) throws DecoderException {
		return new String(URLCodec.encodeUrl(null, Hex.decodeHex(hexStr.toCharArray())));
	}
				
	protected ProxySelector proxySelector;
	
	/**
	 * Posts capture/recording to the web site.
	 * @param parentComponent Parent component for the progress bar.
	 * @param content Content - file or byte array.
	 * @param fileName File name.
	 * @param mimeType Mime type.
	 * @return
	 * @throws Exception
	 */
	public HttpResponse post(
			Component parentComponent, 
			final InputStream content,
			final long contentLength,
			String fileName, 
			String mimeType) throws Exception {
		
		
		System.out.println("jCapture applet, build @@@time@@@");
		
		/**
		 * Debugging - save to file.
		 */
		if (getParameter(OUTPUT_DIR_PARAMETER)!=null) {
			OutputStream out = new FileOutputStream(new File(getParameter(OUTPUT_DIR_PARAMETER)+File.separator+fileName));
			byte[] buf=new byte[4096];
			int l;
			while ((l=content.read(buf))!=-1) {
				out.write(buf, 0, l);
			}
			out.close();
			content.close();
			return null;
		}
		
	    ProgressMonitorInputStream pmis = new ProgressMonitorInputStream(parentComponent, "Uploading "+ fileName + " ("+formatByteSize(contentLength)+")", content);	    
		InputStreamBody bin = new InputStreamBody(pmis, mimeType, bodyName(fileName)) {
	    	
	    	@Override
	    	public long getContentLength() {
	    		return contentLength;
	    	}
	    };		    
	    
		DefaultHttpClient httpClient = new DefaultHttpClient();
	    if (proxySelector!=null) {
	    	ProxySelectorRoutePlanner routePlanner = new ProxySelectorRoutePlanner(
		        httpClient.getConnectionManager().getSchemeRegistry(),
		        proxySelector);
	    	httpClient.setRoutePlanner(routePlanner);
	    }
		return httpClient.execute(createRequest(fileName, bin));			
	}

	protected abstract HttpUriRequest createRequest(String fileName, InputStreamBody bin) throws Exception;
	
	protected abstract String bodyName(String fileName);
}
