package DokeosAppShare;
import java.net.*;
import java.io.*;
import java.util.*;

/**
 * Summary description for DownloadThread.
 */
public class DownloadThread extends Thread
{
	private List listeners = new LinkedList();
	private URL fileURL;
	private File fileDest;

	private boolean canceling = false;

	public DownloadThread(URL fileURL, File fileDest)
	{
		this.fileURL = fileURL;
		this.fileDest = fileDest;
	}

	public void cancel()
	{
		canceling = true;
	}

	public void run()
	{
		try
		{
			fireConnecting();
			URLConnection connection = fileURL.openConnection();
			int max = connection.getContentLength();
			InputStream in = connection.getInputStream();
			OutputStream out = new FileOutputStream(fileDest);
			fireStarted();
			fireProgressChange(0, max);
			{
				int count = 0;
				int b = 0;
				int readCount = 0;
				byte[] buffer = new byte[1024*10];

				b = in.read();
				while (!canceling && b >= 0)
				{
					out.write(b);
					count += 1;
					readCount = in.read(buffer, 0, buffer.length);
					out.write(buffer, 0, readCount);
					count += readCount;
					fireProgressChange(count, max);
					b = in.read();
				}
			}
			in.close();
			out.close();
			if (!canceling)
			{
				fireDone(fileDest);
			}
			else
			{
				//fireCancel(fileDest);
			}
		}
		catch (Exception ex)
		{
			fireException(ex);
			ex.printStackTrace();
		}
	}

	public void addDownloadProgressEventListener(DownloadProgressEventListener eventListener)
	{
		synchronized (listeners)
		{
			listeners.add(eventListener);
		}
	}

	public void removeDownloadProgressEventListener(DownloadProgressEventListener eventListener)
	{
		synchronized (listeners)
		{
			listeners.remove(eventListener);
		}
	}

	protected void fireConnecting()
	{
		Object[] ls;
		synchronized (listeners)
		{
			ls = listeners.toArray();
		}
		for (int i = 0; i < ls.length; i++)
		{
			((DownloadProgressEventListener)ls[i]).connecting();
		}
	}

	protected void fireStarted()
	{
		Object[] ls;
		synchronized (listeners)
		{
			ls = listeners.toArray();
		}
		for (int i = 0; i < ls.length; i++)
		{
			((DownloadProgressEventListener)ls[i]).started();
		}
	}

	protected void fireProgressChange(int progress, int max)
	{
		Object[] ls;
		synchronized (listeners)
		{
			ls = listeners.toArray();
		}
		for (int i = 0; i < ls.length; i++)
		{
			((DownloadProgressEventListener)ls[i]).progressChange(progress, max);
		}
	}

	protected void fireDone(File fileDest) throws Exception
	{
		Object[] ls;
		synchronized (listeners)
		{
			ls = listeners.toArray();
		}
		for (int i = 0; i < ls.length; i++)
		{
			((DownloadProgressEventListener)ls[i]).done(fileDest);
		}
	}
	protected void fireException(Exception ex)
	{
		Object[] ls;
		synchronized (listeners)
		{
			ls = listeners.toArray();
		}
		for (int i = 0; i < ls.length; i++)
		{
			((DownloadProgressEventListener)ls[i]).exception(ex);
		}
	}
}
