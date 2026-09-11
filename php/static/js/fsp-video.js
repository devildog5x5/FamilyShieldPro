(function () {
  var wrap = document.querySelector(".hero-video");
  if (!wrap) return;
  var video = wrap.querySelector("video");
  var start = wrap.querySelector(".hero-video-start");
  if (!video || !start) return;

  function playStory() {
    wrap.classList.add("is-playing");
    video.muted = false;
    video.defaultMuted = false;
    video.volume = 0.5;
    video.setAttribute("controls", "controls");
    var p = video.play();
    if (p && typeof p.catch === "function") {
      p.catch(function () {
        wrap.classList.remove("is-playing");
        video.removeAttribute("controls");
      });
    }
  }

  start.addEventListener("click", playStory);
  video.addEventListener("ended", function () {
    wrap.classList.remove("is-playing");
    video.removeAttribute("controls");
    video.pause();
    video.currentTime = 0;
  });
})();
