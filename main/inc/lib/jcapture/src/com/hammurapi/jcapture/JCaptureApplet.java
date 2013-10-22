package com.hammurapi.jcapture;

import org.apache.commons.codec.DecoderException;
import org.apache.commons.codec.binary.Hex;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.client.methods.HttpUriRequest;
import org.apache.http.entity.mime.MultipartEntity;
import org.apache.http.entity.mime.content.InputStreamBody;
import org.apache.http.entity.mime.content.StringBody;

public class JCaptureApplet extends AbstractCaptureApplet {
	
	private static final String HTTPS_PREFIX = "https://";

	protected HttpUriRequest createRequest(String fileName, InputStreamBody bin) throws Exception {
		String uploadUrl = getParameter("uploadUrl");
		if (uploadUrl==null || uploadUrl.trim().length()==0) {		
			String host = getParameter("host");
			String dokuHost = host;
			
			if (dokuHost.toLowerCase().startsWith(HTTPS_PREFIX)) {
				if (dokuHost.lastIndexOf(":")<HTTPS_PREFIX.length()) { // No port number
					dokuHost+=":443";
				}
			} else if (dokuHost.endsWith(":80")) {
				dokuHost = dokuHost.substring(0, dokuHost.length()-3);
			} 
			System.out.println("DokuHost: "+dokuHost);
			String dokuBase = getDokuBase();
			System.out.println("DokuBase: "+dokuBase);

			StringBuilder uploadUrlBuilder = new StringBuilder(dokuHost);

			if (dokuBase.startsWith(host)) {
				dokuBase = dokuBase.substring(host.length());
			}
			uploadUrlBuilder.append(dokuBase);
			uploadUrlBuilder.append("lib/exe/mediamanager.php");
			uploadUrl = uploadUrlBuilder.toString();
		}
		System.out.println("Uploading to "+uploadUrl);
        HttpPost httppost = new HttpPost(uploadUrl);

        if (!httppost.containsHeader("Cookie")) {
        	httppost.setHeader("Cookie", getCookies());
        }
        
        httppost.setHeader("Pragma", "No-cache");       
	    
	    MultipartEntity reqEntity = new MultipartEntity();
	    String sectok = getParameter("sectok");
	    if (sectok!=null && sectok.trim().length()>0) {
			reqEntity.addPart("sectok", new StringBody(sectok));
	    }
	    reqEntity.addPart("ow", new StringBody("1"));	
	    
	    String opaque = getParameter("opaque");
	    if (opaque!=null && opaque.trim().length()>0) {
			reqEntity.addPart("opaque", new StringBody(opaque));	    	
	    }
	    
	    reqEntity.addPart("Filename", new StringBody(fileName));
	    
	    int nsIdx = fileName.lastIndexOf(":");
	    String namespace;
	    if (nsIdx==-1) {
	    	namespace = ":";
	    } else {
	    	namespace = ":"+fileName.substring(0, nsIdx);
	    	fileName = fileName.substring(nsIdx+1);
	    }
        
	    if (namespace!=null) {
		    reqEntity.addPart("ns", new StringBody(namespace));		    	
	    }
	    
	    reqEntity.addPart("Filedata", bin);
	    
	    httppost.setEntity(reqEntity);
		return httppost;
	}

	String getDokuBase() throws DecoderException {
		return new String(Hex.decodeHex(getParameter("dokuBase").toCharArray()));
	}

	@Override
	protected String bodyName(String fileName) {
	    return fileName.substring(fileName.lastIndexOf(":")+1);
	}
	
}
