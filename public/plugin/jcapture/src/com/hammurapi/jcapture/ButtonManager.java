package com.hammurapi.jcapture;
import java.io.IOException;
import java.net.URL;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.zip.DataFormatException;

import com.flagstone.transform.DefineTag;
import com.flagstone.transform.Movie;
import com.flagstone.transform.MovieTag;
import com.flagstone.transform.Place2;
import com.flagstone.transform.datatype.CoordTransform;


public class ButtonManager {

	private final Map<String, Place2> buttons;
	private final List<DefineTag> definitions;

	public ButtonManager() {
		buttons = new HashMap<String, Place2>();
		definitions = new ArrayList<DefineTag>();
	}

	public void loadLibrary(URL libUrl) throws IOException, DataFormatException {
		Movie movie = new Movie();
		movie.decodeFromUrl(libUrl);
		findDefinitions(movie, definitions);
		findButtons(movie, buttons);
	}

	public int maxIdentifier() {
		int identifier = 0;
		DefineTag object;
		for (Iterator<DefineTag>iter = definitions.iterator(); iter.hasNext();) {
			object = iter.next();
			if (object.getIdentifier() > identifier) {
				identifier = object.getIdentifier();
			}
		}
		return identifier;
	}

	public List<DefineTag> getDefinitions() {
		List<DefineTag> list = new ArrayList<DefineTag>(definitions.size());
		for (Iterator<DefineTag>iter = definitions.iterator(); iter.hasNext();) {
			list.add((DefineTag) iter.next().copy());
		}
		return list;
	}

	public Place2 getButton(final String name, final int layer, final int xpos, final int ypos) {
		Place2 place = (Place2)buttons.get(name).copy();
		place.setLayer(layer);
		place.setTransform(new CoordTransform(1, 1, 0, 0, xpos, ypos));
		return place;
	}

	private void findDefinitions(final Movie movie, final List<DefineTag> list) {
		MovieTag object;
		for (Iterator<MovieTag> iter = movie.getObjects().iterator(); iter.hasNext();) {
			object = iter.next();
			if (object instanceof DefineTag) {
				list.add((DefineTag)object);
			}
		}
	}

	private void findButtons(final Movie movie, final Map<String, Place2> list) {
		MovieTag object;
		Place2 place;
		for (Iterator<MovieTag>iter = movie.getObjects().iterator(); iter.hasNext();) {
			object = iter.next();
			if (object instanceof Place2) {
				place = (Place2)object;
				if (place.getName() != null) {
					list.put(place.getName(), place);
				}
			}
		}
	}
}
