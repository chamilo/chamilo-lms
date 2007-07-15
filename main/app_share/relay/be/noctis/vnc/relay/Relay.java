package be.noctis.vnc.relay;

import java.io.IOException;
import java.io.InputStream;
import java.io.OutputStream;
import java.net.InetAddress;
import java.net.ServerSocket;
import java.net.Socket;
import java.util.HashMap;
import java.util.LinkedList;
import java.util.List;
import java.util.Map;

public class Relay {

	private ServerSocket listeningSocket;

	private List<ConnectClientThread> clientWaitServer = new LinkedList<ConnectClientThread>();

	private Map<String, Socket> commandSockets = new HashMap<String, Socket>();

	private class ConnectClientThread extends Thread {

		private int MAX_WAITING_CYCLE = 1000;

		private String serverID = null;

		private Socket clientSocket = null;

		private Socket serverSocket = null;

		public ConnectClientThread(String inServerID, Socket inClientSocket) {
			serverID = inServerID;
			clientSocket = inClientSocket;
		}

		public void run() {
			try {
				int waitingCycle = 0;
				while ((serverSocket == null) && waitingCycle < MAX_WAITING_CYCLE) {
					synchronized (serverID) {
						serverID.wait(10000);
					}
					waitingCycle++;
				}
				synchronized (clientWaitServer) {
					clientWaitServer.remove(this);
				}
				if (serverSocket != null) {
					System.out.println("New connection create with server : " + serverID);
					RelayThread serverClient = new RelayThread(serverSocket, clientSocket);
					RelayThread clientServer = new RelayThread(clientSocket, serverSocket);
					clientServer.touch();
					serverClient.start();
					clientServer.start();
				} else {
					System.out.println("Error when client create connection to server : " + serverID);
					try {
						clientSocket.close();
					} catch (IOException e) {
						e.printStackTrace();
					}
				}
			} catch (InterruptedException e) {
				e.printStackTrace();
			}
		}

		public String getServerID() {
			return serverID;
		}

		public void setServerSocket(Socket serverSocket) {
			this.serverSocket = serverSocket;
			synchronized (serverID) {
				serverID.notify();
			}
		}

	}

	private class RelayThread extends Thread {

		private Socket clientSocket = null;

		private Socket serverSocket = null;

		public RelayThread(Socket server, Socket client) {
			serverSocket = server;
			clientSocket = client;
		}

		public void touch() {
			try {
				clientSocket.getOutputStream().write(0);
			} catch (IOException e) {
				// TODO Auto-generated catch block
				e.printStackTrace();
			}
			System.out.println("touch");
		}

		@Override
		public void run() {
			System.out.println("start a relay");
			try {
				InputStream in = serverSocket.getInputStream();
				OutputStream out = clientSocket.getOutputStream();

				byte[] readBuffer = new byte[4096];

				int b = in.read();
				while (b >= 0) {
					out.write(b);
					if (in.available() == 0) {
						out.flush();
					} else {
						int readedLength = in.read(readBuffer);
						out.write(readBuffer, 0, readedLength);
						out.flush();
					}
					b = in.read();
				}

			} catch (IOException e) {
				e.printStackTrace();
				if (clientSocket != null) {
					try {
						clientSocket.close();
					} catch (IOException e1) {
						e1.printStackTrace();
					}
				}
				if (serverSocket != null) {
					try {
						serverSocket.close();
					} catch (IOException e1) {
						e1.printStackTrace();
					}
				}
			}
			System.out.println("stop a relay.");
		}
	}

	/**
	 * read a protocol command (first bytes send by client or server or command on command connection) protocol : [command][data length][data]*
	 * 
	 * @param inStream
	 * @return
	 * @throws IOException
	 */
	private ProtocolBean readProtocolCommand(InputStream inStream) throws IOException {
		ProtocolBean outBean = new ProtocolBean();
		int command = inStream.read();
		if (!IProtocolCommand.ALL_COMMANDS.contains(new Integer(command))) {
			throw new IOException("unknow command found as protocol : " + command);
		}
		outBean.setCommand(command);
		int codeSize = inStream.read();
		byte[] b = new byte[codeSize];
		inStream.read(b);
		outBean.setServerID(new String(b));
		return outBean;
	}

