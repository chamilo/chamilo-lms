package com.hammurapi.jcapture;

import java.awt.Frame;
import java.awt.GraphicsDevice;
import java.awt.GraphicsEnvironment;
import java.awt.GraphicsDevice.WindowTranslucency;

public class GraphicsDeviceTranslucener extends Translucener {

	@Override
	protected void makeTranslucent(Frame frame) {
		GraphicsEnvironment ge = GraphicsEnvironment.getLocalGraphicsEnvironment();
        GraphicsDevice gd = ge.getDefaultScreenDevice();

        //If translucent windows aren't supported, exit.
        if (gd.isWindowTranslucencySupported(WindowTranslucency.TRANSLUCENT)) {
        	frame.setOpacity(0.7f);	
        }		
	}

}
