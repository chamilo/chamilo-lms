package com.hammurapi.jcapture;

import java.awt.Component;
import java.awt.Cursor;
import java.awt.Dimension;
import java.awt.GraphicsEnvironment;
import java.awt.Insets;
import java.awt.Point;
import java.awt.Rectangle;
import java.awt.Window;
import java.awt.event.MouseAdapter;
import java.awt.event.MouseEvent;
import java.util.HashMap;
import java.util.Map;

import javax.swing.JComponent;
import javax.swing.SwingUtilities;

/**
 * The ComponentResizer allows you to resize a component by dragging a border of
 * the component.
 * 
 * Taken from http://tips4java.wordpress.com/2009/09/13/resizing-components/
 */
public class ComponentResizer extends MouseAdapter {
	private final static Dimension MINIMUM_SIZE = new Dimension(10, 10);
	private final static Dimension MAXIMUM_SIZE = new Dimension(
			Integer.MAX_VALUE, Integer.MAX_VALUE);

	private static Map<Integer, Integer> cursors = new HashMap<Integer, Integer>();
	{
		cursors.put(1, Cursor.N_RESIZE_CURSOR);
		cursors.put(2, Cursor.W_RESIZE_CURSOR);
		cursors.put(4, Cursor.S_RESIZE_CURSOR);
		cursors.put(8, Cursor.E_RESIZE_CURSOR);
		cursors.put(3, Cursor.NW_RESIZE_CURSOR);
		cursors.put(9, Cursor.NE_RESIZE_CURSOR);
		cursors.put(6, Cursor.SW_RESIZE_CURSOR);
		cursors.put(12, Cursor.SE_RESIZE_CURSOR);
	}

	private Insets dragInsets;
	private Dimension snapSize;

	private int direction;
	protected static final int NORTH = 1;
	protected static final int WEST = 2;
	protected static final int SOUTH = 4;
	protected static final int EAST = 8;

	private Cursor sourceCursor;
	private boolean resizing;
	private Rectangle bounds;
	private Point pressed;
	private boolean autoscrolls;

	private Dimension minimumSize = MINIMUM_SIZE;
	private Dimension maximumSize = MAXIMUM_SIZE;

	/**
	 * Convenience contructor. All borders are resizable in increments of a
	 * single pixel. Components must be registered separately.
	 */
	public ComponentResizer() {
		this(new Insets(5, 5, 5, 5), new Dimension(1, 1));
	}

	/**
	 * Convenience contructor. All borders are resizable in increments of a
	 * single pixel. Components can be registered when the class is created or
	 * they can be registered separately afterwards.
	 * 
	 * @param components
	 *            components to be automatically registered
	 */
	public ComponentResizer(Component... components) {
		this(new Insets(5, 5, 5, 5), new Dimension(1, 1), components);
	}

	/**
	 * Convenience contructor. Eligible borders are resisable in increments of a
	 * single pixel. Components can be registered when the class is created or
	 * they can be registered separately afterwards.
	 * 
	 * @param dragInsets
	 *            Insets specifying which borders are eligible to be resized.
	 * @param components
	 *            components to be automatically registered
	 */
	public ComponentResizer(Insets dragInsets, Component... components) {
		this(dragInsets, new Dimension(1, 1), components);
	}

	/**
	 * Create a ComponentResizer.
	 * 
	 * @param dragInsets
	 *            Insets specifying which borders are eligible to be resized.
	 * @param snapSize
	 *            Specify the dimension to which the border will snap to when
	 *            being dragged. Snapping occurs at the halfway mark.
	 * @param components
	 *            components to be automatically registered
	 */
	public ComponentResizer(Insets dragInsets, Dimension snapSize,
			Component... components) {
		setDragInsets(dragInsets);
		setSnapSize(snapSize);
		registerComponent(components);
	}

	/**
	 * Get the drag insets
	 * 
	 * @return the drag insets
	 */
	public Insets getDragInsets() {
		return dragInsets;
	}

	/**
	 * Set the drag dragInsets. The insets specify an area where mouseDragged
	 * events are recognized from the edge of the border inwards. A value of 0
	 * for any size will imply that the border is not resizable. Otherwise the
	 * appropriate drag cursor will appear when the mouse is inside the
	 * resizable border area.
	 * 
	 * @param dragInsets
	 *            Insets to control which borders are resizeable.
	 */
	public void setDragInsets(Insets dragInsets) {
		validateMinimumAndInsets(minimumSize, dragInsets);

		this.dragInsets = dragInsets;
	}

