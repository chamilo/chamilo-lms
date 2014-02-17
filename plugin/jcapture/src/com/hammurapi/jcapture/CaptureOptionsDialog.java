package com.hammurapi.jcapture;

import java.awt.BorderLayout;
import java.awt.Component;
import java.awt.GridBagConstraints;
import java.awt.GridBagLayout;
import java.awt.Insets;
import java.awt.event.ActionEvent;
import java.awt.event.ActionListener;
import java.util.List;

import javax.sound.sampled.AudioFormat;
import javax.sound.sampled.AudioSystem;
import javax.sound.sampled.DataLine;
import javax.sound.sampled.Mixer;
import javax.sound.sampled.TargetDataLine;
import javax.swing.BorderFactory;
import javax.swing.ButtonGroup;
import javax.swing.ComboBoxModel;
import javax.swing.DefaultComboBoxModel;
import javax.swing.JButton;
import javax.swing.JCheckBox;
import javax.swing.JComboBox;
import javax.swing.JLabel;
import javax.swing.JOptionPane;
import javax.swing.JPanel;
import javax.swing.JRadioButton;
import javax.swing.JTabbedPane;
import javax.swing.JTextField;
import javax.swing.SwingConstants;

public class CaptureOptionsDialog extends javax.swing.JDialog {
	private JRadioButton sampleSize16Button;
	private JTextField timeLineScaleTextField;
	private JCheckBox toobarCheckBox;
	private JCheckBox videoBorderCheckBox;
	private JTextField screenScaleTextField;
	private JLabel timelineScalingLabel;
	private JLabel screenScalingLabel;
	private JTextField fpsTextField;
	private JLabel fpsLabel;
	private ButtonGroup sampleSizeButtonGroup;
	private JComboBox<String> sampleRateComboBox;
	private JRadioButton sampleSize8Button;
	private JTextField inactivityIntervalTextField;
	private JLabel inactivityIntervalLabel;
	private JCheckBox inactivityCheckBox;
	private JPanel inactivityPanel;
	private JPanel scalingPanel;
	private JCheckBox stereoCheckBox;
	private JLabel sampleSizeLabel;
	private JTabbedPane recordingSettingsPane;
	private JCheckBox recordSoundCheckBox;
	private JLabel sampleRateLabel;
	private JComboBox<String> soundLineComboBox;
	private JLabel soundSourceLabel;
	private JPanel audioSettingsPanel;
	private JPanel videoSettingsPanel;
	private JButton cancelButton;
	private JButton okButton;
	private JPanel recordPanel;
	private JComboBox<VideoEncoder> encodersComboBox;
	private JTextField mp3Text;

	public CaptureOptionsDialog(final CaptureFrame owner) {
		super(owner);
		setDefaultCloseOperation(DISPOSE_ON_CLOSE);
		BorderLayout thisLayout = new BorderLayout();
		this.setLayout(thisLayout);
		this.setPreferredSize(new java.awt.Dimension(333, 186));

		recordPanel = new JPanel();
		this.add(recordPanel);
		GridBagLayout recordPanelLayout = new GridBagLayout();
		recordPanelLayout.rowWeights = new double[] { 0.1, 0.0, 0.0, 0.0 };
		recordPanelLayout.rowHeights = new int[] { 7, 7, 20, 7 };
		recordPanelLayout.columnWeights = new double[] { 0.1, 0.0, 0.0, 0.0, 0.0 };
		recordPanelLayout.columnWidths = new int[] { 20, 7, 7, 7, 7 };
		recordPanel.setLayout(recordPanelLayout);
		recordPanel.setPreferredSize(new java.awt.Dimension(335, 297));

		okButton = new JButton();
		recordPanel.add(okButton, new GridBagConstraints(1, 2, 1, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		okButton.setText("OK");
		okButton.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				try {
					if (recordSoundCheckBox.isSelected()) {
						owner.getCaptureConfig().setAudioFormat(audioFormat);
						owner.getCaptureConfig().setMixerName((String) soundLineComboBox.getSelectedItem());
					} else {
						owner.getCaptureConfig().setRemoveInactivity(inactivityCheckBox.isSelected());
						if (owner.getCaptureConfig().isRemoveInactivity()) {
							owner.getCaptureConfig().setInactivityInterval(Double.parseDouble(inactivityIntervalTextField.getText()));
						}
					}

					owner.getCaptureConfig().setLoop(getLoopCheckBox().isSelected());
					owner.getCaptureConfig().setPlay(getPlayCheckBox().isSelected());
					owner.getCaptureConfig().setMouse(getMouseCheckBox().isSelected());
					owner.getCaptureConfig().setSound(recordSoundCheckBox.isSelected());
					owner.getCaptureConfig().setImageFormat(getImageFormatTextField().getText().trim());
					owner.getCaptureConfig().setBorder(videoBorderCheckBox.isSelected());
					owner.getCaptureConfig().setFramesPerSecond(Float.parseFloat(fpsTextField.getText()));
					owner.getCaptureConfig().setScreenScale(Double.parseDouble(screenScaleTextField.getText()) / 100.0);
					owner.getCaptureConfig().setSpeedScale((float) (Float.parseFloat(timeLineScaleTextField.getText()) / 100.0));
					owner.getCaptureConfig().setToolBar(toobarCheckBox.isSelected());
					owner.getApplet().storeConfig(owner.getCaptureConfig().store());
					owner.getCaptureConfig().setMp3command(mp3Text.getText());
					owner.getCaptureConfig().setEncoder((VideoEncoder) encodersComboBox.getSelectedItem());
					owner.setRecordButtonState();
					CaptureOptionsDialog.this.setVisible(false);
				} catch (Exception e) {
					e.printStackTrace();
					JOptionPane.showMessageDialog(CaptureOptionsDialog.this,
							e.toString(), "Error in configuration parameters",
							JOptionPane.ERROR_MESSAGE);
				}
			}
		});

