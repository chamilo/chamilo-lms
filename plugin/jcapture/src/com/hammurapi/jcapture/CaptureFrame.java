package com.hammurapi.jcapture;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Dimension;
import java.awt.Insets;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ComponentAdapter;
import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Properties;
import java.util.concurrent.atomic.AtomicBoolean;

import javax.imageio.ImageIO;
import javax.swing.AbstractAction;
import javax.swing.JButton;
import javax.swing.JFrame;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.SwingUtilities;
import javax.swing.SwingWorker;
import javax.swing.border.LineBorder;

import netscape.javascript.JSObject;

import org.apache.http.HttpResponse;
import org.apache.http.HttpStatus;

/**
 * License: LGPL.
 * @author Pavel Vlasov.
 *
 */
public class CaptureFrame extends javax.swing.JFrame {
	private JPanel capturePanel;	
	private SimpleDateFormat dateFormat = new SimpleDateFormat("yyyyMMddHHmmss-SSS");
	private int counter;
	private CaptureConfig captureConfig;
	private AbstractCaptureApplet applet;
	private JButton recordButton;
	
	public CaptureConfig getCaptureConfig() {
		return captureConfig;
	}

	public CaptureFrame(final AbstractCaptureApplet applet) throws Exception {				
		super("Screen capture");
		setIconImage(Toolkit.getDefaultToolkit().getImage(getClass().getResource("camera.png")));

    	setUndecorated(true);
    	
    	Translucener.makeFrameTranslucent(this);
    	
		setAlwaysOnTop(true);
		this.applet = applet;
		captureConfig = new CaptureConfig();
		captureConfig.load(applet.loadConfig());
		captureConfig.setBackgroundProcessor(applet.getBackgroundProcessor());
		
		//--- GUI construction ---
		
		capturePanel = new JPanel();

		final JLabel dimensionsLabel = new JLabel("");
		capturePanel.add(dimensionsLabel, BorderLayout.CENTER);		
		
		capturePanel.addComponentListener(new ComponentAdapter() {
			
			@Override
			public void componentResized(ComponentEvent e) {				
				super.componentResized(e);
				dimensionsLabel.setText(e.getComponent().getWidth()+" x "+e.getComponent().getHeight());
			}
		});
		
		JButton captureButton = new JButton(new AbstractAction() {

			@Override
			public void actionPerformed(ActionEvent e) {
				Rectangle bounds = capturePanel.getBounds();
				Point loc = bounds.getLocation();
				SwingUtilities.convertPointToScreen(loc, capturePanel);
				bounds.setLocation(loc);
				Properties props = captureConfig.setRecordingRectangle(bounds);
				if (props!=null) {
					getApplet().storeConfig(props);
				}
				capturing.set(true);
				setVisible(false);
			}
			
		});
		captureButton.setText("Capture");
		captureButton.setToolTipText("Create a snapshot of the screen");
		capturePanel.add(captureButton, BorderLayout.CENTER);		
		
		recordButton = new JButton(new AbstractAction() {

			@Override
			public void actionPerformed(ActionEvent e) {
				Rectangle bounds = capturePanel.getBounds();
				Point loc = bounds.getLocation();
				SwingUtilities.convertPointToScreen(loc, capturePanel);
				bounds.setLocation(loc);
				Properties props = captureConfig.setRecordingRectangle(bounds);
				if (props!=null) {
					getApplet().storeConfig(props);
				}
				recording.set(true);
				setVisible(false);
			}
			
		});
		recordButton.setText("Record");
		setRecordButtonState();
		capturePanel.add(recordButton, BorderLayout.CENTER);
		
		JButton optionsButton = new JButton(new AbstractAction() {

			@Override
			public void actionPerformed(ActionEvent e) {
				new CaptureOptionsDialog(CaptureFrame.this).setVisible(true);
			}
			
		});
		optionsButton.setText("Options");
		capturePanel.add(optionsButton, BorderLayout.CENTER);
		
		JButton cancelButton = new JButton(new AbstractAction() {

			@Override
			public void actionPerformed(ActionEvent e) {
				CaptureFrame.this.setVisible(false);
			}
			
		});
		cancelButton.setText("Cancel");
		capturePanel.add(cancelButton, BorderLayout.CENTER);
		
		getContentPane().add(capturePanel, BorderLayout.CENTER);
				
		capturePanel.setBorder(new LineBorder(new java.awt.Color(0,0,0), 1, false));
		
		if (captureConfig.getRecordingRectangle()==null) {
			setSize(400, 300);
			setLocationRelativeTo(null);
		} else {
			setBounds(captureConfig.getRecordingRectangle());
		}
		    	
		Insets dragInsets = new Insets(5, 5, 5, 5);
    	new ComponentResizer(dragInsets, this);
    	
    	ComponentMover cm = new ComponentMover();
    	cm.registerComponent(this);
    	cm.setDragInsets(dragInsets);
    	
    	addComponentListener(new ComponentListener() {
			
			@Override
			public void componentShown(ComponentEvent e) {
				// TODO Auto-generated method stub
				
			}
			
			@Override
			public void componentResized(ComponentEvent e) {
				// TODO Auto-generated method stub
				
			}
			
			@Override
			public void componentMoved(ComponentEvent e) {
				// TODO Auto-generated method stub
				
			}
			
			@Override
			public void componentHidden(ComponentEvent e) {
				if (capturing.get()) {
					capturing.set(false);
					try {
						capture();
					} catch (Exception ex) {
						ex.printStackTrace();
					}							
				} else if (recording.get()) {
					recording.set(false);
					record();
				}
			}
		});
		
	}

