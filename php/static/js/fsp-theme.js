(function () {
  try {
    localStorage.removeItem("fsp-theme");
  } catch (e) {}

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

  function current() {
    return document.documentElement.classList.contains("dark") ? "dark" : "light";
  }

  function save(mode) {
    var csrf = "";
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta) csrf = meta.getAttribute("content") || "";
    var body = "_csrf=" + encodeURIComponent(csrf) + "&theme=" + encodeURIComponent(mode);
    fetch("/theme", {
      method: "POST",
      headers: {
        "Content-Type": "application/x-www-form-urlencoded",
        "X-Requested-With": "fetch",
      },
      body: body,
      credentials: "same-origin",
    }).catch(function () {});
  }

  apply(current());

  document.addEventListener("DOMContentLoaded", function () {
    apply(current());
    var btn = document.getElementById("fsp-theme-toggle");
    if (!btn) return;
    btn.addEventListener("click", function () {
      var next = current() === "dark" ? "light" : "dark";
      apply(next);
      save(next);
    });
  });
})();
