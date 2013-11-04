package com.hammurapi.jcapture;

import java.awt.BorderLayout;
import java.awt.Dimension;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.ComponentEvent;
import java.awt.event.ComponentListener;
import java.io.File;
import java.io.FileInputStream;
import java.io.FileOutputStream;

import javax.swing.JButton;
import javax.swing.JFrame;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.SwingWorker;
import javax.swing.WindowConstants;
import javax.swing.border.LineBorder;

import netscape.javascript.JSObject;

import org.apache.http.HttpResponse;
import org.apache.http.HttpStatus;

public class RecordingControlsFrame extends javax.swing.JFrame {
	private static final String RESUME_TEXT = "Resume";
	private static final String PAUSE_TEXT = "Pause";
	private JButton pauseButton;
	private JButton cancelButton;
	private JButton stopButton;
	private ScreenRecorder screenRecorder;
	private CaptureFrame captureFrame;

	public RecordingControlsFrame(final CaptureFrame captureFrame, final JFrame[] borderFrames) {
		super("jCapture recording");		
		setIconImage(captureFrame.getIconImage());
		this.captureFrame = captureFrame;
		
		setUndecorated(true);
		setAlwaysOnTop(!getBounds().intersects(captureFrame.getBounds()));
		
    	addComponentListener(new ComponentListener() {
			
			@Override
			public void componentShown(ComponentEvent e) {
				for (JFrame bf: borderFrames) {
					if (bf!=null) {
						bf.setVisible(true);
					}
				}				
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
				for (JFrame bf: borderFrames) {
					if (bf!=null) {
						bf.setVisible(false);
					}
				}				
			}
		});
				
		JPanel contentPanel = new JPanel();
		contentPanel.setBorder(new LineBorder(new java.awt.Color(0, 0, 0), 1, false));
		getContentPane().add(contentPanel, BorderLayout.CENTER);

		GridBagLayout thisLayout = new GridBagLayout();
		setDefaultCloseOperation(WindowConstants.DISPOSE_ON_CLOSE);
		thisLayout.rowWeights = new double[] { 0.0, 0.1, 0.0 };
		thisLayout.rowHeights = new int[] { 7, 7, 7 };
		thisLayout.columnWeights = new double[] { 0.0, 0.1, 0.0, 0.1, 0.0, 0.1,	0.0 };
		thisLayout.columnWidths = new int[] { 7, 20, 7, 20, 7, 7, 7 };
		contentPanel.setLayout(thisLayout);

		pauseButton = new JButton();
		contentPanel.add(pauseButton, new GridBagConstraints(1, 1, 1, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.BOTH,
				new Insets(0, 0, 0, 0), 0, 0));
		pauseButton.setText(PAUSE_TEXT);
		pauseButton.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				try {
					if (PAUSE_TEXT.equals(pauseButton.getText())) {
						screenRecorder.stop();
						pauseButton.setText(RESUME_TEXT);
					} else {
						screenRecorder.start();
						pauseButton.setText(PAUSE_TEXT);
					}
				} catch (Exception e) {
					e.printStackTrace();
					JOptionPane.showMessageDialog(RecordingControlsFrame.this,
							e.toString(), "Error pausing/resuming recording",
							JOptionPane.ERROR_MESSAGE);
				}
			}
		});

		stopButton = new JButton();
		contentPanel.add(stopButton, new GridBagConstraints(3, 1, 1, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.BOTH,
				new Insets(0, 0, 0, 0), 0, 0));
		stopButton.setText("Stop");
		stopButton.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				
				pauseButton.setEnabled(false);
				stopButton.setEnabled(false);
				cancelButton.setEnabled(false);
				
				SwingWorker<Movie, Long> task = new SwingWorker<Movie, Long>() {

					@Override
					protected Movie doInBackground() throws Exception {
						screenRecorder.stop();
						return screenRecorder.getMovie();
					}

					@Override
					protected void done() {
						try {							
							final Movie movie = get();
							if (movie!=null) {
								if (JOptionPane.showConfirmDialog(RecordingControlsFrame.this, "Would you like to edit the movie before uploading?", "Edit movie?", JOptionPane.YES_NO_OPTION)==JOptionPane.YES_OPTION) {
									new MovieEditorDialog(
											RecordingControlsFrame.this, 
											movie, 
											captureFrame.getCaptureConfig().getBackgroundProcessor(), 
											captureFrame.getCaptureConfig().getInactivityInterval(),
											captureFrame.getCaptureConfig().getImageFormat());									
								} else {
									uploadMovie(movie);
								}
							} else {
								JOptionPane.showMessageDialog(
										RecordingControlsFrame.this,
										"Recording discarded",
										"Saving recording",
										JOptionPane.INFORMATION_MESSAGE);
								RecordingControlsFrame.this.setVisible(false);
							}							
						} catch (Exception e) {
							e.printStackTrace();
							JOptionPane.showMessageDialog(
									RecordingControlsFrame.this, e.toString(),
									"Error saving recording",
									JOptionPane.ERROR_MESSAGE);
							RecordingControlsFrame.this.setVisible(false);
						}
					}

				};

				task.execute();

			}
		});

		cancelButton = new JButton();
		contentPanel.add(cancelButton, new GridBagConstraints(5, 1, 1, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.BOTH,
				new Insets(0, 0, 0, 0), 0, 0));
		cancelButton.setText("Cancel");
		cancelButton.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				if (JOptionPane.showConfirmDialog(RecordingControlsFrame.this, "Are you sure you want to discard the recording?", "Confirm discarding movie", JOptionPane.YES_NO_OPTION)==JOptionPane.YES_OPTION) {;
					try {
						screenRecorder.stop();
					} catch (Exception e) {
						e.printStackTrace();
						JOptionPane.showMessageDialog(RecordingControlsFrame.this,
								e.toString(), "Error cancelling recording",
								JOptionPane.ERROR_MESSAGE);
					} finally {
						RecordingControlsFrame.this.setVisible(false);
						captureFrame.setVisible(true);
					}
				}
			}
		});

		pack();
		this.setSize(301, 40);
		captureFrame.getCaptureConfig().setParentComponent(this);
		try {
			screenRecorder = new ScreenRecorder(captureFrame.getCaptureConfig(), captureFrame.getApplet());
		} catch (Exception e) {
			e.printStackTrace();
			JOptionPane.showMessageDialog(this, e.toString(), "Error starting recording", JOptionPane.ERROR_MESSAGE);
			setVisible(false);
		}

	}
	
	/**
	 * Asks for file name and uploads the movie.
	 * @param movie
	 */
	void uploadMovie(final Movie movie) {
		try {
			if (movie!=null) {
				
				final String fileName = JOptionPane.showInputDialog(
						RecordingControlsFrame.this, 
						"Upload as", 
						captureFrame.getApplet().getParameter("pageName")+
						"-recording-"+
								captureFrame.getDatePrefix()+
								"-" + captureFrame.nextCounter() +"."+captureFrame.getCaptureConfig().getEncoder().getFileExtension());
				
				if (fileName!=null) {
					// Uploading
					SwingWorker<Dimension, Long> task = new SwingWorker<Dimension, Long>() {
	
						@Override
						protected Dimension doInBackground() throws Exception {
							
							File savedTo = null;
							
							try {		
								// encode and upload
								File tmpFile = File.createTempFile("jCaptureMovie", "."+captureFrame.getCaptureConfig().getEncoder().getFileExtension());
								FileOutputStream out = new FileOutputStream(tmpFile);
								Dimension dimension = captureFrame.getCaptureConfig().getEncoder().encode(captureFrame.getCaptureConfig(), movie, out);
								if (dimension==null) {
									return null;
								}
								out.close();
								savedTo = tmpFile;
								
								HttpResponse iResponse = captureFrame.getApplet().post(
										RecordingControlsFrame.this, 
										new FileInputStream(tmpFile),
										tmpFile.length(),
										fileName, 
										"application/octet-stream");
								
								if (iResponse!=null) {
									System.out.println("Response status line: "+iResponse.getStatusLine());
									if (iResponse.getStatusLine().getStatusCode()!=HttpStatus.SC_OK) {
								    	errorMessage = iResponse.getStatusLine();
								    	errorTitle = "Error saving recording";
								    	return null;
									}
								}
								if (!tmpFile.delete()) {
									tmpFile.deleteOnExit();
								}
								return dimension;
							} catch (Error e) {										
								errorMessage=e.toString();
								if (savedTo!=null) {
									errorMessage=errorMessage + ",\n recording was saved to "+savedTo.getAbsolutePath();
								}
								errorTitle = "Upload error";
								e.printStackTrace();
								return null;
							}
						}
						
						private Object errorMessage;
						private String errorTitle;
						
						protected void done() {
							try {
								Dimension dimension = get();
								if (dimension!=null) {
									JSObject window = JSObject.getWindow(captureFrame.getApplet());
									String toEval = "insertAtCarret('"+captureFrame.getApplet().getParameter("edid")+"','{{:"+fileName+"?"+dimension.width+"x"+dimension.height+"|}}')";
									System.out.println("Evaluating: "+toEval);
									window.eval(toEval);
								} else {
							    	JOptionPane.showMessageDialog(
							    			RecordingControlsFrame.this,
											errorMessage, 
											errorTitle,
											JOptionPane.ERROR_MESSAGE);	
								}
							} catch (Exception e) {
								e.printStackTrace();
						    	JOptionPane.showMessageDialog(
						    			RecordingControlsFrame.this,
										e.toString(), 
										"Exception",
										JOptionPane.ERROR_MESSAGE);																			
							}
						};
						
					};
					
					task.execute();
				}
			} else {
				JOptionPane.showMessageDialog(
						RecordingControlsFrame.this,
						"Recording discarded",
						"Saving recording",
						JOptionPane.INFORMATION_MESSAGE);
			}		
		} finally {
			RecordingControlsFrame.this.setVisible(false);																																						
		}
	}

}
