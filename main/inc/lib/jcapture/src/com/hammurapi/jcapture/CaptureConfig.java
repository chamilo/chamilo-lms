package com.hammurapi.jcapture;

import java.awt.AWTException;
import java.awt.Component;
import java.awt.MouseInfo;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.Robot;
import java.awt.image.BufferedImage;
import java.io.IOException;
import java.nio.channels.FileChannel;
import java.util.ArrayList;
import java.util.Collections;
import java.util.Comparator;
import java.util.Iterator;
import java.util.List;
import java.util.Properties;
import java.util.ServiceLoader;
import java.util.concurrent.ExecutorService;
import java.util.concurrent.Future;

import javax.sound.sampled.AudioFormat;

public class CaptureConfig implements VideoEncoder.Config {
	
	private static final String MP3_COMMAND_PROPERTY = "mp3command";
	private static final String TOOL_BAR_PROPERTY = "toolBar";
	private static final String SPEED_SCALE_PROPERTY = "speedScale";
	private static final String SOUND_PROPERTY = "sound";
	private static final String SCREEN_SCALE_PROPERTY = "screenScale";
	private static final String REMOVE_INACTIVITY_PROPERTY = "removeInactivity";
	private static final String PLAY_PROPERTY = "play";
	private static final String MOUSE_PROPERTY = "mouse";
	private static final String MIXER_NAME_PROPERTY = "mixerName";
	private static final String LOOP_PROPERTY = "loop";
	private static final String INACTIVITY_INTERVAL_PROPERTY = "inactivityInterval";
	private static final String IMAGE_FORMAT_PROPERTY = "imageFormat";
	private static final String FRAMES_PER_SECOND_PROPERTY = "framesPerSecond";
	private static final String BORDER_PROPERTY = "border";
	private static final String RECORDING_RECTANGLE_PROPERTY = "recordingRectangle";
	private static final String ENCODER_NAME_PROPERTY = "encoderName";
	private static final String AUDIO_FORMAT_SAMPLE_SIZE_PROPERTY = "audioFormat.sampleSize";
	private static final String AUDIO_FORMAT_SAMPLE_RATE_PROPERTY = "audioFormat.sampleRate";
	private static final String AUDIO_FORMAT_CHANNELS_PROPERTY = "audioFormat.channels";
	private AudioFormat audioFormat =  new AudioFormat(22050.0F, 16, 1, true, false);;
	private String mixerName;
	private float framesPerSecond = 10.0f;
	private double screenScale = 1.0;
	private float speedScale = 1.0f;
	private boolean removeInactivity;
	private double inactivityInterval = 0.7;
	private Component parentComponent;
	private Rectangle recordingRectangle;	
	private boolean border = true;
	private boolean toolBar = true;	
	private	Robot robot;
	private String imageFormat = "PNG";		
	private boolean sound = true;
	private boolean mouse = true;
	private boolean loop = true;
	private boolean play = false;
	private VideoEncoder encoder;
	private int grabRange = 3;
	private ExecutorService backgroundProcessor;
	private String mp3command;
		
	public String getMp3command() {
		return mp3command;
	}

	public void setMp3command(String mp3command) {
		this.mp3command = mp3command;
	}

	public int getGrabRange() {
		return grabRange;
	}

	public ExecutorService getBackgroundProcessor() {
		return backgroundProcessor;
	}

	public void setBackgroundProcessor(ExecutorService backgroundProcessor) {
		this.backgroundProcessor = backgroundProcessor;
	}

	public void setGrabRange(int grabRange) {
		this.grabRange = grabRange;
	}

	public VideoEncoder getEncoder() {
		return encoder;
	}

	public void setEncoder(VideoEncoder encoder) {
		this.encoder = encoder;
	}

	public boolean isLoop() {
		return loop;
	}

	public void setLoop(boolean loop) {
		this.loop = loop;
	}

	public boolean isPlay() {
		return play;
	}

	public void setPlay(boolean play) {
		this.play = play;
	}

	public boolean isSound() {
		return sound;
	}

	public void setSound(boolean sound) {
		this.sound = sound;
	}

	public boolean isMouse() {
		return mouse;
	}

	public void setMouse(boolean mouse) {
		this.mouse = mouse;
	}

	public String getImageFormat() {
		return imageFormat;
	}

	public void setImageFormat(String imageFormat) {
		this.imageFormat = imageFormat;
	}

	public CaptureConfig() throws AWTException {
		robot = new Robot();
		
		ServiceLoader<VideoEncoder> sl = ServiceLoader.load(VideoEncoder.class);
		List<VideoEncoder> accumulator = new ArrayList<VideoEncoder>();
		Iterator<VideoEncoder> vit = sl.iterator();
		while (vit.hasNext()) {
			accumulator.add(vit.next());
		}
		
		Collections.sort(accumulator, new Comparator<VideoEncoder>() {

			@Override
			public int compare(VideoEncoder o1, VideoEncoder o2) {
				return o1.toString().compareTo(o2.toString());
			}
			
		});
		
		encoders = Collections.unmodifiableList(accumulator);
		if (encoder==null && !encoders.isEmpty()) {
			encoder = encoders.get(0);
		}
		
	}
	
