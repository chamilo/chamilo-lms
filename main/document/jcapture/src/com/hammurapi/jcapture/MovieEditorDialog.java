package com.hammurapi.jcapture;

import java.awt.AlphaComposite;
import java.awt.BorderLayout;
import java.awt.Color;
import java.awt.Component;
import java.awt.Dimension;
import java.awt.Graphics;
import java.awt.Graphics2D;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Image;
import java.awt.Insets;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.RenderingHints;
import java.awt.Toolkit;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.awt.event.WindowAdapter;
import java.awt.event.WindowEvent;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.lang.ref.Reference;
import java.lang.ref.SoftReference;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.concurrent.Executor;

import javax.sound.sampled.AudioFormat;
import javax.sound.sampled.AudioInputStream;
import javax.sound.sampled.AudioSystem;
import javax.sound.sampled.DataLine;
import javax.sound.sampled.SourceDataLine;
import javax.swing.AbstractAction;
import javax.swing.Action;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JCheckBoxMenuItem;
import javax.swing.JComponent;
import javax.swing.JFrame;
import javax.swing.JMenuItem;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JPopupMenu;
import javax.swing.JScrollPane;
import javax.swing.JTable;
import javax.swing.JToolTip;
import javax.swing.ListSelectionModel;
import javax.swing.ProgressMonitor;
import javax.swing.SwingWorker;
import javax.swing.Timer;
import javax.swing.event.PopupMenuEvent;
import javax.swing.event.PopupMenuListener;
import javax.swing.table.DefaultTableModel;
import javax.swing.table.TableCellRenderer;
import javax.swing.table.TableModel;

import com.hammurapi.jcapture.ShapeImpl.ImageImpl;
import com.hammurapi.jcapture.VideoEncoder.Fragment;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.ImageReference;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.ShapeContent;

public class MovieEditorDialog extends javax.swing.JDialog {
	
	private static final double DECIBELS_PER_PIXEL = 2.0;
	private static final double NORMALIZED_LEVEL = 0.95;
	private static final int AUDIO_CELL_HEIGHT = 50;
	private static final int MEDIAN = AUDIO_CELL_HEIGHT/2;
	int minCellDimension = 10;
	int minToolTipImageDimension = 150;
	
	int splashIndex = -1;
	
	double coeff;
	
	private static Color INACTIVE_COLOR = new Color(230, 230, 230);
	private static Color ACTIVE_COLOR = Color.white;
	private static Color SELECTED_COLOR = new Color(0, 0, 255, 70);
	private static Color FOCUSED_COLOR = new Color(0, 0, 255, 100);
	private static Color PLAYING_COLOR = new Color(255, 0, 0, 100);

	private static Color SPLASH_COLOR = new Color(0, 255, 0, 127);	
	
	private static Color SOUND_COLOR = new Color(0, 0, 127);
	private static Color DELETED_SOUND_COLOR = new Color(100, 100, 100);
	
	private JButton saveButton;
	private JPanel contentPanel;
	private JScrollPane timeLineScrollPane;
	private JCheckBox normalizeVolumeCheckBox;
	private JPanel frameCanvas;
	JTable timeLineTable;
	private JButton discardButton;
	private int focusColumn = 0;
	private int playingColumn = -1;
	private Image mouseImage;
	private double maxVolume = -1;
	FrameEntry[] frameEntries;
	int cellWidth;
	int cellHeight;
	int toolTipImageWidth;
	int toolTipImageHeight;
	boolean hasAudio;
	private Movie movie;
	
	int numChannels;
	int validBits;
	long sampleRate;
	Timer[] playTimera = {null};
	private Executor backgroundProcessor;
	private double inactivityInterval;
	private String imageFormat;
	
	private class FrameEntry {
		
		// Not null for first frames in fragments indicating that 
		// Indicating that it's time to open a new audio file.
		File audioFile;
		
		boolean mouseMoved;
		
		// Scaled samples for painting - not real ones.
		// idx, {min, max}
		double[] audioSamples;

		// Number of real samples falling to this frame.
		int audioSamplesInFrame;
		
		boolean isDeleted;
		
		Reference<BufferedImage> toolTipImageRef;
		
		Reference<BufferedImage> frameImageRef;
		
		// row, selected, focus
		private JPanel[][][] canvases = {
				{ {new FrameCellCanvas(false, false), new FrameCellCanvas(false, true)}, {new FrameCellCanvas(true, false), new FrameCellCanvas(true, true)} },
				{ {new AudioCellCanvas(false, false), new AudioCellCanvas(false, true)}, {new AudioCellCanvas(true, false), new AudioCellCanvas(true, true)} }
		};
		
		class CellCanvas extends JPanel {
			
			boolean selected;
			boolean hasFocus;

			CellCanvas(boolean selected, boolean hasFocus) {
				this.selected = selected;
				this.hasFocus = hasFocus;
			}
			
		}
		
		class FrameCellCanvas extends CellCanvas {

			FrameCellCanvas(boolean selected, boolean hasFocus) {
				super(selected, hasFocus);
			}
			
