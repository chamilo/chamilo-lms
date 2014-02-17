package com.hammurapi.jcapture;

import java.awt.AlphaComposite;
import java.awt.Dimension;
import java.awt.Graphics2D;
import java.awt.Point;
import java.awt.RenderingHints;
import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.File;
import java.io.OutputStream;
import java.text.MessageFormat;
import java.util.ArrayList;
import java.util.IdentityHashMap;
import java.util.List;
import java.util.Map;
import java.util.concurrent.atomic.AtomicInteger;

import javax.imageio.ImageIO;
import javax.swing.JOptionPane;
import javax.swing.ProgressMonitor;

import com.flagstone.transform.Background;
import com.flagstone.transform.DefineTag;
import com.flagstone.transform.DoAction;
import com.flagstone.transform.Movie;
import com.flagstone.transform.MovieHeader;
import com.flagstone.transform.MovieTag;
import com.flagstone.transform.Place2;
import com.flagstone.transform.Remove;
import com.flagstone.transform.Remove2;
import com.flagstone.transform.ShowFrame;
import com.flagstone.transform.action.Action;
import com.flagstone.transform.action.BasicAction;
import com.flagstone.transform.coder.Coder;
import com.flagstone.transform.datatype.Bounds;
import com.flagstone.transform.datatype.CoordTransform;
import com.flagstone.transform.datatype.WebPalette;
import com.flagstone.transform.image.ImageTag;
import com.flagstone.transform.util.image.ImageDecoder;
import com.flagstone.transform.util.image.ImageRegistry;
import com.flagstone.transform.util.image.ImageShape;
import com.flagstone.transform.util.shape.Canvas;
import com.flagstone.transform.util.sound.SoundFactory;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.Image;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.ImageReference;
import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape.ShapeContent;

public class SwfEncoder implements VideoEncoder {

	@Override
	public String getFileExtension() {
		return "swf";
	}

	@Override
	public String getMimeType() {
		return "application/x-shockwave-flash";
	}
	
	@Override
	public String toString() {
		return "SWF";
	}

