package com.hammurapi.jcapture;

import java.awt.Dimension;
import java.io.Closeable;
import java.io.File;
import java.io.IOException;
import java.io.RandomAccessFile;
import java.nio.channels.FileChannel;
import java.util.ArrayList;
import java.util.Collections;
import java.util.IdentityHashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;
import java.util.concurrent.Future;
import java.util.zip.DataFormatException;

import javax.sound.sampled.AudioFileFormat;
import javax.sound.sampled.AudioInputStream;
import javax.sound.sampled.AudioSystem;
import javax.sound.sampled.DataLine;
import javax.sound.sampled.Mixer;
import javax.sound.sampled.TargetDataLine;
import javax.swing.ProgressMonitor;

import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.Image;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.ShapeContent;

/**
 * Records screen into SWF movie.
 * @author Pavel Vlasov
 *
 */
public class ScreenRecorder {
	
	private CaptureConfig config;
	private Closeable imagesFileCloseable;
	
	class Fragment {
		
		private ScreenShot first;
		
		float getActualFps() {
			return first.getFramesPerSecond(); 
		}
		
		private class AudioRecordingThread extends SafeThread {
			
			public AudioRecordingThread() {
				super("Audio recording thread");
			}

			@Override
			protected void runInternal() throws Exception {
				AudioSystem.write(new AudioInputStream(targetDataLine), AudioFileFormat.Type.WAVE, audioSink);
			}
			
		}
				
		private class ScreenCapturingThread  extends SafeThread {
			
			public ScreenCapturingThread() {
				super("Screen capturing thread");
			}

			@Override
			protected void runInternal() throws Exception {
				long start = System.currentTimeMillis();
				ScreenShot screenShot = null;
				for (int shot=0; !isDone; ++shot) {
					
		       		long toSleep = (shot+1)*frameLength - (System.currentTimeMillis()-start);
		       		if (toSleep>0) {
		       			Thread.sleep(toSleep);
		       		}
					
		       		screenShot = config.createScreenShot(screenShot, imagesChannel);
		       		if (first==null) {
		       			first = screenShot;
		       		}
		       		screenshots.add(config.submit(screenShot));		       		
		       	}
				
				System.out.println("Captured "+screenshots.size()+" screenshots");
			}
			
		}		
		
		public Fragment() throws Exception {	        
			if (targetDataLine!=null) {
	        	audioSink = File.createTempFile("jCaptureAudioSink", ".wav");           
				targetDataLine.start();
				audioRecordingThread = new AudioRecordingThread();
				audioRecordingThread.start();
			}
			
			screenCapturingThread = new ScreenCapturingThread();			
			screenCapturingThread.start();
		}
		
		File audioSink;
		List<Future<ScreenShot>> screenshots = new ArrayList<Future<ScreenShot>>();
		
		AudioRecordingThread audioRecordingThread;
		ScreenCapturingThread screenCapturingThread;
		
		volatile boolean isDone;

		void stop() throws Exception {
			if (targetDataLine!=null) {
				targetDataLine.stop();
			}
			isDone = true;
			if (audioRecordingThread!=null) {
				audioRecordingThread.join();
			}
			screenCapturingThread.join();
			if (screenCapturingThread.getException()!=null) {
				throw screenCapturingThread.getException();
			}
			if (audioRecordingThread!=null && audioRecordingThread.getException()!=null) {
				throw audioRecordingThread.getException();
			}
		}
				
	}
	
	LinkedList<Fragment> fragments = new LinkedList<Fragment>();
	private FileChannel imagesChannel;
	
	public ScreenRecorder(CaptureConfig config, AbstractCaptureApplet applet) throws Exception {
		this.config = config;
		final File imagesFile = File.createTempFile("jCaptureImages", ".tmp");
		imagesFile.deleteOnExit();
		final RandomAccessFile raf = new RandomAccessFile(imagesFile, "rw");
		this.imagesChannel = raf.getChannel();
		
		imagesFileCloseable = new Closeable() {

			@Override
			public void close() throws IOException {
				imagesChannel.close();
				raf.close();
				if (!imagesFile.delete()) {
					imagesFile.deleteOnExit();
				}				
			}
			
		};
		
		applet.addCloseable(imagesFileCloseable);
        
        if (config.isSound()) {
			DataLine.Info info = new DataLine.Info(TargetDataLine.class, config.getAudioFormat());
			
			Mixer mixer = null;
			Mixer firstMixer = null;
			for (Mixer.Info mi: AudioSystem.getMixerInfo()) {
				Mixer mx = AudioSystem.getMixer(mi);
				if (mx.isLineSupported(info)) {
					if (firstMixer==null) {
						firstMixer = mx;
					}
					if (config.getMixerName()==null || mi.getName().equals(config.getMixerName())) {
						mixer = mx;
						break;
					}
				}			
			}
			
			if (mixer==null) {
				mixer = firstMixer;
			}
			
			if (mixer!=null) {
				targetDataLine = (TargetDataLine) mixer.getLine(info); 
				targetDataLine.open(config.getAudioFormat());
			}
        }
				
		frameLength = (long) (1000.0/config.getFramesPerSecond());
		
		start();
	}
	