			@Override
			public void paintComponent(Graphics g) {
				super.paintComponent(g);
				paintFrame(this, g, selected, hasFocus);
			}
			
		}
		
		class AudioCellCanvas extends CellCanvas {

			AudioCellCanvas(boolean selected, boolean hasFocus) {
				super(selected, hasFocus);
			}
			
			@Override
			public void paintComponent(Graphics g) {
				super.paintComponent(g);
				paintAudio(this, g, selected, hasFocus);
			}
			
		}
		
		int idx;
		int delta;
		
		Frame frame;
		
		BufferedImage getToolTipImage() throws IOException {
			BufferedImage ret = toolTipImageRef==null ? null : toolTipImageRef.get();
			if (ret == null) {
				BufferedImage image = getImage();
		    	ret = new BufferedImage(toolTipImageWidth, toolTipImageHeight, image.getType());
		    	Graphics2D g = ret.createGraphics();
		    	g.setComposite(AlphaComposite.Src);
		    	g.setRenderingHint(RenderingHints.KEY_INTERPOLATION,RenderingHints.VALUE_INTERPOLATION_BILINEAR);
		    	g.setRenderingHint(RenderingHints.KEY_RENDERING,RenderingHints.VALUE_RENDER_QUALITY);
		    	g.setRenderingHint(RenderingHints.KEY_ANTIALIASING,RenderingHints.VALUE_ANTIALIAS_ON);
		    	g.drawImage(image, 0, 0, ret.getWidth(), ret.getHeight(), null);
		    	g.dispose();
				toolTipImageRef = new SoftReference<BufferedImage>(ret);
			}
			return ret;
			
		}
		
		void paintFrame(FrameCellCanvas frameCellCanvas, Graphics g, boolean selected, boolean hasFocus) {
			g.setColor(frame.isActive() ? ACTIVE_COLOR : INACTIVE_COLOR);
			g.fillRect(0, 0, frameCellCanvas.getWidth(), frameCellCanvas.getHeight());
			
			if (idx==splashIndex) {
				g.setColor(SPLASH_COLOR);
				g.fillRect(1, 1, frameCellCanvas.getWidth()-2, frameCellCanvas.getHeight()-2);
				
			}
			
			if (frame.getMousePointer()!=null) {
				int mx = (int) (frame.getMousePointer().getX()*(frameCellCanvas.getWidth()-3)/frame.getSize().getWidth())+1;
				int my = (int) (frame.getMousePointer().getY()*(frameCellCanvas.getHeight()-3)/frame.getSize().getHeight())+1;
				g.setColor(mouseMoved ? Color.BLACK : Color.GRAY);
				g.fillRect(mx, my, 2, 2);
			}
			
			if (isDeleted) {
				g.setColor(Color.RED);
				g.drawLine(2, 2, frameCellCanvas.getWidth()-2, frameCellCanvas.getHeight()-2);
				g.drawLine(frameCellCanvas.getWidth()-2, 2, 2, frameCellCanvas.getHeight()-2);
			}
			
			decorate(frameCellCanvas, g, selected, hasFocus);
		}

		void paintAudio(AudioCellCanvas audioCellCanvas, Graphics g, boolean selected, boolean hasFocus) {
			g.setColor(frame.isActive() ? ACTIVE_COLOR : INACTIVE_COLOR);
			g.fillRect(0, 0, audioCellCanvas.getWidth(), audioCellCanvas.getHeight());
			
			if (audioSamples!=null) {
				for (int i = 0; i<audioCellCanvas.getWidth(); ++i) {
					g.setColor(isDeleted ? DELETED_SOUND_COLOR : SOUND_COLOR);
					int volume = (int) (20.0*Math.log10(coeff*audioSamples[Math.min(i, audioSamples.length-1)]+1)/DECIBELS_PER_PIXEL);
					g.drawLine(i, MEDIAN - volume, i,  MEDIAN + volume);
				}
			}
			
			decorate(audioCellCanvas, g, selected, hasFocus);
		}
		
		private void decorate(JComponent component, Graphics g, boolean selected, boolean hasFocus) {
			if (idx==playingColumn) {
				g.setColor(PLAYING_COLOR);
				Rectangle bounds = g.getClipBounds();
				g.fillRect(bounds.x, bounds.y, bounds.width, bounds.height);
			} else if (hasFocus) {
				g.setColor(FOCUSED_COLOR);
				Rectangle bounds = g.getClipBounds();
				g.fillRect(bounds.x, bounds.y, bounds.width, bounds.height);
			} else if (selected) {
				g.setColor(SELECTED_COLOR);
				Rectangle bounds = g.getClipBounds();
				g.fillRect(bounds.x, bounds.y, bounds.width, bounds.height);
			}
		}
		
