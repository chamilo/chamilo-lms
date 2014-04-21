package com.hammurapi.jcapture;

import java.awt.Point;

import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame.Shape;

class ShapeImpl implements Shape {
	
	static class ImageImpl implements Image {
		
		MappedImage image;
		private boolean coversEverything;		
		
		ImageImpl(MappedImage image, boolean coversEverything) {
			super();
			this.image = image;
			this.coversEverything = coversEverything;
		}

		@Override
		public boolean coversEverything() {
			return coversEverything;
		}

		@Override
		public MappedImage getImage() {
			return image;
		}
		
	}
	
	static class ImageReferenceImpl implements ImageReference {

		private Image image;
		
		ImageReferenceImpl(Image image) {
			super();
			if (image==null) {
				throw new NullPointerException();
			}
			this.image = image;
		}

		@Override
		public boolean coversEverything() {
			return image.coversEverything();
		}

		@Override
		public Image getImage() {
			return image;
		}
		
	}
	
	private Point location;
	private ShapeContent content;		

	ShapeImpl(Point location, ShapeContent content) {
		super();
		this.location = location;
		this.content = content;
	}

	@Override
	public Point getLocation() {
		return location;
	}

	@Override
	public ShapeContent getContent() {
		return content;
	}

}