	private void sendProtocolCommand(OutputStream out, ProtocolBean bean) throws IOException {
		out.write((byte) bean.getCommand());
		byte[] data = bean.getServerID().getBytes();
		out.write(data.length);
		out.write(data);
		out.flush();
	}

	public void addClientSocket(String serverID, Socket inSocket) throws IOException {
		ConnectClientThread cc = new ConnectClientThread(serverID, inSocket);
		cc.start();
		synchronized (clientWaitServer) {
			clientWaitServer.add(cc);
		}
		askConnection(serverID);
	}

	public void askConnection(String serverID) throws IOException {

		Socket serverSocket = commandSockets.get(serverID);
		if (serverSocket != null) {
			synchronized (commandSockets) {
				ProtocolBean bean = new ProtocolBean();
				bean.setCommand(IProtocolCommand.RELAY_CONNECTION);
				// send server ID for check it when server receive this command.
				bean.setServerID(serverID);
				System.out.println("ask new connection to server local relay : " + serverID);
				sendProtocolCommand(serverSocket.getOutputStream(), bean);
			}
		} else {
			System.out.println("server socket not found : " + serverID);
		}
	}

	public void addServerSocket(String serverID, Socket inSocket) {
		commandSockets.put(serverID, inSocket);
	}

	public void addRelaySocket(String serverID, Socket inSocket) {
		ConnectClientThread needConnectionFound = null;
		synchronized (clientWaitServer) {
			for (ConnectClientThread cc : clientWaitServer) {
				if (cc.getServerID().equals(serverID)) {
					needConnectionFound = cc;
				}
			}
			if (needConnectionFound != null) {
				clientWaitServer.remove(needConnectionFound);
			}
		}
		needConnectionFound.setServerSocket(inSocket);
	}

	private class ClientThread extends Thread {

		private Socket newSocket = null;

		@Override
		public void run() {
			try {

				ProtocolBean bean = readProtocolCommand(newSocket.getInputStream());
				System.out.println("command receive : " + bean.getCommand() + " serverID : " + bean.getServerID());
				if (bean.getCommand() == IProtocolCommand.SERVER_CONNECTION) {
					addServerSocket(bean.getServerID(), newSocket);
				} else if (bean.getCommand() == IProtocolCommand.CLIENT_CONNECTION) {
					addClientSocket(bean.getServerID(), newSocket);
				} else if (bean.getCommand() == IProtocolCommand.RELAY_CONNECTION) {
					addRelaySocket(bean.getServerID(), newSocket);
				}

			} catch (Exception e) {
				e.printStackTrace();
			}
		}

		public void setNewSocket(Socket newSocket) {
			this.newSocket = newSocket;
		}
	}

	public void startRelay(int port, InetAddress ip) {
		try {
			String ipStr = "all";
			if (ip != null) {
				ipStr = ip.getHostAddress();
			}
			System.out.println("create server on [port:" + port + "] [ip=" + ipStr + "]");
			if (ip != null) {
				listeningSocket = new ServerSocket(port, 10, ip);
			} else {
				listeningSocket = new ServerSocket(port, 10);
			}
			for (;;) {
				System.out.println("Wait client...");
				Socket newSocket = listeningSocket.accept();
				System.out.println("New client connected.");
				ClientThread clientThread = new ClientThread();
				clientThread.setNewSocket(newSocket);
				clientThread.start();
			}
		} catch (IOException e) {
			e.printStackTrace();
		}
	}

	public static void main(String[] args) {
		Relay rel = new Relay();

		int port = 443;
		InetAddress ip = null;
		if (args.length > 0) {
			String portStr = args[0];
			try {
				port = Integer.parseInt(portStr);

				// ip defined
				if (args.length > 1) {
					String ipStr = args[1];
					ip = InetAddress.getByName(ipStr);
				}
			} catch (Throwable e) {
				e.printStackTrace();
				System.out.println("ERROR : parameter format : [Port number] [ip]");
				System.exit(-1);
			}
		}

		rel.startRelay(port, ip);
	}
}