		cancelButton = new JButton();
		recordPanel.add(cancelButton, new GridBagConstraints(3, 2, 1, 1, 0.0,
				0.0, GridBagConstraints.CENTER, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		cancelButton.setText("Cancel");
		cancelButton.addActionListener(new ActionListener() {
			
			@Override
			public void actionPerformed(ActionEvent e) {
				CaptureOptionsDialog.this.setVisible(false);
			}
		});

		recordingSettingsPane = new JTabbedPane();
		recordPanel.add(recordingSettingsPane, new GridBagConstraints(0, 0, 5,
				1, 0.0, 0.0, GridBagConstraints.CENTER,
				GridBagConstraints.BOTH, new Insets(0, 0, 0, 0), 0, 0));

		videoSettingsPanel = new JPanel();
		GridBagLayout videoSettingsPanelLayout = new GridBagLayout();
		recordingSettingsPane.addTab("Video", null, videoSettingsPanel, null);
		videoSettingsPanel.setPreferredSize(new java.awt.Dimension(112, 207));
		videoSettingsPanelLayout.rowWeights = new double[] { 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.1 };
		videoSettingsPanelLayout.rowHeights = new int[] { 7, 7, 7, 7, 7, 7, 7, 20 };
		videoSettingsPanelLayout.columnWeights = new double[] { 0.0, 0.0, 0.0,
				0.0, 0.0, 0.0, 0.0, 0.0, 0.1 };
		videoSettingsPanelLayout.columnWidths = new int[] { 115, 7, 40, 7, 20,
				7, 20, 7, 20 };
		videoSettingsPanel.setLayout(videoSettingsPanelLayout);

		scalingPanel = new JPanel();
		GridBagLayout scalingPanelLayout = new GridBagLayout();
		videoSettingsPanel.add(scalingPanel, new GridBagConstraints(0, 6, 1, 1,
				0.0, 0.0, GridBagConstraints.CENTER, GridBagConstraints.BOTH,
				new Insets(0, 0, 0, 0), 0, 0));
		scalingPanel.setBorder(BorderFactory.createTitledBorder("Scaling (%)"));
		scalingPanelLayout.rowWeights = new double[] { 0.1, 0.0, 0.1 };
		scalingPanelLayout.rowHeights = new int[] { 7, 7, 7 };
		scalingPanelLayout.columnWeights = new double[] { 0.0, 0.0, 0.1 };
		scalingPanelLayout.columnWidths = new int[] { 7, 7, 7 };
		scalingPanel.setLayout(scalingPanelLayout);
		scalingPanel.add(getScreenScalingLabel(), new GridBagConstraints(0, 0,
				1, 1, 0.0, 0.0, GridBagConstraints.EAST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		scalingPanel.add(getTimelineScalingLabel(), new GridBagConstraints(0,
				2, 1, 1, 0.0, 0.0, GridBagConstraints.EAST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		scalingPanel.add(getScreenScaleTextField(), new GridBagConstraints(2,
				0, 1, 1, 0.0, 0.0, GridBagConstraints.CENTER,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));
		scalingPanel.add(getTimeLineScaleTextField(), new GridBagConstraints(2,
				2, 1, 1, 0.0, 0.0, GridBagConstraints.CENTER,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));

		inactivityPanel = new JPanel();
		GridBagLayout inactivityPanelLayout = new GridBagLayout();
		videoSettingsPanel.add(inactivityPanel, new GridBagConstraints(2, 6, 8,
				1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.VERTICAL, new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getFpsLabel(), new GridBagConstraints(0, 0, 1,
				1, 0.0, 0.0, GridBagConstraints.EAST, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getFpsTextField(), new GridBagConstraints(2, 0,
				1, 1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));
		
		videoSettingsPanel.add(getImageFormatLabel(), new GridBagConstraints(0, 2, 1,
				1, 0.0, 0.0, GridBagConstraints.EAST, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getImageFormatTextField(), new GridBagConstraints(2, 2,
				1, 1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));
		
		videoSettingsPanel.add(getEncoderLabel(), new GridBagConstraints(0, 4, 1,
				1, 0.0, 0.0, GridBagConstraints.EAST, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getEncoderComboBox(), new GridBagConstraints(2, 4,
				6, 1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));
		
		videoSettingsPanel.add(getVideoBorderCheckBox(),
				new GridBagConstraints(4, 0, 1, 1, 0.0, 0.0,
						GridBagConstraints.WEST, GridBagConstraints.NONE,
						new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getLoopCheckBox(),
				new GridBagConstraints(6, 2, 1, 1, 0.0, 0.0,
						GridBagConstraints.WEST, GridBagConstraints.NONE,
						new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getPlayCheckBox(),
				new GridBagConstraints(8, 2, 1, 1, 0.0, 0.0,
						GridBagConstraints.WEST, GridBagConstraints.NONE,
						new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getMouseCheckBox(),
				new GridBagConstraints(4, 2, 1, 1, 0.0, 0.0,
						GridBagConstraints.WEST, GridBagConstraints.NONE,
						new Insets(0, 0, 0, 0), 0, 0));
		videoSettingsPanel.add(getJToobarCheckBox(), new GridBagConstraints(6,
				0, 1, 1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		inactivityPanel.setBorder(BorderFactory
				.createTitledBorder("Inactivity processing"));
		inactivityPanel
				.setToolTipText("Inactivity handling, enabled if audio is not being recorded.");
		inactivityPanelLayout.rowWeights = new double[] { 0.0, 0.0, 0.0 };
		inactivityPanelLayout.rowHeights = new int[] { 7, 7, 7 };
		inactivityPanelLayout.columnWeights = new double[] { 0.0, 0.0, 0.0,
				0.0, 0.1 };
		inactivityPanelLayout.columnWidths = new int[] { 7, 7, 7, 47, 7 };
		inactivityPanel.setLayout(inactivityPanelLayout);
		inactivityPanel.setEnabled(false);

		inactivityCheckBox = new JCheckBox();
		inactivityPanel.add(inactivityCheckBox, new GridBagConstraints(1, 0, 4,
				1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));
		inactivityCheckBox.setText("Remove inactivity");
		inactivityCheckBox.setEnabled(false);

		inactivityIntervalLabel = new JLabel();
		inactivityPanel.add(inactivityIntervalLabel, new GridBagConstraints(1,
				2, 1, 1, 0.0, 0.0, GridBagConstraints.EAST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		inactivityIntervalLabel.setText("Inactivity interval (sec)");
		inactivityIntervalLabel.setEnabled(false);

		inactivityIntervalTextField = new JTextField();
		inactivityPanel.add(inactivityIntervalTextField,
				new GridBagConstraints(3, 2, 1, 1, 0.0, 0.0,
						GridBagConstraints.CENTER,
						GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0),
						0, 0));
		inactivityIntervalTextField.setText("0.7");
		inactivityIntervalTextField.setEnabled(false);

		audioSettingsPanel = new JPanel();
		GridBagLayout audioSettingsPanelLayout = new GridBagLayout();
		recordingSettingsPane.addTab("Audio", null, audioSettingsPanel, null);
		audioSettingsPanelLayout.rowWeights = new double[] { 0.0, 0.0, 0.0,
				0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.1 };
		audioSettingsPanelLayout.rowHeights = new int[] { 7, 7, 7, 7, 7, 7, 7, 7, 7,
				20 };
		audioSettingsPanelLayout.columnWeights = new double[] { 0.0, 0.0, 0.0,
				0.0, 0.0, 0.0, 0.1 };
		audioSettingsPanelLayout.columnWidths = new int[] { 7, 7, 49, 7, 135,
				7, 20 };
		audioSettingsPanel.setLayout(audioSettingsPanelLayout);

		sampleSize16Button = new JRadioButton();
		audioSettingsPanel.add(sampleSize16Button, new GridBagConstraints(4, 4,
				1, 1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		sampleSize16Button.setText("16");
		sampleSize16Button.setSelected(true);
		getSampleSizeButtonGroup().add(sampleSize16Button);
		sampleSize16Button.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				selectSoundSource();
			}
		});

		recordSoundCheckBox = new JCheckBox();
		audioSettingsPanel.add(recordSoundCheckBox, new GridBagConstraints(0,
				0, 4, 1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		recordSoundCheckBox.setText("Record sound");
		recordSoundCheckBox.setSelected(true);
		recordSoundCheckBox.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				onSetSound();
			}
		});

		soundSourceLabel = new JLabel();
		audioSettingsPanel.add(soundSourceLabel, new GridBagConstraints(0, 6,
				1, 1, 0.0, 0.0, GridBagConstraints.EAST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		soundSourceLabel.setText("Source");

		soundLineComboBox = new JComboBox();
		audioSettingsPanel.add(soundLineComboBox, new GridBagConstraints(2, 6,
				3, 1, 0.0, 0.0, GridBagConstraints.CENTER,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));

		audioSettingsPanel.add(new JLabel("WAV2MP3 command"), new GridBagConstraints(0, 8,
				1, 1, 0.0, 0.0, GridBagConstraints.EAST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));

		mp3Text = new JTextField();
		audioSettingsPanel.add(mp3Text, new GridBagConstraints(2, 8,
				5, 1, 0.0, 0.0, GridBagConstraints.CENTER,
				GridBagConstraints.HORIZONTAL, new Insets(0, 0, 0, 0), 0, 0));

		sampleRateLabel = new JLabel();
		audioSettingsPanel.add(sampleRateLabel, new GridBagConstraints(0, 2, 1,
				1, 0.0, 0.0, GridBagConstraints.EAST, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		sampleRateLabel.setText("Sample rate (khz)");

		sampleSizeLabel = new JLabel();
		audioSettingsPanel.add(sampleSizeLabel, new GridBagConstraints(0, 4, 1,
				1, 0.0, 0.0, GridBagConstraints.EAST, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		sampleSizeLabel.setText("Sample size (bits)");

		stereoCheckBox = new JCheckBox();
		audioSettingsPanel.add(stereoCheckBox, new GridBagConstraints(4, 2, 1,
				1, 0.0, 0.0, GridBagConstraints.WEST, GridBagConstraints.NONE,
				new Insets(0, 0, 0, 0), 0, 0));
		stereoCheckBox.setText("Stereo");
		stereoCheckBox.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				selectSoundSource();
			}
		});

		sampleSize8Button = new JRadioButton();
		audioSettingsPanel.add(sampleSize8Button, new GridBagConstraints(2, 4,
				1, 1, 0.0, 0.0, GridBagConstraints.WEST,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		sampleSize8Button.setText("8");
		getSampleSizeButtonGroup().add(sampleSize8Button);
		sampleSize8Button.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				selectSoundSource();
			}
		});

		ComboBoxModel<String> sampleRateComboBoxModel = new DefaultComboBoxModel<String>(
				new String[] { "5.5", "11", "22", "44" });
		sampleRateComboBox = new JComboBox<String>();
		audioSettingsPanel.add(sampleRateComboBox, new GridBagConstraints(2, 2,
				1, 1, 0.0, 0.0, GridBagConstraints.CENTER,
				GridBagConstraints.NONE, new Insets(0, 0, 0, 0), 0, 0));
		sampleRateComboBox.setModel(sampleRateComboBoxModel);
		sampleRateComboBox.setSelectedIndex(2);
		sampleRateComboBox.addActionListener(new ActionListener() {
			public void actionPerformed(ActionEvent evt) {
				selectSoundSource();
			}
		});
		
		getImageFormatTextField().setText(owner.getCaptureConfig().getImageFormat());
		
		recordSoundCheckBox.setSelected(owner.getCaptureConfig().isSound());
		stereoCheckBox.setSelected(owner.getCaptureConfig().getAudioFormat().getChannels()>1);
		if (owner.getCaptureConfig().getAudioFormat().getSampleSizeInBits()==8) {
			sampleSize8Button.setSelected(true);
		} else {
			sampleSize16Button.setSelected(true);
		}
		
		float sampleRate = owner.getCaptureConfig().getAudioFormat().getSampleRate();
		float proximity = Math.abs(sampleRate-sampleRates[0]);
		sampleRateComboBox.setSelectedIndex(0);
		for (int i=1; i<sampleRates.length; ++i) {
			float prx = Math.abs(sampleRate-sampleRates[i]);
			if (prx<proximity) {
				sampleRateComboBox.setSelectedIndex(i);
				proximity = prx;
			}				
		}			
		inactivityCheckBox.setSelected(owner.getCaptureConfig().isRemoveInactivity());
		if (owner.getCaptureConfig().isRemoveInactivity()) {
			inactivityIntervalTextField.setText(String.valueOf(owner.getCaptureConfig().getInactivityInterval()));
		}
		onSetSound();
		
		videoBorderCheckBox.setSelected(owner.getCaptureConfig().isBorder());
		getMouseCheckBox().setSelected(owner.getCaptureConfig().isMouse());
		getLoopCheckBox().setSelected(owner.getCaptureConfig().isLoop());
		getPlayCheckBox().setSelected(owner.getCaptureConfig().isPlay());
		fpsTextField.setText(Float.toString(owner.getCaptureConfig().getFramesPerSecond()));
		screenScaleTextField.setText(Long.toString(Math.round(owner.getCaptureConfig().getScreenScale()*100.0)));
		timeLineScaleTextField.setText(Long.toString(Math.round(owner.getCaptureConfig().getSpeedScale()*100.0)));
		toobarCheckBox.setSelected(owner.getCaptureConfig().isToolBar());
		mp3Text.setText(owner.getCaptureConfig().getMp3command());

		selectSoundSource();
		soundLineComboBox.setSelectedItem(owner.getCaptureConfig().getMixerName());
				
		timeLineScaleTextField.setEnabled(!recordSoundCheckBox.isSelected());
		
		getEncoderComboBox().setSelectedItem(owner.getCaptureConfig().getEncoder());
		
		JPanel aboutPanel = new JPanel();
		aboutPanel.setLayout(new BorderLayout());
		recordingSettingsPane.addTab("About", aboutPanel);
		aboutPanel.add(new JLabel("jCapture", SwingConstants.CENTER), BorderLayout.NORTH);
		aboutPanel.add(new JLabel("by Hammurapi Group (http://www.hammurapi.com)", SwingConstants.CENTER), BorderLayout.CENTER);
		aboutPanel.add(new JLabel("Memory (available/max): "+AbstractCaptureApplet.formatByteSize(Runtime.getRuntime().freeMemory())+"/"+AbstractCaptureApplet.formatByteSize(Runtime.getRuntime().maxMemory()), SwingConstants.CENTER), BorderLayout.SOUTH);		
		
		setSize(400, 300);
		setLocationRelativeTo(owner);
	}

	private AudioFormat audioFormat;
	private float[] sampleRates = { 5512.0F, 11025.0F, 22050.0F, 44100.0F };
	private DefaultComboBoxModel<String> soundLineComboBoxModel;
	private JLabel imageFormatLabel;
	private JTextField imageFormatTextField;
	private JCheckBox mouseCheckBox;
	private JCheckBox playCheckBox;
	private JCheckBox loopCheckBox;
	private JLabel encoderLabel;

	private void selectSoundSource() {
		audioFormat = new AudioFormat(
				sampleRates[sampleRateComboBox.getSelectedIndex()],
				sampleSize8Button.isSelected() ? 8 : 16,
				stereoCheckBox.isSelected() ? 2 : 1, true, false);

		String sourceName = (String) soundLineComboBox.getSelectedItem();

		if (soundLineComboBoxModel == null) {
			soundLineComboBoxModel = new DefaultComboBoxModel<String>();
			soundLineComboBox.setModel(soundLineComboBoxModel);
		} else {
			soundLineComboBoxModel.removeAllElements();
		}

		DataLine.Info info = new DataLine.Info(TargetDataLine.class, audioFormat);

		boolean hasSourceName = false;
		for (Mixer.Info mi : AudioSystem.getMixerInfo()) {
			Mixer mx = AudioSystem.getMixer(mi);
			if (mx.isLineSupported(info)) {
				soundLineComboBoxModel.addElement(mi.getName());
				if (sourceName!=null && mi.getName().equals(sourceName)) {
					hasSourceName = true;
				}
			}
		}

		if (hasSourceName) {
			soundLineComboBoxModel.setSelectedItem(sourceName);
		}
	}

	private ButtonGroup getSampleSizeButtonGroup() {
		if (sampleSizeButtonGroup == null) {
			sampleSizeButtonGroup = new ButtonGroup();
		}
		return sampleSizeButtonGroup;
	}

	private JLabel getFpsLabel() {
		if (fpsLabel == null) {
			fpsLabel = new JLabel();
			fpsLabel.setText("Frames Per Second");
		}
		return fpsLabel;
	}

	private JTextField getFpsTextField() {
		if (fpsTextField == null) {
			fpsTextField = new JTextField();
			fpsTextField.setText("10");
			fpsTextField.setSize(30, 23);
		}
		return fpsTextField;
	}

	private JLabel getImageFormatLabel() {
		if (imageFormatLabel == null) {
			imageFormatLabel = new JLabel();
			imageFormatLabel.setText("Image format");
		}
		return imageFormatLabel;
	}

	private JTextField getImageFormatTextField() {
		if (imageFormatTextField == null) {
			imageFormatTextField = new JTextField();
			imageFormatTextField.setText("png");
			imageFormatTextField.setSize(30, 23);
		}
		return imageFormatTextField;
	}

	private JLabel getEncoderLabel() {
		if (encoderLabel == null) {
			encoderLabel = new JLabel();
			encoderLabel.setText("Video format");
		}
		return encoderLabel;
	}

	private JComboBox<VideoEncoder> getEncoderComboBox() {
		if (encodersComboBox == null) {
			List<VideoEncoder> el = ((CaptureFrame) getOwner()).getCaptureConfig().getEncoders();
			encodersComboBox = new JComboBox<VideoEncoder>(el.toArray(new VideoEncoder[el.size()]));
//			encodersComboBox.setSize(30, 23);
		}
		return encodersComboBox;
	}

	private JLabel getScreenScalingLabel() {
		if (screenScalingLabel == null) {
			screenScalingLabel = new JLabel();
			screenScalingLabel.setText("Graphics");
		}
		return screenScalingLabel;
	}

	private JLabel getTimelineScalingLabel() {
		if (timelineScalingLabel == null) {
			timelineScalingLabel = new JLabel();
			timelineScalingLabel.setText("Speed");
		}
		return timelineScalingLabel;
	}

	private JTextField getScreenScaleTextField() {
		if (screenScaleTextField == null) {
			screenScaleTextField = new JTextField();
			screenScaleTextField.setText("100");
		}
		return screenScaleTextField;
	}

	private JTextField getTimeLineScaleTextField() {
		if (timeLineScaleTextField == null) {
			timeLineScaleTextField = new JTextField();
			timeLineScaleTextField.setText("100");
		}
		return timeLineScaleTextField;
	}

	private JCheckBox getVideoBorderCheckBox() {
		if (videoBorderCheckBox == null) {
			videoBorderCheckBox = new JCheckBox();
			videoBorderCheckBox.setText("Border");
		}
		return videoBorderCheckBox;
	}

	private JCheckBox getMouseCheckBox() {
		if (mouseCheckBox == null) {
			mouseCheckBox = new JCheckBox();
			mouseCheckBox.setText("Mouse");
		}
		return mouseCheckBox;
	}

	private JCheckBox getLoopCheckBox() {
		if (loopCheckBox == null) {
			loopCheckBox = new JCheckBox();
			loopCheckBox.setText("Loop");
		}
		return loopCheckBox;
	}

	private JCheckBox getPlayCheckBox() {
		if (playCheckBox == null) {
			playCheckBox = new JCheckBox();
			playCheckBox.setText("Play");
		}
		return playCheckBox;
	}

	private JCheckBox getJToobarCheckBox() {
		if (toobarCheckBox == null) {
			toobarCheckBox = new JCheckBox();
			toobarCheckBox.setText("Toolbar");
			toobarCheckBox.setSelected(true);
		}
		return toobarCheckBox;
	}

	void onSetSound() {
		for (Component child : recordSoundCheckBox.getParent().getComponents()) {
			if (child != recordSoundCheckBox) {
				child.setEnabled(recordSoundCheckBox.isSelected());
			}
		}
		inactivityPanel.setEnabled(!recordSoundCheckBox.isSelected());
		timeLineScaleTextField.setEnabled(!recordSoundCheckBox.isSelected());
		for (Component child : inactivityPanel.getComponents()) {
			child.setEnabled(!recordSoundCheckBox.isSelected());
		}
	}

}
