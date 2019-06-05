<div class="summary-legend">
    {{ table }}
</div>
<script>
    $(function() {
        $('.easypiechart-blue').easyPieChart({
            scaleColor: false,
            barColor: '#30a5ff',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });

        $('.easypiechart-red').easyPieChart({
            scaleColor: false,
            barColor: '#f9243f',
            lineWidth:8,
            trackColor: '#f2f2f2'
        });
    });
</script>