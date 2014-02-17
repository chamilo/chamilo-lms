package com.hammurapi.jcapture;

import java.awt.Dimension;
import java.io.Closeable;
import java.io.IOException;
import java.text.MessageFormat;
import java.util.List;

public class Movie implements Closeable {
	
	private float framesPerSecond;
	private List<VideoEncoder.Fragment> fragments;
	private Dimension frameDimension;
	private Closeable imagesFileCloseable;	

	public Movie(Dimension frameDimension, float framesPerSecond, List<VideoEncoder.Fragment> fragments, Closeable imagesFileCloseable) {
		super();
		this.frameDimension = frameDimension;
		this.framesPerSecond = framesPerSecond;
		this.fragments = fragments;
		this.imagesFileCloseable = imagesFileCloseable;
	}

	public List<VideoEncoder.Fragment> getFragments() {
		return fragments;
	}
	
	public float getFramesPerSecond() {
		return framesPerSecond;
	}
	
	public Dimension getFrameDimension() {
		return frameDimension;
	}
	
	@Override
	public String toString() {
		int frames = 0;
		for (VideoEncoder.Fragment f: fragments) {
			frames+=f.getFrames().size();
		}
		
		long length = (long) (frames/framesPerSecond);
		
		return MessageFormat.format("{0,number,00}:{1,number,00}:{2,number,00}, {3} frames", length/3600, (length/60) % 60, length % 60, frames);
	}

	@Override
	public void close() throws IOException {
		if (imagesFileCloseable!=null) {
			imagesFileCloseable.close();
		}		
	}
	
}