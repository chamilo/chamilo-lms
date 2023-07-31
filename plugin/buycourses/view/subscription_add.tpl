<div class="row">
    <div class="col-md-12">
        {{ items_form }}
    </div>
</div>
<script>
    $(function () {
        $("a[name='add']").click(function () {
            var selectedFrequency = $("#duration").val();
            var selectedFrequencyText = $("#duration option:selected").text();
            var selectedFrequencyPrice = $("#price").val();

            if (selectedFrequencyPrice === "0") {
                return;
            }

            var inputs = $("tbody tr td .frequency-days");

            for (var i = 0; i < inputs.length; i++){
                if (inputs[i].value === selectedFrequency) {
                    return;
                }
            }

            var count = $("tbody tr").length;
            var frequencyRow = '<tr><td><input class=\"frequency-days\" type="hidden" name=\"frequencies['+ (count + 1) + '][duration]\" value="'+selectedFrequency+'" />' + selectedFrequencyText + '</input></td><td><input type="hidden" name=\"frequencies['+ (count + 1) + '][price]\" value="' + selectedFrequencyPrice + '" />' + selectedFrequencyPrice + ' {{ currencyIso }} </td><td><a name=\"delete\" class=\"btn btn-danger btn-sm\"><em class=\"fa fa-remove\"></em></a></td></tr>';

            $("tbody").append(frequencyRow);
        });

        $("tbody").on("click", "tr td a", function(){
            var elementToDelete = $(this).closest("tr");
            elementToDelete.remove();
        });
    });
</script>

