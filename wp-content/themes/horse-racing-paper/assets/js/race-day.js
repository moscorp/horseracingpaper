(() => {
  const tabs = document.querySelectorAll(".hrp-race-tabs button");
  if (!tabs.length) return;

  tabs.forEach((btn) => {
    btn.addEventListener("click", () => {
      tabs.forEach((b) => b.setAttribute("aria-selected", "false"));
      btn.setAttribute("aria-selected", "true");
      const race = btn.dataset.race || btn.textContent.trim();
      document.dispatchEvent(new CustomEvent("hrp:race-change", { detail: { race } }));
    });
  });
})();