		BufferedImage getImage() throws IOException {
			BufferedImage ret = frameImageRef==null ? null : frameImageRef.get();
			if (ret == null) {
				int startIdx = idx;
				while (startIdx>0 && !coversEverything(startIdx)) {
					--startIdx;
				}
				int deltaArea = 0;
				ret = new BufferedImage(frame.getSize().width, frame.getSize().height, shapeImage(frameEntries[startIdx].frame.getShapes().get(0)).getType());
				Graphics2D g = ret.createGraphics();
				for (int i=startIdx; i<=idx; ++i) {
					for (Shape shape: frameEntries[i].frame.getShapes()) {
						BufferedImage si = shapeImage(shape);
						g.drawImage(si, shape.getLocation().x, shape.getLocation().y, null);
						if (i==idx) {
							deltaArea+=si.getWidth()*si.getHeight();
						}
					}
				}
				delta = (int) (100.0*deltaArea/(frame.getSize().width * frame.getSize().height));
				if (frame.getMousePointer()!=null) {
					g.drawImage(mouseImage, frame.getMousePointer().x, frame.getMousePointer().y, null);
				}
				frameImageRef = new SoftReference<BufferedImage>(ret);
			}
			return ret;
		}

		private BufferedImage shapeImage(Shape shape) throws IOException {
			ShapeContent shapeContent = shape.getContent();
			if (shapeContent instanceof ImageReference) {
				return ((ImageReference) shapeContent).getImage().getImage().getImage();
			}
			return ((com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.Image) shapeContent).getImage().getImage();
		}

		boolean coversEverything(int entryIdx) {
			for (Shape shape: frameEntries[entryIdx].frame.getShapes()) {
				if (shape.getContent().coversEverything()) {
					return true;
				}
			}
			return false;
		}

		public Component getCellRendererComponent(int row, boolean isSelected, boolean hasFocus) {
			return canvases[row][isSelected ? 1 : 0][hasFocus ? 1 : 0];
		}
		
	}
	
	public MovieEditorDialog(
			final JFrame frame, 
			final Movie movie, 
			final Executor backgroundProcessor, 
			double inactivityInterval, 
			String imageFormat) {
		
		super(frame, "Movie editor ("+movie+")");
		frame.setAlwaysOnTop(false);
		frame.setVisible(false);
		
		this.movie = movie;
		this.backgroundProcessor = backgroundProcessor;
		this.inactivityInterval = inactivityInterval;
		this.imageFormat = imageFormat;
		
		setModal(true);
		setIconImage(frame.getIconImage());
		
		mouseImage = Toolkit.getDefaultToolkit().getImage(getClass().getResource("mouse.png"));
		
		double aspectRatio = (double) movie.getFrameDimension().getWidth()/(double) movie.getFrameDimension().getHeight();
		
		if (aspectRatio>1) {
			cellHeight = minCellDimension;
			cellWidth = (int) Math.round(aspectRatio*cellHeight);
			
			toolTipImageHeight = minToolTipImageDimension;
			toolTipImageWidth = (int) Math.round(aspectRatio*toolTipImageHeight);
		} else {
			cellWidth = minCellDimension;
			cellHeight = (int) Math.round((double) cellWidth/aspectRatio);
			
			toolTipImageWidth = minToolTipImageDimension;
			toolTipImageHeight = (int) Math.round((double) toolTipImageWidth/aspectRatio);
		}
		
		setDefaultCloseOperation(DO_NOTHING_ON_CLOSE);
		
		addWindowListener(new WindowAdapter() {
            public void windowClosing(WindowEvent e) {
                int confirmed = JOptionPane.showConfirmDialog(null,
                                "Are you sure you want to exit and discard the movie?", "User Confirmation",
                                JOptionPane.YES_NO_OPTION);
                if (confirmed == JOptionPane.YES_OPTION) {
                	dispose();
					getOwner().setVisible(false);
                }
            }
		});
		
		SwingWorker<Boolean, Long> loader = new SwingWorker<Boolean, Long>() {

			@Override
			protected Boolean doInBackground() throws Exception {
				int totalFrames = 0;
				for (Fragment fr: movie.getFragments()) {
					if (fr.getAudio()!=null) {
						hasAudio = true;
					}
					totalFrames+=fr.getFrames().size();
				}
				
				ProgressMonitor progressMonitor = new ProgressMonitor(frame, "Loading frames", "Loading movie frames", 0, totalFrames);
				
				try {
					frameEntries = new FrameEntry[totalFrames];
					int idx = 0;
					double audioSamplesPerFrame = -1;
					Point prevMouse = null;
					for (Fragment fr: movie.getFragments()) {
						WavFile wavFile = fr.getAudio()==null ? null : WavFile.openWavFile(fr.getAudio());
						if (wavFile!=null) {
							audioSamplesPerFrame = wavFile.getSampleRate()/movie.getFramesPerSecond();	
							numChannels = wavFile.getNumChannels();
							validBits = wavFile.getValidBits();
							sampleRate = wavFile.getSampleRate();
						}
						int audioFramesRead = 0;
						int framePosition = 0;
						for (Frame frm: fr.getFrames()) {
							if (progressMonitor.isCanceled()) {
								return false;
							}
							frameEntries[idx] = new FrameEntry();
							frameEntries[idx].frame = frm;
							frameEntries[idx].idx = idx;
							if (frm.getMousePointer()!=null) {
								frameEntries[idx].mouseMoved = !frm.getMousePointer().equals(prevMouse);
							}
							prevMouse = frm.getMousePointer();
							
							if (framePosition == 0) {
								frameEntries[idx].audioFile = fr.getAudio();
							}
							
							if (wavFile!=null && wavFile.getFramesRemaining()>0) {
								frameEntries[idx].audioSamplesInFrame = (int) ((framePosition+1)*audioSamplesPerFrame-audioFramesRead);
								frameEntries[idx].audioSamples = new double[cellWidth];
								double[][] sampleBuffer = new double[wavFile.getNumChannels()][frameEntries[idx].audioSamplesInFrame];
								frameEntries[idx].audioSamplesInFrame=wavFile.readFrames(sampleBuffer, frameEntries[idx].audioSamplesInFrame);								
								audioFramesRead+=frameEntries[idx].audioSamplesInFrame;
								for (int i=0; i<frameEntries[idx].audioSamplesInFrame; ++i) {
									for (int ch=0; ch<wavFile.getNumChannels(); ++ch) {
										maxVolume = Math.max(maxVolume, Math.abs(sampleBuffer[ch][i]));
										int asidx = i*cellWidth/frameEntries[idx].audioSamplesInFrame;
										frameEntries[idx].audioSamples[asidx] = Math.max(Math.abs(sampleBuffer[ch][i]), frameEntries[idx].audioSamples[asidx]);
									}
								}																
							}
							
							++idx;
							++framePosition;
							progressMonitor.setProgress(idx);
						}
						if (wavFile!=null) {
							wavFile.close();
						}
						
					}
					
					coeff = Math.pow(10.0, DECIBELS_PER_PIXEL*(MEDIAN-1)/20.0)/maxVolume;
					
					return true;
				} finally {
					progressMonitor.close();
				}
			}
			
			protected void done() {
				try {
					if (get()) {
						buildUI();
						setLocationRelativeTo(frame);
						setVisible(true);
					} else {
				    	JOptionPane.showMessageDialog(
				    			MovieEditorDialog.this,
								"Loading operation was cancelled", 
								"Loading cancelled",
								JOptionPane.ERROR_MESSAGE);				    	
					}
				} catch (Exception e) {
					e.printStackTrace();
			    	JOptionPane.showMessageDialog(
			    			MovieEditorDialog.this,
							e.toString(), 
							"Error loading frames",
							JOptionPane.ERROR_MESSAGE);				    	
				}
				
			};
			
		};
		
		loader.execute();
		
	}
						
