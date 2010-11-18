import java.io.InputStream;


public class POCConnection {
	
	public static final String HOST = "127.0.0.1";
	public static final int PORT = 5900;

	/**
	 * @param args
	 */
	public static void main(String[] args) {
		
		try {
			NoctisRfbProto rfb = new NoctisRfbProto(HOST, PORT, new ConfigClientBean());
			System.out.println("TRACE: [POCConnection]-[main] - authentification : "+rfb.tryAuthenticate("", "1234")); //TODO: remove trace
			
			rfb.doProtocolInitialisation();
			
			InputStream in = rfb.is;
			System.out.println("reading...");
			int read = in.read();
			int c = 0;
			if (read<0) {
				System.out.println("nothing found on stream.");
			}
			while ((read >= 0)&&(c<30)) {
				System.out.print(read);
				read = in.read();
				c++;
			}
			in.close();
		} catch (Exception e) {
			// TODO Auto-generated catch block
			e.printStackTrace();
		}

	}

}
