package com.hammurapi.jcapture;

import java.awt.Frame;

import com.sun.awt.AWTUtilities;
import com.sun.awt.AWTUtilities.Translucency;

public class AWTUtilitiesTranslucener extends Translucener {

	@Override
	protected void makeTranslucent(Frame frame) {
        if (AWTUtilities.isTranslucencySupported(Translucency.TRANSLUCENT)) {
        	AWTUtilities.setWindowOpacity(frame, 0.7f);	
        }		
	}

}
