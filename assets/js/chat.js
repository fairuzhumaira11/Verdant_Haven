// Keep the selected conversation up to date.
(() => {
  const chat = document.getElementById("liveChat");
  if (!chat || !Number(chat.dataset.contact)) return;
  const box = document.getElementById("chatMessages");
  const form = chat.querySelector("form");
  const status = document.getElementById("chatStatus");
  const known = new Set(
    [...box.querySelectorAll("[data-message-id]")].map((el) =>
      Number(el.dataset.messageId),
    ),
  );
  let after = Number(box.dataset.after) || 0;
  let polling = false;
  let sendError = "";
  function show(message) {
    const id = Number(message.id);
    if (known.has(id)) return;
    known.add(id);
    const atBottom = box.scrollHeight - box.scrollTop - box.clientHeight < 40;
    const bubble = document.createElement("div");
    bubble.dataset.messageId = id;
    bubble.className =
      "message" +
      (Number(message.sender_id) === Number(chat.dataset.user) ? " mine" : "");
    bubble.textContent = message.body;
    const time = document.createElement("time");
    time.textContent = message.created_at;
    bubble.appendChild(time);
    const next = [...box.children].find(
      (el) => Number(el.dataset.messageId) > id,
    );
    box.insertBefore(bubble, next || null);
    if (box.children.length > 300) box.firstElementChild.remove();
    if (atBottom || Number(message.sender_id) === Number(chat.dataset.user)) {
      box.scrollTop = box.scrollHeight;
    }
  }
  async function request(action, options = {}) {
    const response = await fetch(chat.dataset.api + "?action=" + action, {
      credentials: "same-origin",
      ...options,
    });
    let data;
    try {
      data = await response.json();
    } catch (error) {
      throw new Error("Chat is unavailable. Refresh the page and try again.");
    }
    if (!response.ok) throw new Error(data.error || "Could not load chat.");
    return data;
  }
  async function poll() {
    if (polling || document.hidden) return;
    polling = true;
    try {
      const data = await request(
        "messages&contactId=" + chat.dataset.contact + "&after=" + after,
      );
      for (const message of data.messages) {
        show(message);
        after = Math.max(after, Number(message.id));
      }
      if (!sendError) status.textContent = "";
    } catch (error) {
      if (!sendError) status.textContent = error.message;
    } finally {
      polling = false;
    }
  }
  form.addEventListener("submit", async (event) => {
    event.preventDefault();
    const button = form.querySelector("button");
    const input = form.elements.message;
    if (button.disabled) return;
    const sentMessage = input.value;
    sendError = "";
    status.textContent = "";
    button.disabled = true;
    try {
      const data = await request("message_send", {
        method: "POST",
        body: new FormData(form),
        headers: { "X-CSRF-Token": chat.dataset.csrf },
      });
      show(data.message);
      if (input.value === sentMessage) input.value = "";
      // Read incoming messages before moving the polling cursor.
      await poll();
    } catch (error) {
      sendError = error.message;
      status.textContent = sendError;
    } finally {
      button.disabled = false;
      input.focus();
    }
  });
  box.scrollTop = box.scrollHeight;
  poll();
  setInterval(poll, 4000);
})();
