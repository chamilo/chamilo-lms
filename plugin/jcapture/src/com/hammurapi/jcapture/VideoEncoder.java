package com.hammurapi.jcapture;

import java.awt.Component;
import java.awt.Dimension;
import java.awt.Point;
import java.io.File;
import java.io.OutputStream;
import java.util.List;
 
/**
 * This is a service interface to be implemented by video encoders. 
 * jCapture discovers encoders using java.util.ServiceLoader.
 * 
 * All interfaces used by this interface are defined as nested for easy reference.
 * @author Pavel
 *
 */
public interface VideoEncoder {
	
	interface Config {

		/**
		 * 
		 * @return true if encoder shall add a toolbar to the movie.
		 */
		boolean isToolBar();
		
		/**
		 * 
		 * @return true if movie shall be played in a loop.
		 */
		boolean isLoop();
		
		/**
		 * 
		 * @return true if movie shall start playing after downloading.
		 */
		boolean isPlay();
		
		/**
		 * @return For scaling mouse pointer.
		 */
		double getScreenScale();
		
		/**
		 * For progress monitor.
		 * @return
		 */
		Component getParentComponent();
		
		String getImageFormat();

		/**
		 * @return OS command to convert WAV to MP3 if encoder requires/benefits from it.
		 */
		String getMp3command();
	}
	
	/**
	 * Movie fragment is a collection of frames with associated audio.
	 * @author Pavel
	 *
	 */
	interface Fragment {
		
		/**
		 * Frame contains zero or more shapes and mouse location.
		 * @author Pavel
		 *
		 */
		interface Frame {
			
			boolean isActive();
			
			/**
			 * Image shape to be placed on the screen.
			 * @author Pavel
			 *
			 */
			interface Shape {
				
				/**
				 * Base interface for shape content.
				 * @author Pavel
				 *
				 */
				interface ShapeContent {
					
					/**
					 * @return true if this shape covers the entire screen area.
					 */
					boolean coversEverything();
				}
				
				interface Image extends ShapeContent {
					
					MappedImage getImage();
					
				}

				/**
				 * References already defined image.
				 * @author Pavel
				 *
				 */
				interface ImageReference extends ShapeContent {
					
					Image getImage();
					
				}
												
				Point getLocation();
				
				ShapeContent getContent();
				
			}
			
			/**
			 * Frame's shapes.
			 * @return
			 */
			List<Shape> getShapes();
			
			Point getMousePointer();
			
			Dimension getSize();
			
		}
		
		/**
		 * Fragment frames.
		 * @return
		 */
		List<Frame> getFrames();
		
		/**
		 * Audio file (WAV).
		 * @return
		 */
		File getAudio();
		
	}
	
	String getFileExtension();
	
	String getMimeType();
	
	/**
	 * This method shall return output format name, e.g. SWF.
	 * @return
	 */
	String toString();
	
	/**
	 * Encodes video to output stream.
	 * @param fragments Fragments to encode
	 * @param out Output stream
	 * @param progressMonitor Progress monitor has work allocated for each frame plus one unit of work per fragment for sound decoding plus one unit for final encoding.
	 * @param progressCounter current progress counter position.
	 * @return movie size or null if operation was cancelled
	 */
	Dimension encode(Config config, Movie movie, OutputStream out) throws Exception;

}