	@Override
	public Dimension encode(Config config, 
		com.hammurapi.jcapture.Movie source,
		OutputStream out)
		throws Exception {

        AtomicInteger uid = new AtomicInteger();
        
        /**
         * For reusing shape id's.
         */
        int maxId = Coder.USHORT_MAX;
        		        
        ButtonManager manager = null;
        if (config.isToolBar()) {
	    	manager = new ButtonManager();
	    	manager.loadLibrary(getClass().getResource("toolbar_buttons.swf"));				    			        
        	uid.set(manager.maxIdentifier()+1);
        }
        
        Canvas path = new Canvas();
        path.setPixels(false);
        
        int minImgLayer = 10;
        int imgLayer = minImgLayer;
        int maxImgLayer = maxId - 1000;
        
        int mouseLayer = maxImgLayer+1;
        int mouseUid = -1;
    	Place2 mousePlace = null;		       
    	ImageTag mouseImage = null;
    	
        int layer = maxImgLayer+2;        	
        
        int totalWork = 0; 
        for (Fragment frg: source.getFragments()) {
        	totalWork = frg.getFrames().size()+1;
        }
        
        ProgressMonitor progressMonitor = new ProgressMonitor(config.getParentComponent(), "Encoding to SWF", "Composing movie", 0, totalWork);
        int progressCounter = 0;
	        
        progressMonitor.setNote("Composing movie");
        boolean firstFrame = true;
        Dimension ret = null;
        Map<Image, ImageTag> imageCache = new IdentityHashMap<Image, ImageTag>();
        	        
        Movie movie = new Movie();
        
        Point prevMouseLocation = null;
        
        int frameNo = 0;
        
        for (Fragment fragment: source.getFragments()) {
		      	        	
	        SoundFactory soundFactory = null;
	        boolean soundHeaderAdded = false;
	        File audio = fragment.getAudio();	        
	        if (audio!=null) {
		        progressMonitor.setNote("Loading sound");
		        soundFactory = new SoundFactory();
		        
		 		// MP3 conversion
		 		if (config.getMp3command()!=null && config.getMp3command().trim().length()>0) {
			 		audio = new File(audio.getAbsolutePath()+".mp3"); 
			 		Runtime runtime = Runtime.getRuntime();				 		 
				    Process proc = runtime.exec(MessageFormat.format(config.getMp3command(), new Object[] {fragment.getAudio().getAbsolutePath(), audio.getAbsolutePath()}));
				    proc.waitFor();
				    if (!fragment.getAudio().delete()) {
				    	fragment.getAudio().deleteOnExit();
					}
		 		}
		        
		        soundFactory.read(audio);
	        }
		        
		    progressMonitor.setProgress(++progressCounter);
		    
 			if (progressMonitor.isCanceled()) {
 				return null;
 			}
 			
		    for (Frame frame: fragment.getFrames()) {
	 			if (progressMonitor.isCanceled()) {
	 				return null;
	 			}
	 			
	 			boolean addStop = false;
	 			
	 			++frameNo;
		        		
		        if (firstFrame) {
		        	firstFrame = false;
		        	
		            MovieHeader header = new MovieHeader();
		            header.setCompressed(true);
		            header.setFrameRate(source.getFramesPerSecond());	            
		            
		        	int toolbarHeight = 29 * 20;
		        	int toolbarWidth = 495 * 20;

		        	int toolbarX = 0; // - image.getWidth()*20/2;
		        	int toolbarY = frame.getSize().height*20;		            
		            
					int movieWidth = frame.getSize().width*20;
					int movieHeight = frame.getSize().height*20;
					if (config.isToolBar()) {
						movieHeight+=toolbarHeight;
					}
					
					ret = new Dimension(movieWidth/20, movieHeight/20);
					
		        	float toolbarScaleX = (float) movieWidth / (float) toolbarWidth;
		        	float toolbarScaleY = 1.0f;
		        	
					Bounds movieBounds = new Bounds(0, 0, movieWidth, movieHeight);
					header.setFrameSize(movieBounds);
		            movie.add(header);
		            movie.add(new Background(WebPalette.WHITE.color()));
		            
		            if (config.isToolBar()) {	            
			        	// Add all the shapes etc used for buttons
			        	List<DefineTag> toolbarDefinitions = manager.getDefinitions();
						movie.getObjects().addAll(toolbarDefinitions);
			        			            
			        	Place2 placeBackground = manager.getButton("background", layer++, 0, 0);
			        	placeBackground.setTransform(new CoordTransform(toolbarScaleX, toolbarScaleY, 0, 0, toolbarX, toolbarY));
		
			        	// Get the button to use and give its position
			        	movie.add(placeBackground);
			        	movie.add(manager.getButton("play_button", layer++, toolbarX + 500, toolbarY + toolbarHeight / 2));
			        	movie.add(manager.getButton("progress_bar", layer++, toolbarX + 1000, toolbarY + toolbarHeight / 2));
			        	movie.add(manager.getButton("volume_control", layer++, toolbarX + 5600, toolbarY + toolbarHeight / 2));
			        	
			        	if (!config.isPlay()) {
			        		addStop = true;
			        	}
		            }
		        }
		        
		        if (!soundHeaderAdded && soundFactory!=null) {
		            movie.add(soundFactory.streamHeader(source.getFramesPerSecond()));
		            soundHeaderAdded = true;
		        }
			        
		        if (soundFactory!=null) {
			        MovieTag soundBlock = soundFactory.streamSound();
			        if (soundBlock != null) {
			            movie.add(soundBlock);
			        }
		        }
			        
		 		for (Shape shape: frame.getShapes()) {
		 			if (shape.getContent().coversEverything() || imgLayer==maxImgLayer) {
		 				for (int i=minImgLayer; i<=imgLayer; ++i) {
		 					movie.add(new Remove2(i));
		 				}
		 				imgLayer = minImgLayer;
		 			}
		 			
		 			ShapeContent shapeContent = shape.getContent();
		 			Image image;
		 			if (shapeContent instanceof Image) {
		 				image = (Image) shapeContent;
		 			} else if (shape.getContent() instanceof ImageReference) {
		 				image = ((ImageReference) shapeContent).getImage();
		 			} else {
		 				throw new IllegalArgumentException("Unexpected content type: "+shapeContent);
		 			}
		 			
		 			ImageTag imageTag = imageCache.get(image);
		 			if (imageTag==null) {
		 				try {
				            ImageDecoder decoder = ImageRegistry.getImageProvider("image/"+config.getImageFormat().toLowerCase());           
				            decoder.read(new ByteArrayInputStream(image.getImage().getImageBytes()));
				            imageTag = decoder.defineImage(uid.incrementAndGet());
				            imageCache.put(image, imageTag);
				            movie.add(imageTag);
		 				} catch (Exception e) {
		 					// Doing our best to create movie, even with flaws.
		 					System.err.println("Error encoding image at frame "+frameNo+": "+e);
		 					e.printStackTrace();
		 					if (JOptionPane.showConfirmDialog(config.getParentComponent(),
		 							"Error encoding image ("+image.getImage().getWidth()+"*"+image.getImage().getHeight()+") at frame "+frameNo+": "+e+". Continue encoding?", 
		 							"Encoding error",
	                                JOptionPane.YES_NO_OPTION,
	                                JOptionPane.ERROR_MESSAGE)==JOptionPane.NO_OPTION) {
		 						throw e;
		 					}
		 				}
		 			}
		 			
		 			int shapeId = uid.incrementAndGet();
			        DefineTag shapeTag = new ImageShape().defineShape(shapeId, imageTag);	
			        Place2 place = Place2.show(shapeTag.getIdentifier(), imgLayer++, shape.getLocation().x*20, shape.getLocation().y*20);
		            movie.add(shapeTag);
		            movie.add(place);			 			
		 		}
		    						        
		        Point mouseLocation = frame.getMousePointer();
		        if (mouseLocation!=null) {
	        		if (mouseImage==null) {
	        			BufferedImage mouseBi = ImageIO.read(getClass().getResource("mouse.png"));
	        	    	if (config.getScreenScale()<0.99 || config.getScreenScale() > 1.01) {
	        		    	BufferedImage scaled = new BufferedImage((int) (mouseBi.getWidth()*config.getScreenScale()), (int) (mouseBi.getHeight()*config.getScreenScale()), mouseBi.getType());
	        		    	Graphics2D g = scaled.createGraphics();
	        		    	g.setComposite(AlphaComposite.Src);
	        		    	g.setRenderingHint(RenderingHints.KEY_INTERPOLATION,RenderingHints.VALUE_INTERPOLATION_BILINEAR);
	        		    	g.setRenderingHint(RenderingHints.KEY_RENDERING,RenderingHints.VALUE_RENDER_QUALITY);
	        		    	g.setRenderingHint(RenderingHints.KEY_ANTIALIASING,RenderingHints.VALUE_ANTIALIAS_ON);
	        		    	g.drawImage(mouseBi, 0, 0, scaled.getWidth(), scaled.getHeight(), null);
	        		    	g.dispose();
	        		    	mouseBi = scaled;
	        	    	}

	    				ByteArrayOutputStream baos = new ByteArrayOutputStream();        	
	    				ImageIO.write(mouseBi, "PNG", baos);
	    	        	baos.close();
	    	            ImageDecoder decoder = ImageRegistry.getImageProvider("image/png");           
	    	            decoder.read(new ByteArrayInputStream(baos.toByteArray()));
		            	mouseImage = decoder.defineImage(uid.incrementAndGet());
		            	movie.add(mouseImage);
	        		}
		                
	        		if (!mouseLocation.equals(prevMouseLocation)) {
	        			prevMouseLocation = mouseLocation;
			            mouseUid = uid.incrementAndGet();
				        DefineTag mShape = new ImageShape().defineShape(uid.incrementAndGet(), mouseImage); //createRect(mouseUid, 100, 100, WebPalette.RED.color());		
				        if (mousePlace==null) {
				        	mousePlace = Place2.show(mShape.getIdentifier(), mouseLayer, mouseLocation.x*20, mouseLocation.y*20);
				        } else {
				        	mousePlace = Place2.replace(mShape.getIdentifier(), mouseLayer, mouseLocation.x*20, mouseLocation.y*20);
				        }
			            movie.add(mShape);
			            movie.add(mousePlace);
	        		}
	        	} else if (mouseUid!=-1) {
	        		Remove remove = new Remove(mouseUid, mouseLayer);
	        		movie.add(remove);
	        	}
			     
		        if (addStop) {
		        	DoAction cmd = new DoAction(new ArrayList<Action>());
		        	cmd.add(BasicAction.STOP);
		        	movie.add(cmd);
		        }
	        	movie.add(ShowFrame.getInstance());
			        
	 			progressMonitor.setProgress(++progressCounter);			        	        
		    }

	 		progressMonitor.setProgress(++progressCounter);
	        if (soundFactory!=null) {
		 		progressMonitor.setNote("Recording trailing sound");
		        MovieTag block;
		        while ((block = soundFactory.streamSound()) != null) {
		            movie.add(block);
		            movie.add(ShowFrame.getInstance());
		        }
	        }
	        
	        if (audio!=null) {
	        	if (!audio.delete()) {
	        		audio.deleteOnExit();
	        	}
	        }
        }
					        
        if (!config.isLoop()) {
        	List<Action> actions = new ArrayList<Action>();
        	actions.add(BasicAction.STOP);
        	actions.add(BasicAction.END);
			DoAction doAction = new DoAction(actions);	        	
			movie.add(doAction);
            movie.add(ShowFrame.getInstance());
        }
        
        progressMonitor.setProgress(++progressCounter);
        progressMonitor.setNote("Encoding movie");
		movie.encodeToStream(out);
		source.close();
		return ret;
	}

}
