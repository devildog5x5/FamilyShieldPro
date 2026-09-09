(function () {
  var btn = document.getElementById("trial-wait");
  var form = document.getElementById("trial-continue-form");
  if (!btn || !form) {
    return;
  }
  var n = 10;
  var t = setInterval(function () {
    n -= 1;
    if (n <= 0) {
      clearInterval(t);
      btn.disabled = false;
      btn.textContent = "Continue with trusted list and past checks";
      return;
    }
    btn.textContent = "Continue in " + n + " seconds";
  }, 1000);
  form.addEventListener("submit", function (e) {
    if (btn.disabled) {
      e.preventDefault();
    }
  });
})();