	void buildUI() {
		BorderLayout thisLayout = new BorderLayout();
		getContentPane().setLayout(thisLayout);

		contentPanel = new JPanel();
		GridBagLayout contentPanelLayout = new GridBagLayout();
		getContentPane().add(contentPanel, BorderLayout.CENTER);
		contentPanelLayout.rowWeights = new double[] { 0.1, 0.0, 0.0, 0.0, 0.0,	0.0 };
		contentPanelLayout.rowHeights = new int[] { movie.getFrameDimension().height, 7, cellHeight+(hasAudio? 23 + AUDIO_CELL_HEIGHT : 22), 7, 7, 7 };
		contentPanelLayout.columnWeights = new double[] { 0.1, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0 };
		contentPanelLayout.columnWidths = new int[] { 7, 7, 7, 7, 7, 7, 7 };
		contentPanel.setLayout(contentPanelLayout);

		saveButton = new JButton("Save");
		contentPanel.add(saveButton, new GridBagConstraints(3, 4, 1, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		saveButton.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				SwingWorker<Movie, Long> encoder = new SwingWorker<Movie, Long>() {

					@Override
					protected Movie doInBackground() throws Exception {
						ProgressMonitor progressMonitor = new ProgressMonitor(MovieEditorDialog.this, "Saving movie", "Composing movie", 0, frameEntries.length);

						List<Frame> newFrames = new ArrayList<Frame>();
						
						if (splashIndex!=-1) {
							newFrames.add(new FrameImpl(
									Collections.singletonList((Shape) new ShapeImpl(new Point(0,0), new ImageImpl(new MappedImage(frameEntries[splashIndex].getImage(), imageFormat, null), true))) , 
									frameEntries[splashIndex].frame.getMousePointer(), 
									frameEntries[splashIndex].frame.getSize(), 
									false));
						}
						
						File newAudio = hasAudio ? File.createTempFile("jCaptureAudioSink", ".wav") : null;
												
						long numFrames=0;						
						for (FrameEntry fe: frameEntries) {
							if (!fe.isDeleted) {
								numFrames+=fe.audioSamplesInFrame;
							}
						}
						WavFile newWavFile = newAudio==null ? null : WavFile.newWavFile(newAudio, numChannels, numFrames, validBits, sampleRate);						
						
						File currentAudio = null;
						WavFile currentWav = null;
						
						for (int i=0; i<frameEntries.length; ++i) {
							if (frameEntries[i].audioFile!=null) {
								if (currentWav!=null) {
									currentWav.close();
								}
								if (currentAudio!=null) {
									if (!currentAudio.delete()) {
										currentAudio.deleteOnExit();
									}
								}

								currentAudio = frameEntries[i].audioFile;
								currentWav = WavFile.openWavFile(currentAudio);
							}
							
							if (currentWav!=null) {
								if (normalizeVolumeCheckBox.isSelected()) {
									double[][] buf = new double[numChannels][frameEntries[i].audioSamplesInFrame];
									int read = currentWav.readFrames(buf, frameEntries[i].audioSamplesInFrame);
									if (read>0 && !frameEntries[i].isDeleted) {
										// Normalization
										for (double[] ch: buf) {
											for (int j=0; j<ch.length; ++j) {
												ch[j] = ch[j] * NORMALIZED_LEVEL / maxVolume; 
											}
										}
										newWavFile.writeFrames(buf, read);
									}
								} else {
									long[][] buf = new long[numChannels][frameEntries[i].audioSamplesInFrame];
									int read = currentWav.readFrames(buf, frameEntries[i].audioSamplesInFrame);
									if (read>0 && !frameEntries[i].isDeleted) {
										newWavFile.writeFrames(buf, read);
									}									
								}
							}
							
							if (frameEntries[i].isDeleted) {
								if (i<frameEntries.length-1) {
									((FrameImpl) frameEntries[i+1].frame).merge(frameEntries[i].frame);
								}
							} else {
								newFrames.add(frameEntries[i].frame);
							}
							
							progressMonitor.setProgress(i);
							if (progressMonitor.isCanceled()) {
								if (currentWav!=null) {
									currentWav.close();
								}
								if (currentAudio!=null) {
									if (!currentAudio.delete()) {
										currentAudio.deleteOnExit();
									}
								}
								if (newWavFile!=null) {
									newWavFile.close();
								}
								if (newAudio!=null) {
									if (!newAudio.delete()) {
										newAudio.deleteOnExit();
									}
								}
								return null;								
							}
						}
						
						if (currentWav!=null) {
							currentWav.close();
						}
						if (currentAudio!=null) {
							if (!currentAudio.delete()) {
								currentAudio.deleteOnExit();
							}
						}
						
						if (newWavFile!=null) {
							newWavFile.close();
						}
						
						return new Movie(movie.getFrameDimension(), movie.getFramesPerSecond(), Collections.singletonList((Fragment) new FragmentImpl(newFrames, newAudio)), movie);
					}
					
					@Override
					protected void done() {
						try {
							MovieEditorDialog.this.setVisible(false);
							((RecordingControlsFrame) getOwner()).uploadMovie(get());
							MovieEditorDialog.this.dispose();
						} catch (Exception e) {
							e.printStackTrace();
							JOptionPane.showMessageDialog(
									MovieEditorDialog.this, e.toString(),
									"Error saving recording",
									JOptionPane.ERROR_MESSAGE);
							MovieEditorDialog.this.setVisible(false);		
							MovieEditorDialog.this.getOwner().setVisible(false);
						}
					}
					
				};
				
				encoder.execute();
			}
		});

