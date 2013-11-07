package com.hammurapi.jcapture;

import java.awt.Color;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.image.BufferedImage;
import java.io.IOException;
import java.nio.channels.FileChannel;

public class Region extends Rectangle {
	
	private static final int TRANSPARENT_COLOR = new Color(0,0,0,0).getRGB();
	
	private BufferedImage master;
	private BufferedImage prev;
	private int grabRange;
	private MappedImage image;
	private boolean transparency;

	public Region(BufferedImage master, String format, FileChannel channel, BufferedImage prev, boolean transparency, int x, int y, int grabRange) {
		this.master = master;
		this.format = format;
		this.channel = channel;
		this.prev = prev;
		this.transparency = transparency;
		this.grabRange = grabRange;		
		
		setBounds(x-grabRange, y-grabRange, grabRange*2+1, grabRange*2+1);
	}
	
	/**
	 * Special case when region covers the whole image.
	 * @param master
	 * @param x
	 * @param y
	 * @param grabRange
	 * @throws IOException 
	 */
	public Region(MappedImage master) throws IOException {
		this.image = master;
		this.grabRange = 0;		
		imageLocation = new Point(0,0);
		coversEverything = true;
		
		setBounds(0,0,master.getWidth(),master.getHeight());
		BufferedImage img = master.getImage();
		for (int sx=0, sw=master.getWidth(); sx<sw; ++sx) {
			for (int sy=0, sh=master.getHeight(); sy<sh; ++sy) {
				imageHash^=img.getRGB(sx, sy);
				Long.rotateRight(imageHash, 1);
			}
		}
	}	
	
	private Point imageLocation;

	private String format;

	private FileChannel channel;
	
	public void grabImage() throws IOException {
		if (image==null) {
			imageLocation = new Point(Math.max(0, x), Math.max(0, y));
			
			int imageWidth = width; 			
			int widthDelta = imageWidth+imageLocation.x - master.getWidth();
			if (widthDelta>0) {
				imageWidth-=widthDelta;
			}
			
			int imageHeight = height;
			int heightDelta = imageHeight+imageLocation.y - master.getHeight();
			if (heightDelta>0) {
				imageHeight-=heightDelta;
			}
			
			BufferedImage bImage = new BufferedImage(imageWidth, imageHeight, BufferedImage.TYPE_INT_ARGB);
			for (int x=0; x<imageWidth; ++x) {
				for (int y=0; y<imageHeight; ++y) {					
					int xt = x + imageLocation.x;
					int yt = y + imageLocation.y;
					int newPixel = master.getRGB(xt, yt);
					int oldPixel = prev.getRGB(xt, yt);
					int pixelRGB = newPixel==oldPixel && transparency ? TRANSPARENT_COLOR : newPixel;
					bImage.setRGB(x, y, pixelRGB);
					imageHash^=pixelRGB;
					Long.rotateRight(imageHash, 1);
				}
			}
			
			image = new MappedImage(bImage, format, channel);
			
			//For debugging
//			Graphics2D ssg = image.createGraphics();
//			ssg.setColor(java.awt.Color.GRAY);
//			ssg.drawRect(0, 0, image.getWidth()-1, image.getHeight()-1);
						
			// Make eligible for garbage collection.
			master = null;
			prev = null;
		}
	}
	
	public MappedImage getImage() {
		return image;
	}
		
	public Point getImageLocation() {
		return imageLocation;
	}
	
	/**
	 * Add point, extend region if added (if it is within grab range). Returns true if point was added
	 */
	boolean merge(int x, int y) {
		if (image!=null) {
			throw new IllegalStateException("Image already grabbed");
		}
		if (contains(x, y)) {			
			int newMinX = Math.min(x-grabRange, this.x);
			int newMinY = Math.min(y-grabRange, this.y);
			int newMaxX = Math.max(x+grabRange, this.x+this.width);
			int newMaxY = Math.max(y+grabRange, this.y+this.height);
			setBounds(newMinX, newMinY, newMaxX-newMinX, newMaxY-newMinY);
			return true;
		}
		return false;
	}
	
	/**
	 * 
	 * @param region
	 * @return True if region is within grab range and was merged.
	 */
	boolean merge(Region region) {
		if (region==this) {
			throw new IllegalArgumentException("Self-merge");
		}
		if (image!=null) {
			throw new IllegalStateException("Image already grabbed");
		}
		if (intersects(region)) {
			int newMinX = Math.min(region.x, this.x);
			int newMinY = Math.min(region.y, this.y);
			int newMaxX = Math.max(region.x+region.width, this.x+this.width);
			int newMaxY = Math.max(region.y+region.height, this.y+this.height);
			setBounds(newMinX, newMinY, newMaxX-newMinX, newMaxY-newMinY);
			return true;			
		}
		return false;
	}
		
	private Region masterImageRegion;
	
	/**
	 * @return Region with the identical image.
	 */
	public Region getMasterImageRegion() {
		return masterImageRegion;
	}

	/**
	 * Sets master region with identical image.
	 * @param masterImageRegion
	 */
	public void setMasterImageRegion(Region masterImageRegion) {
		this.masterImageRegion = masterImageRegion;
		while (this.masterImageRegion.getMasterImageRegion()!=null) {
			this.masterImageRegion = this.masterImageRegion.getMasterImageRegion();
		}
	}

	public boolean imageEquals(Region other) throws IOException {
		if (image==null) {
			throw new IllegalStateException("Image not grabbed");
		}
				
		MappedImage otherImage = other.getImage();		
		if (otherImage==null 
				|| imageHash!=other.imageHash 
				|| image.getHeight()!=otherImage.getHeight() 
				|| image.getWidth()!=otherImage.getWidth()) {
			return false;
		}
		
		BufferedImage bImage = image.getImage();
		BufferedImage oImage = otherImage.getImage();
		for (int sx=0, sw=bImage.getWidth(); sx<sw; ++sx) {
			for (int sy=0, sh=bImage.getHeight(); sy<sh; ++sy) {
				if (bImage.getRGB(sx, sy)!=oImage.getRGB(sx, sy)) {
					return false;
				}
			}
		}	
		return true;
	}
	
	private long imageHash;
	
	/**
	 * Analyzes if this region image is a duplicate of the argument region image and if so sets it as its master. 
	 * @param sr
	 * @return true if duplicate.
	 * @throws IOException 
	 */
	public boolean dedup(Region sr) throws IOException {
		if (imageEquals(sr)) {
			setMasterImageRegion(sr);
			image=null;
			return true;
		}
		return false;
	}
	
	public boolean coversEverything() {
		return coversEverything;
	}
	
	private boolean coversEverything;	

}
