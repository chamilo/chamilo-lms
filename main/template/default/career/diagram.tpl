{% extends 'layout/layout_1_col.tpl'|get_template %}

{% block content %}
    <script>
        mxBasePath = '{{ _p.web_lib }}mxgraph/src/';
    </script>
    <script type="text/javascript" src="{{ _p.web_lib }}mxgraph/src/js/mxClient.js"></script>
    <script>
        // Overridden to define per-shape connection points
        mxGraph.prototype.getAllConnectionConstraints = function(terminal, source) {
            if (terminal != null && terminal.shape != null) {
                if (terminal.shape.stencil != null) {
                    if (terminal.shape.stencil != null) {
                        return terminal.shape.stencil.constraints;
                    }
                } else if (terminal.shape.constraints != null) {
                    return terminal.shape.constraints;
                }
            }

            return null;
        };


        // Edges have no connection points
        mxPolyline.prototype.constraints = null;

        // Program starts here. Creates a sample graph in the
        // DOM node with the specified ID. This function is invoked
        // from the onLoad event handler of the document (see below).
        function main(container)
        {
            // Checks if the browser is supported
            if (!mxClient.isBrowserSupported())
            {
                // Displays an error message if the browser is not supported.
                mxUtils.error('Browser is not supported!', 200, false);
            }
            else
            {
                // Disables the built-in context menu
                mxEvent.disableContextMenu(container);

                // Creates the graph inside the given container
                var graph = new mxGraph(container);
                graph.setConnectable(true);
                graph.setHtmlLabels(true);

                // Blocks the selection of elements
                graph.setEnabled(false);

                // Enables connect preview for the default edge style
                graph.connectionHandler.createEdgeState = function(me)
                {
                    var edge = graph.createEdge(null, null, null, null, null);

                    return new mxCellState(this.graph.view, edge, this.graph.getCellStyle(edge));
                };

                // Specifies the default edge style
                graph.getStylesheet().getDefaultEdgeStyle()['edgeStyle'] = 'orthogonalEdgeStyle';

                // Enables rubberband selection
                new mxRubberband(graph);

                // Gets the default parent for inserting new cells. This
                // is normally the first child of the root (ie. layer 0).
                var parent = graph.getDefaultParent();

                // Adds cells to the model in a single step
                graph.getModel().beginUpdate();
                try {
                    //var v1 = graph.insertVertex(parent, null, 'Hello,', 20, 20, 80, 30);
                    //var v2 = graph.insertVertex(parent, null, 'World!', 200, 150, 80, 30);
                    //var e1 = graph.insertEdge(parent, null, '', v1, v2);

                    {% for vertex in group_list %}
                        {{ vertex }}
                    {% endfor %}

                    {% for vertex in subgroup_list %}
                        {{ vertex }}
                    {% endfor %}

                    {% for vertex in vertex_list %}
                        {{ vertex }}
                    {% endfor %}

                    {% for vertex in connections %}
                        {{ vertex }}
                    {% endfor %}
                } finally {
                    // Updates the display
                    graph.getModel().endUpdate();
                }
            }
        };

        $(document).ready(function () {
            main(document.getElementById('graphContainer'));
        });
    </script>

    {{ content }}
{% endblock %}