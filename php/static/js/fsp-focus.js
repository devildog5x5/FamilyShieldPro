(function () {
  if (document.querySelector("[autofocus]")) return;
  var path = location.pathname || "";
  if (path === "/admin" || path.indexOf("/admin/data") === 0) return;

  function visible(el) {
    return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
  }

  var skipType = {
    hidden: 1,
    submit: 1,
    button: 1,
    reset: 1,
    file: 1,
    checkbox: 1,
    radio: 1,
    image: 1,
  };

  var forms = document.querySelectorAll("form");
  for (var f = 0; f < forms.length; f++) {
    var form = forms[f];
    if (form.id === "fsp-chat-form" || form.closest(".fsp-chat")) continue;
    var els = form.querySelectorAll("input, textarea, select");
    var first = null;
    for (var i = 0; i < els.length; i++) {
      var el = els[i];
      if (skipType[el.type] || el.disabled || el.readOnly) continue;
      if (el.name === "_csrf") continue;
      if (!visible(el)) continue;
      if (!first) first = el;
      if (String(el.value || "").trim() === "") {
        el.focus();
        return;
      }
    }
    if (first) {
      first.focus();
      return;
    }
  }
})();
