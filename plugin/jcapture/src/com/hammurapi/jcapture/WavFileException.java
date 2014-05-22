package com.hammurapi.jcapture;

//This file was taken from http://www.labbookpages.co.uk/audio/javaWavFiles.html and package declaration was added.

public class WavFileException extends Exception
{
	public WavFileException()
	{
		super();
	}

	public WavFileException(String message)
	{
		super(message);
	}

	public WavFileException(String message, Throwable cause)
	{
		super(message, cause);
	}

	public WavFileException(Throwable cause) 
	{
		super(cause);
	}
}
