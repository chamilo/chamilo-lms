package com.hammurapi.jcapture;

import java.awt.Frame;

abstract class Translucener {
	
	protected abstract void makeTranslucent(Frame frame);
	
	static void makeFrameTranslucent(Frame frame) throws Exception {
		String jVersion = System.getProperty("java.version");
		if (jVersion==null || "1.6".equals(jVersion) || jVersion.startsWith("1.6.")) {
			((Translucener) Class.forName("com.hammurapi.jcapture.AWTUtilitiesTranslucener").newInstance()).makeTranslucent(frame);
		} else {
			((Translucener) Class.forName("com.hammurapi.jcapture.GraphicsDeviceTranslucener").newInstance()).makeTranslucent(frame);			
		}
	}
}
