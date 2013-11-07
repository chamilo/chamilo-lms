package com.hammurapi.jcapture;
 
import java.awt.AlphaComposite;
import java.awt.Dimension;
import java.awt.Graphics2D;
import java.awt.Point;
import java.awt.RenderingHints;
import java.awt.image.BufferedImage;
import java.io.File;
import java.io.IOException;
import java.nio.channels.FileChannel;
import java.util.ArrayList;
import java.util.List;
import java.util.ListIterator;
import java.util.concurrent.Callable;

import javax.imageio.ImageIO;

public class ScreenShot implements Callable<ScreenShot> {
	
	private final ScreenShot prev;
	private final int secNo;
	private ScreenShot next;
	final private long timeStamp;
	private int grabRange;
	private boolean transparency;
	private MappedImage image;
	private Point mousePosition;
	private double scale;
	private boolean border;
	private Dimension size;
	private FileChannel imageChannel;
	private String imageFormat;
	
	public ScreenShot(
			BufferedImage image, 
			Point mousePosition, 
			ScreenShot prev, 
			long timeStamp, 
			int grabRange, 
			boolean transparency, 
			boolean border, 
			double scale, 
			FileChannel imageChannel,
			String imageFormat) throws IOException {
		
		this.image = new MappedImage(image, imageFormat, imageChannel);
		this.mousePosition = mousePosition;
		this.prev = prev;
		if (prev==null) {
			secNo=0;
		} else {
			prev.next = this;
			secNo = prev.secNo+1;
		}
		this.timeStamp = timeStamp;
		this.grabRange = grabRange;
		this.transparency = transparency;
		this.scale = scale;		
		this.border = border;
		this.imageChannel = imageChannel;
		this.imageFormat = imageFormat;
	}
	
	public Point getMousePosition() {
		return mousePosition;
	}
	
	/**
	 * Calculates actual FPS.
	 * @return
	 */
	public float getFramesPerSecond() {
		long start = timeStamp;
		long end = 0;
		int length = 0;
		for (ScreenShot sibling = next; sibling!=null; sibling=sibling.next) {
			++length;
			end = sibling.timeStamp;
		}
		if (length==0) {
			return -1; // No way to tell.
		}
		return (float) (length * 1000.0)/(end - start);
	}
		
	private List<Region> regions;
	
	private long totalPixels;
	private long differentPixels;
	
	public double getDiffLevel() {
		return (double) differentPixels/(double) totalPixels;
	}
	
	/**
	 * If images are different more than diffThreshold, then the 
	 * entire screenshot shall be taken.
	 */
	private double diffThreshold = 0.7;
	
	/**
	 * Performs processing and returns self.
	 * Screenshot is structured as Callable to simplify live processing in a background thread.
	 */
	@Override
	public ScreenShot call() throws Exception {
		BufferedImage img = image.getImage();
    	// No petty scaling.
    	if (scale<0.99 || scale > 1.01) {
	    	BufferedImage scaled = new BufferedImage((int) (img.getWidth()*scale), (int) (img.getHeight()*scale), img.getType());
	    	Graphics2D g = scaled.createGraphics();
	    	g.setComposite(AlphaComposite.Src);
	    	g.setRenderingHint(RenderingHints.KEY_INTERPOLATION,RenderingHints.VALUE_INTERPOLATION_BILINEAR);
	    	g.setRenderingHint(RenderingHints.KEY_RENDERING,RenderingHints.VALUE_RENDER_QUALITY);
	    	g.setRenderingHint(RenderingHints.KEY_ANTIALIASING,RenderingHints.VALUE_ANTIALIAS_ON);
	    	g.drawImage(img, 0, 0, scaled.getWidth(), scaled.getHeight(), null);
	    	g.dispose();
	    	img = scaled;
	    	
	    	if (mousePosition!=null) {
	    		mousePosition = new Point((int) (mousePosition.x*scale), (int) (mousePosition.y*scale));
	    	}
    	}
		
		if (border) {
			Graphics2D ssg = img.createGraphics();
			ssg.setColor(java.awt.Color.GRAY);
			ssg.drawRect(0, 0, img.getWidth()-1, img.getHeight()-1);
		}
		
		size = new Dimension(image.getWidth(), image.getHeight());
		
		regions = new ArrayList<Region>();
		if (prev==null) {
			regions.add(new Region(image));
		} else {
			BufferedImage pimg = prev.image.getImage();
			for (int x=0, w=img.getWidth(); x<w; ++x) {
				Y: for (int y=0, h=img.getHeight(); y<h; ++y) {
					++totalPixels;
					int newPixel = img.getRGB(x, y);
					int oldPixel = pimg.getRGB(x, y);
					if (newPixel!=oldPixel) {
						++differentPixels;
						for (Region region: regions) {
							if (region.merge(x, y)) {
								continue Y;
							}
						}
						regions.add(new Region(img, imageFormat, imageChannel, pimg, transparency, x, y, grabRange));
					}
				}
			}
			
			if (getDiffLevel()>diffThreshold) {
				regions.clear();
				regions.add(new Region(image));
			} else {				
				// Merging adjacent regions
				for (int i=0; i<regions.size()-1; ++i) {
					ListIterator<Region> lit = regions.listIterator(i+1);
					Region master = regions.get(i);
					while (lit.hasNext()) {
						if (master.merge(lit.next())) {
							lit.remove();
						}
					}
				}
				
				for (Region region: regions) {
					region.grabImage();
				}
			}
	
			// Eligible for garbage collection
			if (prev!=null) {
				prev.image=null;
			}
		}
		
		// De-dup
		ListIterator<Region> oit = regions.listIterator();
		R: while (oit.hasNext()) {
			Region or = oit.next();
			
			if (oit.hasPrevious()) {
				ListIterator<Region> iit = regions.listIterator(oit.previousIndex());
				while (iit.hasPrevious()) {
					if (or.dedup(iit.previous())) {
						continue R;
					}
				}
			}
			
			for (ScreenShot sibling=prev; sibling!=null; sibling=sibling.prev) {
				for (Region sr: sibling.regions) {
					if (or.dedup(sr)) {
						continue R;
					}
				}
			}				
		}
		return this;
	}

	public void dump(File dir, String imageFormat) throws IOException {
		for (int i=0; i<regions.size(); ++i) {
			BufferedImage img = regions.get(i).getImage().getImage();
			if (img!=null) {
				ImageIO.write(img, imageFormat, new File(dir, "s_"+secNo+"_"+i+"."+imageFormat));
			}
		}
	}
	
	public List<Region> getRegions() {
		return regions;
	}
	
	public int getSecNo() {
		return secNo;
	}
	
	public boolean isActive() {
		if (!regions.isEmpty()) {
			return true;
		}
		if (mousePosition==null) {
			if (prev==null) {
				return false;
			}
			if (prev.getMousePosition()!=null) {
				return true;
			}
			return false;
		}
		
		if (prev==null) {
			return true;
		}
		if (!mousePosition.equals(prev.getMousePosition())) {
			return true;
		}
		return false;
	}
	
	public Dimension getSize() {
		return size;
	}

}
