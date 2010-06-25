package be.noctis.vnc.relay;

public class ProtocolBean {
	
	private int command = -1;
	private String serverID = null;
	
	public int getCommand() {
		return command;
	}
	
	public void setCommand(int command) {
		this.command = command;
	}
	
	public String getServerID() {
		return serverID;
	}
	
	public void setServerID(String serverID) {
		this.serverID = serverID;
	}
	
}
