var CalculatedAnswerManager = (function() {
  'use strict';

  var blanksData = {};
  var formulasData = {};

  /**
   * Initialise les données depuis le PHP
   */
  function initData(blanks, formulas) {
    blanksData = blanks || {};
    formulasData = formulas || {};
  }

  function extractVariablesAndFormulas(text) {
    var blanks = [];
    var formulas = [];

    // Extraire les variables [#nom]
    var blankMatches = text.match(/\[#([a-zA-Z0-9_]+)\]/g);
    if (blankMatches) {
      blankMatches.forEach(function(match) {
        var name = match.replace(/\[#|\]/g, "");
        if (blanks.indexOf(name) === -1) {
          blanks.push(name);
        }
      });
    }

    // Extraire les formules [=nom]
    var formulaMatches = text.match(/\[=([a-zA-Z0-9_]+)\]/g);
    if (formulaMatches) {
      formulaMatches.forEach(function(match) {
        var name = match.replace(/\[=|\]/g, "");
        if (formulas.indexOf(name) === -1) {
          formulas.push(name);
        }
      });
    }

    return { blanks: blanks, formulas: formulas };
  }

  function updateDynamicFields() {
    var editor = window.tinymce && tinymce.get("answer");
    var text = editor ? editor.getContent() : (document.getElementById("answer") ? document.getElementById("answer").value : "");
    var extracted = extractVariablesAndFormulas(text);
    renderBlankFields(extracted.blanks);
    renderFormulaFields(extracted.formulas);
  }

  /**
   * Affiche les champs pour les variables
   */
  function renderBlankFields(blanks) {
    var container = document.getElementById("blanks_container");
    if (!container) return;

    var html = "<h4>Variables</h4>";

    if (blanks.length === 0) {
      html += "<p class=\"text-muted\">Aucune variable définie.</p>";
    } else {
      html += "<table class=\"table table-bordered\">";
      html += "<tbody>";
      html += "<tr><td><strong>Variable</strong></td><td><strong>Intervalles (ex: 1-10 ou 220|330|440 ou 1-10|2 ou 1-10; 20-30)</strong></td><td><strong>Décimales</strong></td></tr>";

      blanks.forEach(function(name) {
        var intervals = blanksData[name] ? blanksData[name].intervals : "";
        var decimals = blanksData[name] ? blanksData[name].decimals : 0;

        html += "<tr>";
        html += "<td><strong>[#" + name + "]</strong></td>";
        html += "<td><input type=\"text\" name=\"blank_intervals_" + name + "\" value=\"" + intervals + "\" class=\"form-control\" placeholder=\"1-10 ou 220|330|440 ou 1-10|2 ou 1-10; 20-30\" onchange=\"CalculatedAnswerManager.updateBlankData('" + name + "')\" /></td>";
        html += "<td><input type=\"number\" name=\"blank_decimals_" + name + "\" value=\"" + decimals + "\" min=\"0\" max=\"10\" class=\"form-control\" style=\"width:80px\" onchange=\"CalculatedAnswerManager.updateBlankData('" + name + "')\" /></td>";
        html += "<td><button type=\"button\" class=\"btn btn-sm btn-info\" onclick=\"CalculatedAnswerManager.showSample('" + name + "')\">📊</button></td>";
        html += "</tr>";
      });
      html += "</tbody></table>";
    }
    container.innerHTML = html;
  }

  /**
   * Affiche les champs pour les formules
   */
  function renderFormulaFields(formulas) {
    var container = document.getElementById("formulas_container");
    if (!container) return;

    var html = "<h4>Formules</h4>";

    if (formulas.length === 0) {
      html += "<p class=\"text-muted\">Aucune formule définie. Utilisez [=nom] dans votre texte.</p>";
    } else {
      html += "<table class=\"table table-bordered\">";
      html += "<tbody>";
      html += "<tr><td><strong>Formule</strong></td><td><strong>Expression</strong></td><td><strong>Tolérance</strong></td><td><strong>Type</strong></td><td><strong>Décimales</strong></td><td><strong>Score</strong></td></tr>";

      formulas.forEach(function(name) {
        var data = formulasData[name] || {
          formula: "",
          tolerance: 0,
          toleranceType: "digit",
          decimals: 2,
          score: 10
        };

        html += "<tr>";
        html += "<td><strong>[=" + name + "]</strong></td>";
        html += "<td><input type=\"text\" name=\"formula_expression_" + name + "\" value=\"" + data.formula + "\" class=\"form-control\" placeholder=\"a+b\" required onchange=\"CalculatedAnswerManager.updateFormulaData('" + name + "')\" /></td>";
        html += "<td><input type=\"number\" name=\"formula_tolerance_" + name + "\" value=\"" + data.tolerance + "\" min=\"0\" step=\"0.1\" class=\"form-control\" style=\"width:80px\" onchange=\"CalculatedAnswerManager.updateFormulaData('" + name + "')\" /></td>";
        html += "<td><select name=\"formula_tolerancetype_" + name + "\" class=\"form-control\" style=\"width:100px\" onchange=\"CalculatedAnswerManager.updateFormulaData('" + name + "')\">";
        html += "<option value=\"digit\"" + (data.toleranceType === "digit" ? " selected" : "") + ">±</option>";
        html += "<option value=\"percent\"" + (data.toleranceType === "percent" ? " selected" : "") + ">%</option>";
        html += "</select></td>";
        html += "<td><input type=\"number\" name=\"formula_decimals_" + name + "\" value=\"" + data.decimals + "\" min=\"0\" max=\"10\" class=\"form-control\" style=\"width:80px\" onchange=\"CalculatedAnswerManager.updateFormulaData('" + name + "')\" /></td>";
        html += "<td><input type=\"number\" name=\"formula_score_" + name + "\" value=\"" + data.score + "\" min=\"0\" step=\"0.5\" class=\"form-control\" style=\"width:80px\" required onchange=\"CalculatedAnswerManager.updateFormulaData('" + name + "')\" /></td>";
        html += "</tr>";
      });

      html += "</tbody></table>";
    }

    container.innerHTML = html;
  }

  /**
   * Met à jour les données d'une variable
   */
  function updateBlankData(name) {
    blanksData[name] = {
      intervals: document.querySelector("[name=blank_intervals_" + name + "]").value,
      decimals: document.querySelector("[name=blank_decimals_" + name + "]").value
    };
  }

  /**
   * Met à jour les données d'une formule
   */
  function updateFormulaData(name) {
    formulasData[name] = {
      formula: document.querySelector("[name=formula_expression_" + name + "]").value,
      tolerance: parseFloat(document.querySelector("[name=formula_tolerance_" + name + "]").value) || 0,
      toleranceType: document.querySelector("[name=formula_tolerancetype_" + name + "]").value,
      decimals: parseInt(document.querySelector("[name=formula_decimals_" + name + "]").value) || 0,
      score: parseFloat(document.querySelector("[name=formula_score_" + name + "]").value) || 0
    };
  }


 function generateRandomFromIntervals(intervals, decimals, calculatedValues) {
   calculatedValues = calculatedValues || {};
   intervals = String(intervals).replace(/,/g, '.');

   // Si c'est un nombre simple
   if (!isNaN(intervals)) return parseFloat(intervals);

   if (calculatedValues.hasOwnProperty(intervals)) {
     return parseFloat(calculatedValues[intervals]);
   }

   var intervalList = intervals.split(";").map(function(i){ return i.trim(); });
   var chosen = intervalList[Math.floor(Math.random() * intervalList.length)];

   if (calculatedValues.hasOwnProperty(chosen)) {
     return parseFloat(calculatedValues[chosen]);
   }
   if (!isNaN(chosen)) {
     return parseFloat(chosen);
   }
   // Liste de nombres fixes ou intervalle avec pas
   if (chosen.indexOf('|') !== -1) {
     var parts = chosen.split('|');
     var allNumeric = true;

     for (var i = 0; i < parts.length; i++) {
       if (isNaN(parts[i].trim())) {
         allNumeric = false;
         break;
       }
     }

     if (allNumeric) {
       var randomValue = parts[Math.floor(Math.random() * parts.length)];
       return parseFloat(randomValue);
     }

     // Intervalle avec pas (1-10|2)
     var stepParts = chosen.split('|');
     chosen = stepParts[0];
     var step = parseFloat(stepParts[1]);

     var intervalParts = chosen.match(/^(-?[^-]+)-(-?[^-]+)$/);
     if (!intervalParts) return 0;

     var min = parseFloat(intervalParts[1]);
     var max = parseFloat(intervalParts[2]);

     if (min > max) {
       var temp = min;
       min = max;
       max = temp;
     }

     var steps = Math.floor((max - min) / step);
     var randomStep = Math.floor(Math.random() * (steps + 1));
     return min + (randomStep * step);
   }

   // Intervalle simple (1-10)
   var parts = chosen.match(/^(-?[^-]+)-(-?[^-]+)$/);

   if (!parts) return 0;

    var min = parseFloat(parts[1]);
    var max = parseFloat(parts[2]);

    if (min > max) {
      var temp = min;
      min = max;
      max = temp;
    }

    if (decimals === 0) {
      return Math.floor(Math.random() * (max - min + 1)) + min;
    } else {
      var value = Math.random() * (max - min) + min;
      return parseFloat(value.toFixed(decimals));
    }
  }

  function testFormulas(ajaxUrl) {
    if (Object.keys(blanksData).length === 0) {
      document.getElementById("testArea").innerHTML = "<div class=\"alert alert-warning\">Aucune variable définie</div>";
      return;
    }
    if (Object.keys(formulasData).length === 0) {
      document.getElementById("testArea").innerHTML = "<div class=\"alert alert-warning\">Aucune formule définie</div>";
      return;
    }

    var calculatedValues = {}; // Stocke toutes les valeurs calculées
    var html = "<table class=\"table table-bordered\"><tbody>";
    html += "<tr><td colspan=\"2\"><strong>Valeurs aléatoires générées</strong></td></tr>";

    // Générer les valeurs aléatoires pour les variables
    for (var blankName in blanksData) {
      var intervals = blanksData[blankName].intervals;
      var decimals = parseInt(blanksData[blankName].decimals) || 0;
      var value = generateRandomFromIntervals(intervals, decimals, calculatedValues);
      calculatedValues[blankName] = value;
      html += "<tr><td><strong>[#" + blankName + "]</strong></td><td>" + value + "</td></tr>";
    }

    html += "<tr><td colspan=\"2\"><hr></td></tr>";
    html += "<tr><td colspan=\"2\"><strong>Résultats des formules</strong></td></tr>";

    var formulaCount = 0;
    var totalFormulas = Object.keys(formulasData).length;
    var formulaNames = Object.keys(formulasData);

    function evaluateFormula(index) {
      if (index >= formulaNames.length) {
        html += "</tbody></table>";
        document.getElementById("testArea").innerHTML = html;
        return;
      }

      var formulaName = formulaNames[index];
      var formulaInfo = formulasData[formulaName];
      var formula = formulaInfo.formula;
      var evaluatedFormula = formula;

      for (var varName in calculatedValues) {
        var regex = new RegExp("\\b" + varName + "\\b", "g");
        evaluatedFormula = evaluatedFormula.replace(regex, calculatedValues[varName]);
      }

      $.ajax({
        url: ajaxUrl,
        type: "POST",
        data: {
          a: "calculated_question_result",
          formula: evaluatedFormula,
          toleranceValue: formulaInfo.tolerance,
          toleranceType: formulaInfo.toleranceType,
          digitNumber: formulaInfo.decimals
        },
        dataType: "json",
        success: function(response) {
          formulaCount++;
          if (response && response.length === 3 && response[0] !== "error") {
            var result = response[0];
            var min = response[1];
            var max = response[2];

            calculatedValues[formulaName] = result;

            var toleranceInfo = "";
            if (formulaInfo.tolerance > 0) {
              toleranceInfo = " (±" + (formulaInfo.toleranceType === "percent" ? formulaInfo.tolerance + "%" : formulaInfo.tolerance) + ")";
              html += "<tr><td><strong>[=" + formulaName + "]</strong> = " + formula + "</td><td>" + result + toleranceInfo + "<br><small class=\"text-muted\">Accepté: [" + min + " - " + max + "]</small></td></tr>";
            } else {
              html += "<tr><td><strong>[=" + formulaName + "]</strong> = " + formula + "</td><td>" + result + "</td></tr>";
            }
          } else {
            html += "<tr><td><strong>[=" + formulaName + "]</strong></td><td><span class=\"text-danger\">Erreur</span></td></tr>";
          }

          evaluateFormula(index + 1);
        },
        error: function() {
          html += "<tr><td><strong>[=" + formulaName + "]</strong></td><td><span class=\"text-danger\">Erreur AJAX</span></td></tr>";
          evaluateFormula(index + 1);
        }
      });
    }

    evaluateFormula(0);
  }


  function init() {
    document.addEventListener("DOMContentLoaded", function () {
      if (!window.tinymce) return;

      tinymce.on("AddEditor", function (e) {
        const editor = e.editor;

        if (editor.id !== "answer") return;

        const events = [
          "change",
          "keyup",
          "paste",
          "undo",
          "redo",
          "setContent",
          "input"
        ];

        editor.on(events.join(" "), updateDynamicFields);

        updateDynamicFields(); // première mise à jour
      });
    });
  }


  init();
  /**
   * Affiche un popup avec échantillon de 100 tirages
   */
  function showSample(varName) {
    var intervals = blanksData[varName] ? blanksData[varName].intervals : "";
    var decimals = parseInt(blanksData[varName] ? blanksData[varName].decimals : 0);

    if (!intervals) {
      alert("Veuillez d'abord définir un intervalle pour cette variable");
      return;
    }

    var sampleHtml = calculateSample(intervals, decimals);

    var modalHtml = '<div id="sampleModal" style="position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.3); z-index: 10000; max-width: 90%; max-height: 90%; overflow: auto;">';
    modalHtml += '<h4>Échantillon de 100 tirages - Variable [#' + varName + ']</h4>';
    modalHtml += '<p><strong>Intervalle:</strong> ' + intervals + ' | <strong>Décimales:</strong> ' + decimals + '</p>';
    modalHtml += '<div id="sampleContent">' + sampleHtml + '</div>';
    modalHtml += '<br><button class="btn btn-primary" onclick="CalculatedAnswerManager.redoSample(\'' + varName + '\')">🔄 Nouveau tirage</button> ';
    modalHtml += '<button class="btn btn-default" onclick="CalculatedAnswerManager.closeSample()">Fermer</button>';
    modalHtml += '</div>';
    modalHtml += '<div id="sampleOverlay" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;" onclick="CalculatedAnswerManager.closeSample()"></div>';

    document.body.insertAdjacentHTML('beforeend', modalHtml);
  }

  function redoSample(varName) {
    var intervals = blanksData[varName].intervals;
    var decimals = parseInt(blanksData[varName].decimals);
    var sampleHtml = calculateSample(intervals, decimals);
    document.getElementById("sampleContent").innerHTML = sampleHtml;
  }

  function closeSample() {
    var modal = document.getElementById("sampleModal");
    var overlay = document.getElementById("sampleOverlay");
    if (modal) modal.remove();
    if (overlay) overlay.remove();
  }

  function calculateSample(intervals, decimals) {
    var values = {};
    var keyArray = [];

    for (var i = 0; i < 100; i++) {
      var data = generateRandomFromIntervals(intervals, decimals, {});

      if (values.hasOwnProperty(data)) {
        values[data]++;
      } else {
        values[data] = 1;
        keyArray.push(data);
      }
    }

    keyArray.sort(function(a, b) { return a - b; });

    var chart = "<table>";
    for (var i = 0; i < keyArray.length; i++) {
      var key = keyArray[i];
      chart += "<tr style='line-height:10px;'>";
      chart += "<td style='text-align: right; font-size:10px; padding-right:10px'>" + key + "</td>";
      for (var j = 0; j < values[key]; j++) {
        chart += "<td style='font-size:10px; background-color: #d22d72; border-bottom: 1px solid white; border-right: 1px solid white;'>&nbsp;&nbsp;&nbsp;&nbsp;</td>";
      }
      chart += "</tr>";
    }
    chart += "</table>";

    return chart;
  }


  return {
    initData: initData,
    updateDynamicFields: updateDynamicFields,
    updateBlankData: updateBlankData,
    updateFormulaData: updateFormulaData,
    testFormulas: testFormulas,
    generateRandomFromIntervals: generateRandomFromIntervals,
    showSample: showSample,
    redoSample: redoSample,
    closeSample: closeSample
  };

})();
