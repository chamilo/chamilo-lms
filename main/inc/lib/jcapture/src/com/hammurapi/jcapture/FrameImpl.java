package com.hammurapi.jcapture;

import java.awt.Dimension;
import java.awt.Point;
import java.util.List;

import com.hammurapi.jcapture.VideoEncoder.Fragment.Frame;

class FrameImpl implements Frame {
	
	private List<Shape> shapes;
	private Point mousePointer;
	private Dimension size;
	private boolean isActive;
	
	FrameImpl(List<Shape> shapes, Point mousePointer, Dimension size, boolean isActive) {
		super();
		this.shapes = shapes;
		this.mousePointer = mousePointer;
		this.size = size;
		this.isActive = isActive;
	}
	
	/**
	 * Merges frame before this frame into this frame by incorporating its shapes.
	 * This method is used for merging deleted frames.
	 * @param frame
	 */
	void merge(Frame frame) {
		for (Shape shape: shapes) {
			if (shape.getContent().coversEverything()) {
				return; // No need in previous shapes.
			}
		}
		shapes.addAll(0, frame.getShapes());
	}

	@Override
	public List<Shape> getShapes() {
		return shapes;
	}

	@Override
	public Point getMousePointer() {
		return mousePointer;
	}

	@Override
	public Dimension getSize() {
		return size;
	}

	@Override
	public boolean isActive() {
		return isActive;
	}
}
