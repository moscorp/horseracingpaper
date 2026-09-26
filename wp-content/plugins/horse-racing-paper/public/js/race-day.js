(() => {
  const root = document.querySelector("[data-hrp-race-day]");
  if (!root) return;

  const tabs = root.querySelectorAll(".hrp-race-tabs button");
  const panels = root.querySelectorAll("[data-race-panel]");
  if (!tabs.length || !panels.length) return;

  const show = (raceNo) => {
    tabs.forEach((btn) => {
      btn.setAttribute(
        "aria-selected",
        String(btn.dataset.race === String(raceNo))
      );
    });
    panels.forEach((panel) => {
      const match = panel.getAttribute("data-race-panel") === String(raceNo);
      if (match) {
        panel.removeAttribute("hidden");
      } else {
        panel.setAttribute("hidden", "");
      }
    });
  };

  tabs.forEach((btn) => {
    btn.addEventListener("click", () => show(btn.dataset.race));
  });

  // Keyboard: left/right between tabs
  root.querySelector(".hrp-race-tabs")?.addEventListener("keydown", (e) => {
    if (e.key !== "ArrowRight" && e.key !== "ArrowLeft") return;
    const list = Array.from(tabs);
    const current = list.findIndex(
      (b) => b.getAttribute("aria-selected") === "true"
    );
    if (current < 0) return;
    e.preventDefault();
    const next =
      e.key === "ArrowRight"
        ? (current + 1) % list.length
        : (current - 1 + list.length) % list.length;
    list[next].focus();
    show(list[next].dataset.race);
  });
})();