	void setRecordButtonState() {
		if (captureConfig.getEncoder()==null) {
			recordButton.setEnabled(false);
			recordButton.setToolTipText("Video format not selected. Use Options dialog to select video format.");			
		} else {
			recordButton.setEnabled(true);
			recordButton.setToolTipText("Record screen activity and audio");
		}
	}
	
	public AbstractCaptureApplet getApplet() {
		return applet;
	}
	
	protected void capture() throws Exception {
		try {
			Thread.sleep(200); // For Ubuntu.
		} catch (InterruptedException ie) {
			// Ignore
		}
		
		BufferedImage screenShot = captureConfig.createScreenShot(null, null).call().getRegions().get(0).getImage().getImage();
		
		String prefix = getDatePrefix();
		
		String defaultImageFormat = applet.getParameter("imageFormat");
		if (defaultImageFormat==null || defaultImageFormat.trim().length()==0) {
			defaultImageFormat = "PNG";
		}
		final String defaultFileExtension=defaultImageFormat.toLowerCase();
		
		final String fileName = JOptionPane.showInputDialog(CaptureFrame.this, "Upload as", applet.getParameter("pageName")+"-capture-"+prefix+"-" + nextCounter() +"."+defaultFileExtension);
		if (fileName!=null) {
			try {
				ByteArrayOutputStream baos = new ByteArrayOutputStream();
				int idx = fileName.lastIndexOf('.');
				String imageFormat = idx==-1 ? defaultImageFormat : fileName.substring(idx+1).toUpperCase();
				ImageIO.write(screenShot, imageFormat, baos);
				final byte[] imageBytes = baos.toByteArray();
				System.out.println("Image size: "+imageBytes.length);
				// Uploading
				SwingWorker<Boolean, Long> task = new SwingWorker<Boolean, Long>() {

					@Override
					protected Boolean doInBackground() throws Exception {
						
						System.out.println("Uploading in background");
						try {									
							HttpResponse iResponse = applet.post(
									CaptureFrame.this, 
									new ByteArrayInputStream(imageBytes),
									imageBytes.length,
									fileName, 
									"application/octet-stream");
							
							System.out.println("Response status line: "+iResponse.getStatusLine());
							if (iResponse.getStatusLine().getStatusCode()!=HttpStatus.SC_OK) {
						    	errorMessage = iResponse.getStatusLine();
						    	errorTitle = "Error saving image";
						    	return false;
							}
							return true;
						} catch (Error e) {										
							errorMessage=e.toString();
							errorTitle = "Upload error";
							e.printStackTrace();
							return false;
						}
					}
					
					private Object errorMessage;
					private String errorTitle;
					
					protected void done() {
						try {
							if (get()) {
								JSObject window = JSObject.getWindow(applet);
								String toEval = "insertAtCarret('"+applet.getParameter("edid")+"','{{:"+fileName+"|}}')";
								System.out.println("Evaluating: "+toEval);
								window.eval(toEval);
								CaptureFrame.this.setVisible(false);																																			
							} else {
						    	JOptionPane.showMessageDialog(
						    			CaptureFrame.this,
										errorMessage, 
										errorTitle,
										JOptionPane.ERROR_MESSAGE);																			
							}
						} catch (Exception e) {
							e.printStackTrace();
					    	JOptionPane.showMessageDialog(
					    			CaptureFrame.this,
									e.toString(), 
									"Exception",
									JOptionPane.ERROR_MESSAGE);																			
						}
					};
					
				};
				
				task.execute();
			} catch (IOException ex) {
		    	JOptionPane.showMessageDialog(
		    			applet,
						ex.toString(), 
						"Error saving image",
						JOptionPane.ERROR_MESSAGE);									
			}
		}
	}