	/**
	 * Submits screenshot for processing in a background thread.
	 * @param task
	 * @return
	 */
	public Future<ScreenShot> submit(ScreenShot task) {
		return backgroundProcessor.submit(task);
	}	
	
	public Robot getRobot() {
		return robot;
	}
	
	public ScreenShot createScreenShot(ScreenShot prev, FileChannel imageChannel) throws IOException {		
		BufferedImage image = robot.createScreenCapture(recordingRectangle);
   		Point mouseLocation = MouseInfo.getPointerInfo().getLocation();
   		if (mouse && recordingRectangle.contains(mouseLocation)) {
   	   		mouseLocation.move(mouseLocation.x-recordingRectangle.x, mouseLocation.y-recordingRectangle.y);   			
   		} else {
   			mouseLocation = null;
   		}
   		return new ScreenShot(
   				image, 
   				mouseLocation, 
   				prev, 
   				System.currentTimeMillis(), 
   				grabRange, 
   				isTransparencySupported(),
   				border, 
   				getScreenScale(),
   				imageChannel,
   				getImageFormat());
	}
	
	public boolean isTransparencySupported() {
		return !"jpeg".equalsIgnoreCase(getImageFormat())
				&& !"jpg".equalsIgnoreCase(getImageFormat());				
	}
	
	public boolean isToolBar() {
		return toolBar;
	}
	public void setToolBar(boolean toolBar) {
		this.toolBar = toolBar;
	}
	public boolean isBorder() {
		return border;
	}
	public void setBorder(boolean border) {
		this.border = border;
	}
	public Rectangle getRecordingRectangle() {
		return recordingRectangle;
	}
	public Properties setRecordingRectangle(Rectangle recordingRectangle) {
		Rectangle oldValue = this.recordingRectangle;
		this.recordingRectangle = recordingRectangle;
		if (this.recordingRectangle!=null && !this.recordingRectangle.equals(oldValue)) {
			return store();
		}
		return null;
	}
	public AudioFormat getAudioFormat() {
		return audioFormat;
	}
	public void setAudioFormat(AudioFormat audioFormat) {
		this.audioFormat = audioFormat;
	}
	public String getMixerName() {
		return mixerName;
	}
	public void setMixerName(String mixerName) {
		this.mixerName = mixerName;
	}
	public float getFramesPerSecond() {
		return framesPerSecond;
	}
	public void setFramesPerSecond(float framesPerSecond) {
		this.framesPerSecond = framesPerSecond;
	}
	public double getScreenScale() {
		return screenScale;
	}
	public void setScreenScale(double screenScale) {
		this.screenScale = screenScale;
	}
	public float getSpeedScale() {
		return speedScale;
	}
	public void setSpeedScale(float speedScale) {
		this.speedScale = speedScale;
	}
	public boolean isRemoveInactivity() {
		return removeInactivity;
	}
	public void setRemoveInactivity(boolean removeInactivity) {
		this.removeInactivity = removeInactivity;
	}
	public double getInactivityInterval() {
		return inactivityInterval;
	}
	public void setInactivityInterval(double inactivityInterval) {
		this.inactivityInterval = inactivityInterval;
	}
	public Component getParentComponent() {
		return parentComponent;
	}
	public void setParentComponent(Component parentComponent) {
		this.parentComponent = parentComponent;
	}
			
