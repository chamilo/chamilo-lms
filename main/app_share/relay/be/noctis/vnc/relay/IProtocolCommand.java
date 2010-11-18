package be.noctis.vnc.relay;

import java.util.Arrays;
import java.util.HashSet;
import java.util.Set;

public interface IProtocolCommand {
	
	public static final int SERVER_CONNECTION = 10;
	public static final int CLIENT_CONNECTION = 20;
	
	public static final int RELAY_CONNECTION = 30;
	
	public Set<Integer> ALL_COMMANDS = new HashSet<Integer>(Arrays.asList(new Integer[] { new Integer(SERVER_CONNECTION),
																					      new Integer(CLIENT_CONNECTION),
																					      new Integer(RELAY_CONNECTION)}));

}