	public int nextCounter() {
		return counter++;
	}

	public String getDatePrefix() {
		return dateFormat.format(new Date());
	}

	protected void record() {
		try {
			Thread.sleep(200); // For Ubuntu.
		} catch (InterruptedException ie) {
			// Ignore
		}
		
		int borderWidth = 1;
		JFrame[] borderFrames = new JFrame[4];
		
		Dimension dim = Toolkit.getDefaultToolkit().getScreenSize();
		
		Rectangle rr = captureConfig.getRecordingRectangle();
		Color borderColor = Color.RED;
		if (rr.x>=borderWidth) {
			// West border
			borderFrames[0] = new JFrame();
			borderFrames[0].setDefaultCloseOperation(DISPOSE_ON_CLOSE);
			borderFrames[0].setSize(borderWidth, rr.height+borderWidth*2);
			borderFrames[0].setLocation(rr.x-borderWidth, rr.y-borderWidth);
			borderFrames[0].setUndecorated(true);
			borderFrames[0].setAlwaysOnTop(true);
			borderFrames[0].setFocusableWindowState(false);			
			borderFrames[0].getContentPane().setBackground(borderColor);
		}
		if (rr.x+rr.width<dim.width-borderWidth) {
			// East border
			borderFrames[1] = new JFrame();
			borderFrames[1].setDefaultCloseOperation(DISPOSE_ON_CLOSE);
			borderFrames[1].setSize(borderWidth, rr.height+borderWidth*2);
			borderFrames[1].setLocation(rr.x+rr.width, rr.y-borderWidth);
			borderFrames[1].setUndecorated(true);
			borderFrames[1].setAlwaysOnTop(true);
			borderFrames[1].setFocusableWindowState(false);			
			borderFrames[1].getContentPane().setBackground(borderColor);
		}
		if (rr.y>=borderWidth) {
			// North border
			borderFrames[2] = new JFrame();
			borderFrames[2].setDefaultCloseOperation(DISPOSE_ON_CLOSE);
			borderFrames[2].setSize(rr.width, borderWidth);
			borderFrames[2].setLocation(rr.x, rr.y-borderWidth);
			borderFrames[2].setUndecorated(true);
			borderFrames[2].setAlwaysOnTop(true);
			borderFrames[2].setFocusableWindowState(false);			
			borderFrames[2].getContentPane().setBackground(borderColor);
		}
		if (rr.y+rr.height<dim.height-borderWidth) {
			// South border
			borderFrames[3] = new JFrame();
			borderFrames[3].setDefaultCloseOperation(DISPOSE_ON_CLOSE);
			borderFrames[3].setSize(rr.width, borderWidth);
			borderFrames[3].setLocation(rr.x, rr.y+rr.height);
			borderFrames[3].setUndecorated(true);
			borderFrames[3].setAlwaysOnTop(true);
			borderFrames[3].setFocusableWindowState(false);			
			borderFrames[3].getContentPane().setBackground(borderColor);
		}
		
		RecordingControlsFrame inst = new RecordingControlsFrame(this, borderFrames);		
		int x = getLocation().x + getWidth() - inst.getWidth();
		if (x+inst.getWidth()>dim.getWidth()) {
			x = dim.width-inst.getWidth();
		} else if (x<0) {
			x = 0;
		}
		
		int y = rr.getLocation().y+getHeight()+1;
		if (y+inst.getHeight()>dim.height) {
			y = rr.getLocation().y-inst.getHeight();
			if (y<0) {
				y=dim.height-inst.getHeight();
			}
		}
		inst.setLocation(x, y);
		inst.setVisible(true);
	}

	private AtomicBoolean capturing = new AtomicBoolean(false);
	private AtomicBoolean recording = new AtomicBoolean(false);

}