	/**
	 * Get the components maximum size.
	 * 
	 * @return the maximum size
	 */
	public Dimension getMaximumSize() {
		return maximumSize;
	}

	/**
	 * Specify the maximum size for the component. The component will still be
	 * constrained by the size of its parent.
	 * 
	 * @param maximumSize
	 *            the maximum size for a component.
	 */
	public void setMaximumSize(Dimension maximumSize) {
		this.maximumSize = maximumSize;
	}

	/**
	 * Get the components minimum size.
	 * 
	 * @return the minimum size
	 */
	public Dimension getMinimumSize() {
		return minimumSize;
	}

	/**
	 * Specify the minimum size for the component. The minimum size is
	 * constrained by the drag insets.
	 * 
	 * @param minimumSize
	 *            the minimum size for a component.
	 */
	public void setMinimumSize(Dimension minimumSize) {
		validateMinimumAndInsets(minimumSize, dragInsets);

		this.minimumSize = minimumSize;
	}

	/**
	 * Remove listeners from the specified component
	 * 
	 * @param component
	 *            the component the listeners are removed from
	 */
	public void deregisterComponent(Component... components) {
		for (Component component : components) {
			component.removeMouseListener(this);
			component.removeMouseMotionListener(this);
		}
	}

	/**
	 * Add the required listeners to the specified component
	 * 
	 * @param component
	 *            the component the listeners are added to
	 */
	public void registerComponent(Component... components) {
		for (Component component : components) {
			component.addMouseListener(this);
			component.addMouseMotionListener(this);
		}
	}

	/**
	 * Get the snap size.
	 * 
	 * @return the snap size.
	 */
	public Dimension getSnapSize() {
		return snapSize;
	}

	/**
	 * Control how many pixels a border must be dragged before the size of the
	 * component is changed. The border will snap to the size once dragging has
	 * passed the halfway mark.
	 * 
	 * @param snapSize
	 *            Dimension object allows you to separately spcify a horizontal
	 *            and vertical snap size.
	 */
	public void setSnapSize(Dimension snapSize) {
		this.snapSize = snapSize;
	}

	/**
	 * When the components minimum size is less than the drag insets then we
	 * can't determine which border should be resized so we need to prevent this
	 * from happening.
	 */
	private void validateMinimumAndInsets(Dimension minimum, Insets drag) {
		int minimumWidth = drag.left + drag.right;
		int minimumHeight = drag.top + drag.bottom;

		if (minimum.width < minimumWidth || minimum.height < minimumHeight) {
			String message = "Minimum size cannot be less than drag insets";
			throw new IllegalArgumentException(message);
		}
	}

	/**
	 */
	@Override
	public void mouseMoved(MouseEvent e) {
		Component source = e.getComponent();
		Point location = e.getPoint();
		direction = 0;

		if (location.x < dragInsets.left)
			direction += WEST;

		if (location.x > source.getWidth() - dragInsets.right - 1)
			direction += EAST;

		if (location.y < dragInsets.top)
			direction += NORTH;

		if (location.y > source.getHeight() - dragInsets.bottom - 1)
			direction += SOUTH;

		// Mouse is no longer over a resizable border

		if (direction == 0) {
			source.setCursor(sourceCursor);
		} else // use the appropriate resizable cursor
		{
			int cursorType = cursors.get(direction);
			Cursor cursor = Cursor.getPredefinedCursor(cursorType);
			source.setCursor(cursor);
		}
	}

	@Override
	public void mouseEntered(MouseEvent e) {
		if (!resizing) {
			Component source = e.getComponent();
			sourceCursor = source.getCursor();
		}
	}

	@Override
	public void mouseExited(MouseEvent e) {
		if (!resizing) {
			Component source = e.getComponent();
			source.setCursor(sourceCursor);
		}
	}

