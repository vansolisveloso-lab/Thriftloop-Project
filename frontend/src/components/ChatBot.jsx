import { useEffect, useRef, useState } from "react";

const GREETING =
  "Hi! I'm Loop, the ThriftLoop assistant.\n" +
  "Ask me about products, payment (GCash, card, COD), " +
  "shipping, or cancelling an order.";

function ChatBot() {
  const [open, setOpen] = useState(false);

  const [messages, setMessages] = useState([
    { role: "assistant", text: GREETING },
  ]);

  const [input, setInput] = useState("");

  const [loading, setLoading] = useState(false);

  const [error, setError] = useState("");

  const scrollRef = useRef(null);

  useEffect(() => {
    const box = scrollRef.current;

    if (box) {
      box.scrollTop = box.scrollHeight;
    }
  }, [messages, loading, open]);

  const send = async () => {
    const text = input.trim();

    if (!text || loading) {
      return;
    }

    setError("");
    setInput("");

    const next = [...messages, { role: "user", text }];

    setMessages(next);
    setLoading(true);

    try {
      const response = await fetch(
        "http://localhost/thriftloop/api/chat/bot.php",
        {
          method: "POST",
          headers: {
            "Content-Type": "application/json",
          },
          body: JSON.stringify({ messages: next }),
        }
      );

      const data = await response.json();

      if (!response.ok || !data.success) {
        throw new Error(
          data.message || "FAILED TO REACH THE ASSISTANT."
        );
      }

      setMessages([
        ...next,
        { role: "assistant", text: data.reply },
      ]);
    } catch (err) {
      console.error("CHATBOT ERROR:", err);
      setError(
        err.message || "FAILED TO REACH THE ASSISTANT."
      );
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <button
        type="button"
        className="chatbot-toggle"
        onClick={() => setOpen((wasOpen) => !wasOpen)}
      >
        {open ? "✕ CLOSE" : "ASK LOOP"}
      </button>

      {open && (
        <div className="chatbot-panel">
          <div className="chatbot-header">
            <p className="section-label">
              [ LOOP — SHOPPING ASSISTANT ]
            </p>
          </div>

          <div className="chatbot-messages" ref={scrollRef}>
            {messages.map((message, index) => (
              <div
                key={index}
                className={
                  "chatbot-msg " +
                  (message.role === "user"
                    ? "chatbot-msg-user"
                    : "chatbot-msg-bot")
                }
              >
                <p style={{ whiteSpace: "pre-line" }}>
                  {message.text}
                </p>
              </div>
            ))}

            {loading && (
              <div className="chatbot-msg chatbot-msg-bot">
                <p>THINKING...</p>
              </div>
            )}

            {error && (
              <p className="chatbot-error">{error}</p>
            )}
          </div>

          <div className="chatbot-input-row">
            <input
              type="text"
              value={input}
              onChange={(event) => setInput(event.target.value)}
              onKeyDown={(event) => {
                if (event.key === "Enter") {
                  send();
                }
              }}
              placeholder="ASK ANYTHING..."
              disabled={loading}
            />

            <button
              type="button"
              onClick={send}
              disabled={loading || input.trim() === ""}
            >
              {loading ? "..." : "SEND"}
            </button>
          </div>
        </div>
      )}
    </>
  );
}

export default ChatBot;
