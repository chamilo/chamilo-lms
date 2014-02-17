package com.hammurapi.jcapture;

import java.awt.image.BufferedImage;
import java.io.ByteArrayInputStream;
import java.io.ByteArrayOutputStream;
import java.io.IOException;
import java.lang.ref.Reference;
import java.lang.ref.SoftReference;
import java.nio.ByteBuffer;
import java.nio.MappedByteBuffer;
import java.nio.channels.FileChannel;
import java.nio.channels.FileChannel.MapMode;
import java.util.zip.Adler32;

import javax.imageio.ImageIO;

/**
 * Mapped image is softly kept in memory and also is written to a temporary file.
 * If image reference is cleared by the garbage collector, the image is loaded from the file on demand.
 * @author Pavel
 *
 */
public class MappedImage {
		
	private Reference<BufferedImage> imageRef;
	private Reference<byte[]> imageBytesRef;
	private MappedByteBuffer buffer;
	private int height;
	private int width;
	private String format;
	private long checksum;
	private int bytesLength;
	
	public MappedImage(final BufferedImage image, String format, FileChannel channel) throws IOException {	
		if (format==null) {
			throw new NullPointerException("Format is null");
		}
		
		class HardReference extends SoftReference<BufferedImage> {

			HardReference(BufferedImage referent) {
				super(referent);
			}
			
			@Override
			public BufferedImage get() {
				return image;
			}
			
		}
		imageRef = channel==null ? new HardReference(image) : new SoftReference<BufferedImage>(image);
		width = image.getWidth();
		height = image.getHeight();
		this.format = format;
		if (channel!=null) {
			ByteArrayOutputStream baos = new ByteArrayOutputStream();
			ImageIO.write(imageRef.get(), format, baos);
			baos.close();
			byte[] imageBytes = baos.toByteArray();
			Adler32 adler = new Adler32();
			adler.update(imageBytes);
			checksum = adler.getValue();
			bytesLength = imageBytes.length;
			imageBytesRef = new SoftReference<byte[]>(imageBytes);
			synchronized (channel) {
				long position = channel.position();
				channel.write(ByteBuffer.wrap(imageBytes));
				buffer = channel.map(MapMode.READ_ONLY, position, imageBytes.length);
			}
		}
	}
	
	public byte[] getImageBytes() throws IOException {
		if (imageBytesRef==null) {
			ByteArrayOutputStream baos = new ByteArrayOutputStream();
			ImageIO.write(imageRef.get(), format, baos);
			return baos.toByteArray();			
		}
		byte[] ret = imageBytesRef.get();
		if (ret==null) {
			buffer.load();
			buffer.rewind();
			ret = new byte[buffer.remaining()];
			buffer.get(ret);
			if (bytesLength != ret.length) {
				throw new IllegalStateException("Invalid image bytes length, expected "+bytesLength+", got "+ret.length);
			}
										
			Adler32 adler = new Adler32();
			adler.update(ret);
			if (checksum != adler.getValue()) {
				throw new IllegalStateException("Invalid image bytes checksum");
			}
			imageBytesRef = new SoftReference<byte[]>(ret);
		}
		return ret;
	}

	/**
	 * Reads from reference, if reference was cleared, loads from the mapped buffer.
	 * @return
	 * @throws IOException
	 */
	public BufferedImage getImage() throws IOException {
		BufferedImage ret = imageRef.get();
		if (ret==null) {
			ret = ImageIO.read(new ByteArrayInputStream(getImageBytes()));
			imageRef = new SoftReference<BufferedImage>(ret);
		}
		return ret;
	}
	
	public int getHeight() {
		return height;
	}
	
	public int getWidth() {
		return width;
	}

}