	@Override
	public void mousePressed(MouseEvent e) {
		// The mouseMoved event continually updates this variable

		if (direction == 0)
			return;

		// Setup for resizing. All future dragging calculations are done based
		// on the original bounds of the component and mouse pressed location.

		resizing = true;

		Component source = e.getComponent();
		pressed = e.getPoint();
		SwingUtilities.convertPointToScreen(pressed, source);
		bounds = source.getBounds();

		// Making sure autoscrolls is false will allow for smoother resizing
		// of components

		if (source instanceof JComponent) {
			JComponent jc = (JComponent) source;
			autoscrolls = jc.getAutoscrolls();
			jc.setAutoscrolls(false);
		}
	}

	/**
	 * Restore the original state of the Component
	 */
	@Override
	public void mouseReleased(MouseEvent e) {
		resizing = false;

		Component source = e.getComponent();
		source.setCursor(sourceCursor);

		if (source instanceof JComponent) {
			((JComponent) source).setAutoscrolls(autoscrolls);
		}
	}

	/**
	 * Resize the component ensuring location and size is within the bounds of
	 * the parent container and that the size is within the minimum and maximum
	 * constraints.
	 * 
	 * All calculations are done using the bounds of the component when the
	 * resizing started.
	 */
	@Override
	public void mouseDragged(MouseEvent e) {
		if (resizing == false)
			return;

		Component source = e.getComponent();
		Point dragged = e.getPoint();
		SwingUtilities.convertPointToScreen(dragged, source);

		changeBounds(source, direction, bounds, pressed, dragged);
	}

	protected void changeBounds(Component source, int direction,
			Rectangle bounds, Point pressed, Point current) {
		// Start with original locaton and size

		int x = bounds.x;
		int y = bounds.y;
		int width = bounds.width;
		int height = bounds.height;

		// Resizing the West or North border affects the size and location

		if (WEST == (direction & WEST)) {
			int drag = getDragDistance(pressed.x, current.x, snapSize.width);
			int maximum = Math.min(width + x, maximumSize.width);
			drag = getDragBounded(drag, snapSize.width, width,
					minimumSize.width, maximum);

			x -= drag;
			width += drag;
		}

		if (NORTH == (direction & NORTH)) {
			int drag = getDragDistance(pressed.y, current.y, snapSize.height);
			int maximum = Math.min(height + y, maximumSize.height);
			drag = getDragBounded(drag, snapSize.height, height,
					minimumSize.height, maximum);

			y -= drag;
			height += drag;
		}

		// Resizing the East or South border only affects the size

		if (EAST == (direction & EAST)) {
			int drag = getDragDistance(current.x, pressed.x, snapSize.width);
			Dimension boundingSize = getBoundingSize(source);
			int maximum = Math.min(boundingSize.width - x, maximumSize.width);
			drag = getDragBounded(drag, snapSize.width, width,
					minimumSize.width, maximum);
			width += drag;
		}

		if (SOUTH == (direction & SOUTH)) {
			int drag = getDragDistance(current.y, pressed.y, snapSize.height);
			Dimension boundingSize = getBoundingSize(source);
			int maximum = Math.min(boundingSize.height - y, maximumSize.height);
			drag = getDragBounded(drag, snapSize.height, height,
					minimumSize.height, maximum);
			height += drag;
		}

		source.setBounds(x, y, width, height);
		source.validate();
	}

	/*
	 * Determine how far the mouse has moved from where dragging started
	 */
	private int getDragDistance(int larger, int smaller, int snapSize) {
		int halfway = snapSize / 2;
		int drag = larger - smaller;
		drag += (drag < 0) ? -halfway : halfway;
		drag = (drag / snapSize) * snapSize;

		return drag;
	}

	/*
	 * Adjust the drag value to be within the minimum and maximum range.
	 */
	private int getDragBounded(int drag, int snapSize, int dimension,
			int minimum, int maximum) {
		while (dimension + drag < minimum)
			drag += snapSize;

		while (dimension + drag > maximum)
			drag -= snapSize;

		return drag;
	}

	/*
	 * Keep the size of the component within the bounds of its parent.
	 */
	private Dimension getBoundingSize(Component source) {
		if (source instanceof Window) {
			GraphicsEnvironment env = GraphicsEnvironment
					.getLocalGraphicsEnvironment();
			Rectangle bounds = env.getMaximumWindowBounds();
			return new Dimension(bounds.width, bounds.height);
		} else {
			return source.getParent().getSize();
		}
	}
}
