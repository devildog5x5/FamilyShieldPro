(function () {
  var KEY = "fsp-theme";

  function stored() {
    try {
      return localStorage.getItem(KEY);
    } catch (e) {
      return null;
    }
  }

  function systemDark() {
    return window.matchMedia && window.matchMedia("(prefers-color-scheme: dark)").matches;
  }

  function resolve() {
    var saved = stored();
    if (saved === "dark" || saved === "light") return saved;
    return systemDark() ? "dark" : "light";
  }

  function apply(mode) {
    var dark = mode === "dark";
    document.documentElement.classList.toggle("dark", dark);
    document.documentElement.style.colorScheme = dark ? "dark" : "light";
    var btn = document.getElementById("fsp-theme-toggle");
    if (btn) {
      btn.setAttribute("aria-pressed", dark ? "true" : "false");
      btn.textContent = dark ? "Light" : "Dark";
      btn.title = dark ? "Switch to light appearance" : "Switch to dark appearance";
    }
  }

  apply(resolve());

  document.addEventListener("DOMContentLoaded", function () {
    apply(resolve());
    var btn = document.getElementById("fsp-theme-toggle");
    if (!btn) return;
    btn.addEventListener("click", function () {
      var next = document.documentElement.classList.contains("dark") ? "light" : "dark";
      try {
        localStorage.setItem(KEY, next);
      } catch (e) {}
      apply(next);
    });
  });
})();
