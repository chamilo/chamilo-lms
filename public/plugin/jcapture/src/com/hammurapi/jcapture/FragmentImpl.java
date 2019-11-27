package com.hammurapi.jcapture;

import java.io.File;
import java.util.List;

import com.hammurapi.jcapture.VideoEncoder.Fragment;

class FragmentImpl implements Fragment {		
	
	private File audio;
	private List<Frame> frames;
	
	FragmentImpl( List<Frame> frames, File audio) {
		this.audio = audio;
		this.frames = frames;
	}

	@Override
	public List<Frame> getFrames() {
		return frames;
	}

	@Override
	public File getAudio() {
		return audio;
	}

}
