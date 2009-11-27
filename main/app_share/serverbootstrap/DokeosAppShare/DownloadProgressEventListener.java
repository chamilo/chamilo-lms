package DokeosAppShare;
import java.io.*;

public interface DownloadProgressEventListener
{
	void connecting();
	void started();
	void progressChange(int progress, int max);
	void done(File fileDest) throws Exception;
	void exception(Exception ex);
}
