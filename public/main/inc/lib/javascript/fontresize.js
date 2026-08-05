(function () {
  var storageKey = "chamiloFontResizeStep";
  var minStep = -2;
  var maxStep = 4;
  var stepPercent = 10;

  function getStep() {
    var storedStep = parseInt(window.localStorage.getItem(storageKey) || "0", 10);

    if (Number.isNaN(storedStep)) {
      return 0;
    }

    return clampStep(storedStep);
  }

  function setStep(step) {
    step = clampStep(step);

    if (0 === step) {
      document.documentElement.style.fontSize = "";
      window.localStorage.setItem(storageKey, "0");

      return;
    }

    document.documentElement.style.fontSize = 100 + step * stepPercent + "%";
    window.localStorage.setItem(storageKey, String(step));
  }

  function clampStep(step) {
    if (step < minStep) {
      return minStep;
    }

    if (step > maxStep) {
      return maxStep;
    }

    return step;
  }

  function handleResizeClick(event) {
    var decreaseButton = event.target.closest(".decrease_font");
    var resetButton = event.target.closest(".reset_font");
    var increaseButton = event.target.closest(".increase_font");

    if (!decreaseButton && !resetButton && !increaseButton) {
      return;
    }

    event.preventDefault();

    if (decreaseButton) {
      setStep(getStep() - 1);

      return;
    }

    if (increaseButton) {
      setStep(getStep() + 1);

      return;
    }

    setStep(0);
  }

  setStep(getStep());
  document.addEventListener("click", handleResizeClick);
})();
