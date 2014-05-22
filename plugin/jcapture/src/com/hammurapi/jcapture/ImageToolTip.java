/*  
 * This file is an adapted example from javareference.com  
 * for more information visit,  
 * http://www.javareference.com  
 */
package com.hammurapi.jcapture;

import java.awt.Dimension;
import java.awt.FontMetrics;
import java.awt.Graphics;
import java.awt.Image;

import javax.swing.JComponent;
import javax.swing.JToolTip;
import javax.swing.SwingUtilities;
import javax.swing.plaf.metal.MetalToolTipUI;

/**
 * This class extends JToolTip and set the UI to ImageToolTipUI.
 * 
 * @author Rahul Sapkal(rahul@javareference.com)
 */
public class ImageToolTip extends JToolTip {

	/**
	 * This class extends MetalToolTipUI and provides customizes it to draw a
	 * given image on it.
	 * 
	 * @author Rahul Sapkal(rahul@javareference.com)
	 */
	private class ImageToolTipUI extends MetalToolTipUI {
		private Image m_image;

		public ImageToolTipUI(Image image) {
			m_image = image;
		}

		/**
		 * This method is overriden from the MetalToolTipUI to draw the given
		 * image and text
		 */
		public void paint(Graphics g, JComponent c) {
			FontMetrics metrics = c.getFontMetrics(g.getFont());
			g.setColor(c.getForeground());

			g.drawString(((ImageToolTip) c).text, 3, 15);

			g.drawImage(m_image, 3, metrics.getHeight() + 3, c);
		}

		/**
		 * This method is overriden from the MetalToolTipUI to return the
		 * appropiate preferred size to size the ToolTip to show both the text
		 * and image.
		 */
		public Dimension getPreferredSize(JComponent c) {
			FontMetrics metrics = c.getFontMetrics(c.getFont());
			String tipText = ((JToolTip) c).getTipText();
			if (tipText == null) {
				tipText = "";
			}

			int width = SwingUtilities.computeStringWidth(metrics, tipText);
			int height = metrics.getHeight() + m_image.getHeight(c) + 6;

			if (width < m_image.getWidth(c)) {
				width = m_image.getWidth(c);
			}

			return new Dimension(width, height);
		}
	}

	private String text;

	public ImageToolTip(String text, Image image) {
		this.text = text;
		setUI(new ImageToolTipUI(image));
	}
}