		discardButton = new JButton("Discard");
		contentPanel.add(discardButton, new GridBagConstraints(5, 4, 1, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		discardButton.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				if (JOptionPane.showConfirmDialog(MovieEditorDialog.this, "Are you sure you want to discard the recording?", "Confirm discarding movie", JOptionPane.YES_NO_OPTION)==JOptionPane.YES_OPTION) {
					MovieEditorDialog.this.setVisible(false);
					MovieEditorDialog.this.dispose();
					MovieEditorDialog.this.getOwner().setVisible(false);
				}
			}
		});

		timeLineScrollPane = new JScrollPane();
		timeLineScrollPane.setPreferredSize(new Dimension(movie.getFrameDimension().width, cellHeight+(hasAudio? 23 + AUDIO_CELL_HEIGHT : 22)));
		contentPanel.add(timeLineScrollPane, new GridBagConstraints(0, 2, 7, 1,
				0.0, 0.0, GridBagConstraints.CENTER, GridBagConstraints.BOTH,
				new Insets(0, 0, 0, 0), 0, 0));

		System.out.println("Loaded "+frameEntries.length+" frames");
		TableModel timeLineTableModel = new DefaultTableModel(hasAudio ? 2 : 1, frameEntries.length) {
			
			@Override
			public boolean isCellEditable(int row, int column) {
				return false;
			}
		};
		timeLineTable = new JTable() {
			
		    @Override
			public JToolTip createToolTip() {
				Point p = getMousePosition();

				// Locate the renderer under the event location
				int hitColumnIndex = columnAtPoint(p);
				int hitRowIndex = rowAtPoint(p);
				
				if ((hitColumnIndex != -1) && (hitRowIndex != -1)) {
					try {
						BufferedImage toolTipImage = frameEntries[hitColumnIndex].getToolTipImage();
						return new ImageToolTip("Frame "+(hitColumnIndex+1)+", delta "+frameEntries[hitColumnIndex].delta+"%", toolTipImage);
					} catch (IOException e) {
						e.printStackTrace();
					}
				}
				return super.createToolTip();
			}
			
		};
		
		JPopupMenu popup = new JPopupMenu("Context");
		
		popup.addPopupMenuListener(new PopupMenuListener() {
			
			@Override
			public void popupMenuWillBecomeVisible(PopupMenuEvent e) {
				if (playTimera[0]!=null) {
					playTimera[0].stop();
				}				
			}
			
			@Override
			public void popupMenuWillBecomeInvisible(PopupMenuEvent e) {
				// TODO Auto-generated method stub
				
			}
			
			@Override
			public void popupMenuCanceled(PopupMenuEvent e) {
				// TODO Auto-generated method stub
				
			}
		});
		
		addDeleteFramesMenuItem(popup);				
		addUndeleteFramesMenuItem(popup);
		addRemoveInactivityMenuItem(popup);				
		
		final JCheckBoxMenuItem splashMenuItem = new JCheckBoxMenuItem("Splash");
		
		splashMenuItem.addActionListener(new ActionListener() {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				splashIndex = splashMenuItem.isSelected() ? focusColumn : -1;
				timeLineTable.repaint();				
			}
		});
		
		popup.add(splashMenuItem);
		
		popup.addPopupMenuListener(new PopupMenuListener() {
			
			@Override
			public void popupMenuWillBecomeVisible(PopupMenuEvent e) {				
				splashMenuItem.setSelected(focusColumn!=-1 && focusColumn==splashIndex);
				
			}
			
			@Override
			public void popupMenuWillBecomeInvisible(PopupMenuEvent e) {
				
			}
			
			@Override
			public void popupMenuCanceled(PopupMenuEvent e) {
				// TODO Auto-generated method stub
				
			}
		});
				
		JMenuItem playMenuItem = new JMenuItem();
		Action playAction = new AbstractAction("Play") {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				if (playTimera[0]!=null) {
					playTimera[0].stop();
					playTimera[0] = null;
				}
								
				final int range[] = {focusColumn, focusColumn};
				
				for (int i=focusColumn; i<frameEntries.length && timeLineTable.isColumnSelected(i); ++i) {
					range[1] = i;
				}
				
				for (int i=focusColumn; i>=0 && timeLineTable.isColumnSelected(i); --i) {
					range[0] = i;
				}
				
				if (range[0]==range[1]) {
					range[1]=frameEntries.length-1;
				}
				
				playingColumn = range[0];				
				
				if (hasAudio) {
					try {
						backgroundProcessor.execute(new SoundPlayer(range[0], range[1]));
					} catch (Exception ex) {
						ex.printStackTrace();
				    	JOptionPane.showMessageDialog(
				    			MovieEditorDialog.this,
								ex.toString(), 
								"Audio problem",
								JOptionPane.ERROR_MESSAGE);	
						
					}
				}
				
				playTimera[0] = new Timer((int) ((double) 1000/movie.getFramesPerSecond()), new ActionListener() {

					@Override
					public void actionPerformed(ActionEvent e) {
						while (frameEntries[playingColumn].isDeleted) {
							++playingColumn;
							if (playingColumn>range[1]) {
								((Timer) e.getSource()).stop();
								return;
							}
						}
						
						Rectangle visibleRect = timeLineTable.getVisibleRect();
						Rectangle playingRect = timeLineTable.getCellRect(0, playingColumn, true);
						if (!visibleRect.contains(playingRect)) {
							Rectangle scrollTo = new Rectangle(playingRect.x, playingRect.width, visibleRect.width-1, visibleRect.height-1);
							timeLineTable.scrollRectToVisible(scrollTo);
						}
						
						frameCanvas.repaint();
						timeLineTable.repaint();		
						
						++playingColumn;
						if (playingColumn>range[1]) {
							((Timer) e.getSource()).stop();
							return;
						}						
					}
					
					
				}) {
					@Override
					public void stop() {
						super.stop();
						playingColumn=-1;
						timeLineTable.scrollRectToVisible(timeLineTable.getCellRect(0, focusColumn, true));
						frameCanvas.repaint();
						timeLineTable.repaint();						
						playTimera[0] = null;						
					}
				};
				
				playTimera[0].start();
				synchronized (playTimera) {
					playTimera.notifyAll();
				}
			}
		};
		playMenuItem.setAction(playAction);
		
		popup.add(playMenuItem);				
		
		timeLineTable.setComponentPopupMenu(popup );
		
		timeLineTable.addMouseListener(new MouseAdapter() {
			
			@Override
			public void mouseClicked(MouseEvent e) {
				if (playTimera[0]!=null) {
					playTimera[0].stop();
				}
				
				if (e.getClickCount()==2) {
					int hitColumnIndex = timeLineTable.columnAtPoint(e.getPoint());
					if (hitColumnIndex!=-1) {
						frameEntries[hitColumnIndex].isDeleted=!frameEntries[hitColumnIndex].isDeleted;
						timeLineTable.repaint();
					}
				}
			}
		});
			
		timeLineTable.setToolTipText("Movie timeline");
		timeLineScrollPane.setViewportView(timeLineTable);
		timeLineTable.setModel(timeLineTableModel);
		timeLineTable.setRowHeight(0, cellHeight+timeLineTable.getRowMargin()*2);
		timeLineTable.setRowHeight(1, AUDIO_CELL_HEIGHT+timeLineTable.getRowMargin()*2);
		for (int i=0; i<frameEntries.length; ++i) {
			timeLineTable.getColumnModel().getColumn(i).setPreferredWidth(cellWidth);
			timeLineTable.setValueAt(frameEntries[i], 0, i);
			if (hasAudio) {
				timeLineTable.setValueAt(frameEntries[i], 1, i);
			}
		}
		timeLineTable.setAutoResizeMode(JTable.AUTO_RESIZE_OFF);
		timeLineTable.setTableHeader(null);
		timeLineTable.getSelectionModel().setSelectionMode(ListSelectionModel.MULTIPLE_INTERVAL_SELECTION);
		timeLineTable.setColumnSelectionAllowed(true);
		timeLineTable.setRowSelectionAllowed(false);

		TableCellRenderer renderer = new TableCellRenderer() {

			@Override
			public Component getTableCellRendererComponent(JTable table, Object value, final boolean isSelected, final boolean hasFocus, int row, int column) {
				
				if (hasFocus && column!=focusColumn) {
					focusColumn = column;
					frameCanvas.repaint();					
				}
				
				return frameEntries[column].getCellRendererComponent(row, isSelected, hasFocus);
			}
		};
		timeLineTable.setDefaultRenderer(Object.class, renderer);

		frameCanvas = new JPanel() {
			
			@Override
			public void paintComponent(Graphics g) {
				super.paintComponent(g);
				Rectangle bounds = getBounds();
				
				try {
					Image image = frameEntries[playingColumn==-1 ? focusColumn : playingColumn].getImage();
					double xScale = (double) bounds.width/(double) image.getWidth(null);
					double yScale = (double) bounds.height/(double) image.getHeight(null);
					double scale = Math.min(xScale, yScale);
					int scaledWidth = (int) (image.getWidth(null)*scale);
					int scaledHeight = (int) (image.getHeight(null)*scale);
					if (g instanceof Graphics2D) {
				    	((Graphics2D) g).setComposite(AlphaComposite.Src);
				    	((Graphics2D) g).setRenderingHint(RenderingHints.KEY_INTERPOLATION,RenderingHints.VALUE_INTERPOLATION_BILINEAR);
				    	((Graphics2D) g).setRenderingHint(RenderingHints.KEY_RENDERING,RenderingHints.VALUE_RENDER_QUALITY);
				    	((Graphics2D) g).setRenderingHint(RenderingHints.KEY_ANTIALIASING,RenderingHints.VALUE_ANTIALIAS_ON);
					}
					g.drawImage(image, (bounds.width-scaledWidth)/2, (bounds.height-scaledHeight)/2, scaledWidth, scaledHeight, null);
				} catch (Exception e) {
					e.printStackTrace();
					g.clearRect(0, 0, bounds.width, bounds.height);
					g.drawString(e.toString(), 10, 20);
				}
			}
			
		};
		
		frameCanvas.addMouseListener(new MouseAdapter() {
			
			@Override
			public void mouseClicked(MouseEvent e) {
				if (playTimera[0]!=null) {
					playTimera[0].stop();					
				}				
			}
		});
		
		frameCanvas.setPreferredSize(movie.getFrameDimension());
		contentPanel.add(frameCanvas, new GridBagConstraints(0, 0, 7, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.BOTH,
				new Insets(1, 1, 1, 1), 0, 0));

		normalizeVolumeCheckBox = new JCheckBox();
		contentPanel.add(normalizeVolumeCheckBox, new GridBagConstraints(1, 4,
				1, 1, 0.0, 0.0, GridBagConstraints.CENTER,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		normalizeVolumeCheckBox.setText("Normalize volume (+"+Math.round(Math.log10(NORMALIZED_LEVEL/maxVolume)*20)+" dB)");

		timeLineTable.changeSelection(0, 0, false, false);
		frameCanvas.repaint();
		
		pack();	
		
	}

	void addUndeleteFramesMenuItem(JPopupMenu popup) {
		JMenuItem unDeleteFrameMenuItem = new JMenuItem();
		Action unDeleteFrameAction = new AbstractAction("Undelete frame(s)") {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				for (int idx: timeLineTable.getSelectedColumns()) {
					frameEntries[idx].isDeleted = false;
				}
				timeLineTable.repaint();
			}
		};
		unDeleteFrameMenuItem.setAction(unDeleteFrameAction);
		
		popup.add(unDeleteFrameMenuItem);
	}

	void addDeleteFramesMenuItem(JPopupMenu popup) {
		JMenuItem deleteFrameMenuItem = new JMenuItem();
		Action deleteFrameAction = new AbstractAction("Delete frame(s)") {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				for (int idx: timeLineTable.getSelectedColumns()) {
					frameEntries[idx].isDeleted = true;
				}
				timeLineTable.repaint();
			}
		};
				
		deleteFrameMenuItem.setAction(deleteFrameAction);
		popup.add(deleteFrameMenuItem);
	}
	
	void addRemoveInactivityMenuItem(JPopupMenu popup) {
		JMenuItem removeInactivityMenuItem = new JMenuItem();
		Action deleteFrameAction = new AbstractAction("Remove inactivity") {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				String msg = "Inactivity interval";
				while (true) {
					String newVal = JOptionPane.showInputDialog(msg, String.valueOf(inactivityInterval));
					if (newVal==null) {
						return;
					}
					
					try {
						inactivityInterval = Double.parseDouble(newVal);
						if (inactivityInterval > 0) {
							break;
						}
					} catch (NumberFormatException nfe) {
						// NOP - loop
					}
					msg = "Invalid double value for inactivity interval: "+newVal+", enter valid value";					
				}
				int inactivityInFrames = (int) (inactivityInterval*movie.getFramesPerSecond());
				int lastActivity = -inactivityInFrames-1;
				for (int idx: timeLineTable.getSelectedColumns()) {
					if (!frameEntries[idx].isDeleted && frameEntries[idx].frame.isActive()) {
						lastActivity = idx;
					} else if (idx - lastActivity > inactivityInFrames && !frameEntries[idx].frame.isActive()) {
						frameEntries[idx].isDeleted = true;
					}
				}
				timeLineTable.repaint();
			}
		};
				
		removeInactivityMenuItem.setAction(deleteFrameAction);
		popup.add(removeInactivityMenuItem);
	}
	
	private class SoundPlayer implements Runnable {

	    private final int BUFFER_SIZE;
	    private AudioInputStream audioStream;
	    private SourceDataLine sourceLine;
		private File audioFile;
	    
	    public SoundPlayer(int start, int end) throws Exception {
	    	
			audioFile = hasAudio ? File.createTempFile("jCaptureRangeAudio", ".wav") : null;
			
			BUFFER_SIZE = (int) ((double) numChannels*sampleRate*validBits/(movie.getFramesPerSecond()*8)); // 1 frame buffer.
									
			long numFrames=0;						
			for (int i = start; i<=end; ++i) {
				if (!frameEntries[i].isDeleted) {
					numFrames+=frameEntries[i].audioSamplesInFrame;
				}
			}
			WavFile newWavFile = audioFile==null ? null : WavFile.newWavFile(audioFile, numChannels, numFrames, validBits, sampleRate);						
			
			File currentAudio = null;
			WavFile currentWav = null;
			
			for (int i=0; i<=end; ++i) {
				if (frameEntries[i].audioFile!=null) {
					if (currentWav!=null) {
						currentWav.close();
					}

					currentAudio = frameEntries[i].audioFile;
					currentWav = WavFile.openWavFile(currentAudio);
				}
				
				if (currentWav!=null) {
					if (normalizeVolumeCheckBox!=null && normalizeVolumeCheckBox.isSelected()) {
						double[][] buf = new double[numChannels][frameEntries[i].audioSamplesInFrame];
						int read = currentWav.readFrames(buf, frameEntries[i].audioSamplesInFrame);
						if (read>0 && i>=start && !frameEntries[i].isDeleted) {
							// Normalization
							for (double[] ch: buf) {
								for (int j=0; j<ch.length; ++j) {
									ch[j] = ch[j] * NORMALIZED_LEVEL / maxVolume; 
								}
							}
							newWavFile.writeFrames(buf, read);
						}
					} else {
						long[][] buf = new long[numChannels][frameEntries[i].audioSamplesInFrame];
						int read = currentWav.readFrames(buf, frameEntries[i].audioSamplesInFrame);
						if (read>0 && i>=start && !frameEntries[i].isDeleted) {
							newWavFile.writeFrames(buf, read);
						}									
					}
				}				
			}
			
			if (currentWav!=null) {
				currentWav.close();
			}
			if (newWavFile!=null) {
				newWavFile.close();
			}
			
			if (audioFile!=null) {
	            audioStream = AudioSystem.getAudioInputStream(audioFile);
		        AudioFormat audioFormat = audioStream.getFormat();
		        DataLine.Info info = new DataLine.Info(SourceDataLine.class, audioFormat);
	            sourceLine = (SourceDataLine) AudioSystem.getLine(info);
	            sourceLine.open(audioFormat);
			}
		}
	    
	    @Override
	    public void run() {
	    	try {
		        sourceLine.start();
		        synchronized (playTimera) {
			        if (playTimera[0] == null) {
			        	playTimera.wait(100);
			        }
		        }
		        try {
			        byte[] buf = new byte[BUFFER_SIZE];
			        int l;
			        while (playTimera[0]!=null && (l=audioStream.read(buf))!=-1) {
			        	sourceLine.write(buf, 0, l);
			        }
		        } finally {
		        	audioStream.close();
			        sourceLine.drain();
			        sourceLine.close();
			        if (!audioFile.delete()) {
			        	audioFile.deleteOnExit();
			        }
		        }
	    	} catch (Exception e) {
	    		e.printStackTrace();
	    	}
	    }
	    
	}

}