    void load(Properties properties) {    	
    	if (properties!=null) {
    		try {
    			if (properties.containsKey(AUDIO_FORMAT_CHANNELS_PROPERTY)) {
    				audioFormat = new AudioFormat(
    						Float.parseFloat(properties.getProperty(AUDIO_FORMAT_SAMPLE_RATE_PROPERTY, String.valueOf(audioFormat.getSampleRate()))),
    						Integer.parseInt(properties.getProperty(AUDIO_FORMAT_SAMPLE_SIZE_PROPERTY, String.valueOf(audioFormat.getSampleSizeInBits()))),
    						Integer.parseInt(properties.getProperty(AUDIO_FORMAT_CHANNELS_PROPERTY, String.valueOf(audioFormat.getChannels()))),
    						true, false);
    			}
        		
    			border=Boolean.parseBoolean(properties.getProperty(BORDER_PROPERTY, String.valueOf(border)));    				
    			framesPerSecond=Float.parseFloat(properties.getProperty(FRAMES_PER_SECOND_PROPERTY, String.valueOf(framesPerSecond)));    				
    			imageFormat=properties.getProperty(IMAGE_FORMAT_PROPERTY, String.valueOf(imageFormat));    				
    			inactivityInterval=Double.parseDouble(properties.getProperty(INACTIVITY_INTERVAL_PROPERTY, String.valueOf(inactivityInterval)));    				
    			loop=Boolean.parseBoolean(properties.getProperty(LOOP_PROPERTY, String.valueOf(loop)));    				
    			mixerName=properties.getProperty(MIXER_NAME_PROPERTY, String.valueOf(mixerName));    				
    			mouse=Boolean.parseBoolean(properties.getProperty(MOUSE_PROPERTY, String.valueOf(mouse)));    				
    			play=Boolean.parseBoolean(properties.getProperty(PLAY_PROPERTY, String.valueOf(play)));    				
    			removeInactivity=Boolean.parseBoolean(properties.getProperty(REMOVE_INACTIVITY_PROPERTY, String.valueOf(removeInactivity)));    				
    			screenScale=Double.parseDouble(properties.getProperty(SCREEN_SCALE_PROPERTY, String.valueOf(screenScale)));    				
    			sound=Boolean.parseBoolean(properties.getProperty(SOUND_PROPERTY, String.valueOf(sound)));    				
    			speedScale=Float.parseFloat(properties.getProperty(SPEED_SCALE_PROPERTY, String.valueOf(speedScale)));    				
    			toolBar=Boolean.parseBoolean(properties.getProperty(TOOL_BAR_PROPERTY, String.valueOf(toolBar)));    
    			mp3command=properties.getProperty(MP3_COMMAND_PROPERTY);
    			encoder = null;
    			String encoderName = properties.getProperty(ENCODER_NAME_PROPERTY);
    			if (encoderName!=null) {
    				for (VideoEncoder candidate: getEncoders()) {
    					if (encoderName.equals(candidate.toString())) {
    						encoder = candidate;
    						break;
    					}
    				}
    			}
    			if (encoder==null && !getEncoders().isEmpty()) {
    				encoder = getEncoders().get(0);
    			}
    			
    			String rr = properties.getProperty(RECORDING_RECTANGLE_PROPERTY);
    			if (rr!=null && rr.trim().length()>0) {
    				String[] dims = rr.split(";");
    				recordingRectangle = new Rectangle(Integer.parseInt(dims[0]), Integer.parseInt(dims[1]), Integer.parseInt(dims[2]), Integer.parseInt(dims[3]));
    			}
    				
    		} catch (Exception e) {
    			e.printStackTrace();
    		}
    	}
    }
    
    private List<VideoEncoder> encoders;
    
    /**
     * @return array of available encoders.
     */
    public List<VideoEncoder> getEncoders() {
		return encoders;
	}

	Properties store() {
    	Properties properties = new Properties();
		if (audioFormat!=null) {
			properties.setProperty(AUDIO_FORMAT_CHANNELS_PROPERTY, String.valueOf(audioFormat.getChannels()));
			properties.setProperty(AUDIO_FORMAT_SAMPLE_RATE_PROPERTY, String.valueOf(audioFormat.getSampleRate()));
			properties.setProperty(AUDIO_FORMAT_SAMPLE_SIZE_PROPERTY, String.valueOf(audioFormat.getSampleSizeInBits()));    				
		}
		properties.setProperty(BORDER_PROPERTY, String.valueOf(border));    				
		properties.setProperty(FRAMES_PER_SECOND_PROPERTY, String.valueOf(framesPerSecond));    				
		properties.setProperty(IMAGE_FORMAT_PROPERTY, String.valueOf(imageFormat));    				
		properties.setProperty(INACTIVITY_INTERVAL_PROPERTY, String.valueOf(inactivityInterval));    				
		properties.setProperty(LOOP_PROPERTY, String.valueOf(loop));    				
		properties.setProperty(MIXER_NAME_PROPERTY, String.valueOf(mixerName));    				
		properties.setProperty(MOUSE_PROPERTY, String.valueOf(mouse));    				
		properties.setProperty(PLAY_PROPERTY, String.valueOf(play));    				
		properties.setProperty(REMOVE_INACTIVITY_PROPERTY, String.valueOf(removeInactivity));    				
		properties.setProperty(SCREEN_SCALE_PROPERTY, String.valueOf(screenScale));    				
		properties.setProperty(SOUND_PROPERTY, String.valueOf(sound));    				
		properties.setProperty(SPEED_SCALE_PROPERTY, String.valueOf(speedScale));    				
		properties.setProperty(TOOL_BAR_PROPERTY, String.valueOf(toolBar));
		if (recordingRectangle!=null) {
			properties.setProperty(RECORDING_RECTANGLE_PROPERTY, recordingRectangle.x+";"+recordingRectangle.y+";"+recordingRectangle.width+";"+recordingRectangle.height);
		}
		if (mp3command!=null) {
			properties.setProperty(MP3_COMMAND_PROPERTY, mp3command);
		}
		if (encoder!=null) {
			properties.setProperty(ENCODER_NAME_PROPERTY, encoder.toString());
		}
    	
    	return properties;
    }

	
}