	public synchronized void start() throws Exception {
        fragments.add(new Fragment());
	}
	
	public void stop() throws Exception {
		fragments.getLast().stop();
	}
	
	/**
	 * Recording is discarded if saveTo is null
	 * @param saveTo
	 * @return Movie size in pixels or null if saving was cancelled.
	 * @throws IOException 
	 * @throws DataFormatException 
	 */
	public Movie getMovie() throws Exception {
		stop();
		
		if (targetDataLine!=null) {
			targetDataLine.close();
		}
					
		int totalWork = 3;
		for (Fragment f: fragments) {
			totalWork+=f.screenshots.size()+1;
		}
		
		Map<Region, Image> imageCache = new IdentityHashMap<Region, VideoEncoder.Fragment.Frame.Shape.Image>();
		
		Dimension frameDimension = null;
		
		ProgressMonitor progressMonitor = new ProgressMonitor(config.getParentComponent(), "Encoding video", "Preparing frames", 0, totalWork+4);
		try {				
			int progressCounter = 0;
	        
	        //In frames
	        int inactivityInterval = config.isRemoveInactivity() && !config.isSound() ? (int) (1000.0 * config.getInactivityInterval() / frameLength) : -1;
	        float fps = -1;
	        final List<VideoEncoder.Fragment> fragmentCollector = new ArrayList<VideoEncoder.Fragment>();
	        for (Fragment fragment: fragments) {
	 			if (progressMonitor.isCanceled()) {
	 				return null;
	 			}
	 			
		        if (fps<0) {
		        	fps = config.isSound() ? fragment.getActualFps() : config.getSpeedScale()*fragment.getActualFps();
		        }
		        
		        progressMonitor.setProgress(++progressCounter);
		        			        
		        int lastActivity = -1;
		        List<VideoEncoder.Fragment.Frame> framesCollector = new ArrayList<VideoEncoder.Fragment.Frame>();
		 		for (Future<ScreenShot> sf: fragment.screenshots) {
		 			
		 			if (progressMonitor.isCanceled()) {
		 				return null;
		 			}
		 		
		 			ScreenShot screenShot = sf.get();
		 			
			        if (inactivityInterval<0 || screenShot.isActive() || screenShot.getSecNo()-lastActivity<inactivityInterval) {
				        List<Shape> frameShapes = new ArrayList<VideoEncoder.Fragment.Frame.Shape>();
				        for (Region region: screenShot.getRegions()) {					        	
				        	ShapeContent content;
				        	if (region.getMasterImageRegion()==null) {
				        		content = new ShapeImpl.ImageImpl(region.getImage(), region.coversEverything());
				        		imageCache.put(region, (Image) content);
				        		if (frameDimension==null && region.coversEverything()) {
				        			frameDimension = region.getSize();
				        		}
				        	} else {
				        		content = new ShapeImpl.ImageReferenceImpl(imageCache.get(region.getMasterImageRegion()));
				        	}
							frameShapes.add(new ShapeImpl(region.getImageLocation(), content));
				        }
						framesCollector.add(new FrameImpl(frameShapes, screenShot.getMousePosition(), screenShot.getSize(), screenShot.isActive()));
			        } else {
			 			progressMonitor.setProgress(++progressCounter);	// Skipping frame, report progress here.		        	        				        	
			        }
			        
			        if (screenShot.isActive()) {
			        	lastActivity = screenShot.getSecNo();
			        }

		 			progressMonitor.setProgress(++progressCounter);			        	        
		        }	
		 		
	 			fragmentCollector.add(new FragmentImpl(Collections.unmodifiableList(framesCollector), fragment.audioSink));
	        }
	        		        		        
			return new Movie(frameDimension, fps, fragmentCollector, imagesFileCloseable);
		} finally {
			progressMonitor.close();
		}		
	}
	
	private static abstract class SafeThread extends Thread {
		private Exception exception;

		public SafeThread(String name) {
			super(name);
		}

		@Override
		public void run() {
			try {
				runInternal();
			} catch (Exception e) {
				this.exception = e;
				e.printStackTrace();
			}
		}
		
		protected abstract void runInternal() throws Exception;
		
		public Exception getException() {
			return exception;
		}
	}
	
	long frameLength;

	
	private TargetDataLine targetDataLine;	
			
}