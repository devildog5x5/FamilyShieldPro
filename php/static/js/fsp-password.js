(function () {
  var eyeOn =
    '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M12 5c5.2 0 9.3 3.3 10.7 7-1.4 3.7-5.5 7-10.7 7S2.7 15.7 1.3 12C2.7 8.3 6.8 5 12 5zm0 2C8 7 4.8 9.3 3.5 12 4.8 14.7 8 17 12 17s7.2-2.3 8.5-5C19.2 9.3 16 7 12 7zm0 2.2A2.8 2.8 0 1 1 12 14.8 2.8 2.8 0 0 1 12 9.2z"/></svg>';
  var eyeOff =
    '<svg viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M3.3 2.5 2 3.8l3.1 3.1C3.4 8.3 2.2 10 1.3 12c1.4 3.7 5.5 7 10.7 7 1.7 0 3.3-.3 4.7-1l3.3 3.3 1.3-1.3L3.3 2.5zM12 17c-4 0-7.2-2.3-8.5-5 .7-1.4 1.9-2.7 3.4-3.6l2 2A3.8 3.8 0 0 0 12 15.8c.4 0 .8 0 1.1-.1l1.6 1.6c-.8.4-1.7.7-2.7.7zm8.5-5c-.4.9-1 1.7-1.7 2.5l-1.5-1.5c.4-.6.6-1.3.6-2 0-2.1-1.7-3.8-3.8-3.8-.7 0-1.4.2-2 .6L10.6 6.3C11 6.1 11.5 6 12 6c5.2 0 9.3 3.3 10.7 7-.4 1.1-1.1 2.1-2 3l-1.4-1.4c.5-.5.9-1 .2-1.6z"/></svg>';

  function decorate(input) {
    if (input.closest(".pw-wrap")) return;
    var wrap = document.createElement("div");
    wrap.className = "pw-wrap";
    input.parentNode.insertBefore(wrap, input);
    wrap.appendChild(input);
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "pw-toggle";
    btn.setAttribute("aria-label", "Show password");
    btn.setAttribute("aria-pressed", "false");
    btn.innerHTML = eyeOn;
    btn.addEventListener("click", function () {
      var show = input.type === "password";
      input.type = show ? "text" : "password";
      btn.setAttribute("aria-pressed", show ? "true" : "false");
      btn.setAttribute("aria-label", show ? "Hide password" : "Show password");
      btn.innerHTML = show ? eyeOff : eyeOn;
      input.focus();
    });
    wrap.appendChild(btn);
  }

  var list = document.querySelectorAll('input[type="password"]');
  for (var i = 0; i < list.length; i++) {
    decorate(list[i]);
  }
})();
