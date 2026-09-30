(() => {
  const badges = document.querySelectorAll("[data-unread-total]");
  if (!badges.length) return;
  const contacts = document.querySelectorAll("[data-unread-contact]");

  async function refresh() {
    if (document.hidden) return;
    try {
      const response = await fetch(badges[0].dataset.api + "?action=unread", {
        credentials: "same-origin",
      });
      if (!response.ok) return;
      const data = await response.json();
      const total = Number(data.total) || 0;
      for (const badge of badges) {
        badge.textContent = total;
        badge.hidden = total === 0;
      }
      const unread = new Map(
        data.contacts.map((contact) => [
          Number(contact.sender_id),
          Number(contact.total),
        ]),
      );
      for (const badge of contacts) {
        const count = unread.get(Number(badge.dataset.unreadContact)) || 0;
        badge.textContent = count;
        badge.hidden = count === 0;
      }
    } catch (error) {
      return;
    }
  }

  refresh();
  setInterval(refresh, 15000);
  document.addEventListener("visibilitychange", refresh);
})();