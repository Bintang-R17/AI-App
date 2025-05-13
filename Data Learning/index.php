<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chatbot AI</title>
  <style>
  body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f4f8;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  h1 {
    color: #00796b;
    margin-top: 30px;
    font-weight: bold;
  }

  .chat-container {
    width: 90%;
    max-width: 600px;
    background: #ffffff;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
    margin: 20px 0;
    display: flex;
    flex-direction: column;
    gap: 10px;
  }

  .message {
    padding: 12px 18px;
    border-radius: 15px;
    max-width: 80%;
    white-space: pre-wrap;
    font-size: 16px;
  }

  .user {
    align-self: flex-end;
    background-color: #c8e6c9;
    color: #1b5e20;
  }

  .bot {
    align-self: flex-start;
    background-color: #b2dfdb;
    color: #004d40;
  }

  .input-group {
    width: 90%;
    max-width: 600px;
    display: flex;
    margin-bottom: 30px;
  }

  input[type="text"] {
    flex-grow: 1;
    padding: 12px;
    border: 1px solid #b0bec5;
    border-radius: 10px;
    margin-right: 10px;
    background-color: #ffffff;
    font-size: 16px;
  }

  button {
    padding: 12px 22px;
    background-color: #00796b;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
    transition: background-color 0.3s;
  }

  button:hover {
    background-color: #004d40;
  }

  .reset-button {
    background-color: #546e7a;
    margin-top: 10px;
  }

  .reset-button:hover {
    background-color: #37474f;
  }
  </style>
</head>
<body>
  <h1>Customer Service</h1>
  <button class="reset-button" onclick="resetChat()">Hapus Riwayat</button>
  <div class="chat-container" id="chat"></div>

  <div class="input-group">
    <input type="text" id="input" placeholder="Tulis pertanyaan..." />
    <button onclick="sendMessage()">Kirim</button>
  </div>

  <script>
    const chatContainer = document.getElementById('chat');
    const input = document.getElementById('input');

    function appendMessage(text, sender) {
      const msg = document.createElement('div');
      msg.className = 'message ' + sender;
      msg.textContent = text;
      chatContainer.appendChild(msg);
      chatContainer.scrollTop = chatContainer.scrollHeight;

      const chatHistory = JSON.parse(localStorage.getItem('chatHistory')) || [];
      chatHistory.push({ text, sender });
      localStorage.setItem('chatHistory', JSON.stringify(chatHistory));
    }

    async function sendMessage() {
      const msg = input.value.trim();
      if (!msg) return;

      appendMessage(msg, 'user');
      sound.play();
      input.value = '';

      try {
        const res = await fetch('chat.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ message: msg })
        });

        const data = await res.json();
        const botReply = data.choices?.[0]?.message?.content || "Tidak ada jawaban.";
        appendMessage(botReply, 'bot');
      } catch (err) {
        appendMessage("❌ Terjadi kesalahan saat menghubungi server.", 'bot');
        console.error(err);
      }
    }

    input.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        sendMessage();
      }
    });

    window.addEventListener('load', () => {
      const savedChat = JSON.parse(localStorage.getItem('chatHistory')) || [];
      savedChat.forEach(({ text, sender }) => {
        appendMessage(text, sender);
      });
    });

    function resetChat() {
      localStorage.removeItem('chatHistory');
      chatContainer.innerHTML = '';
    }
  </script>
</body>
</html>
