<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Chatbot AI</title>
  <style>
  body {
    font-family: 'Comic Sans MS', cursive, sans-serif;
    background: #ffe6f0;
    margin: 0;
    padding: 0;
    display: flex;
    flex-direction: column;
    align-items: center;
  }

  h1 {
    color: #d81b60;
    margin-top: 30px;
    font-weight: bold;
  }

  .chat-container {
    width: 90%;
    max-width: 600px;
    background: #fff0f5;
    padding: 20px;
    border-radius: 15px;
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
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
    background-color: #ffc1e3;
    color: #880e4f;
  }

  .bot {
    align-self: flex-start;
    background-color: #e1bee7;
    color: #4a148c;
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
    border: 1px solid #f8bbd0;
    border-radius: 10px;
    margin-right: 10px;
    background-color: #fff0f5;
    font-size: 16px;
  }

  button {
    padding: 12px 22px;
    background-color: #f06292;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: bold;
    font-size: 16px;
  }

  button:hover {
    background-color: #ec407a;
  }

  </style>
</head>
<body>
  <h1>Chatbot AI</h1>
  <button class="reset-button" onclick="resetChat()"> Hapus Riwayat</button>
  <div class="chat-container" id="chat"></div>

  <div class="input-group">
    <input type="text" id="input" placeholder="Tulis pertanyaan..." />
    <button onclick="sendMessage()">Kirim</button>
    <audio id="sendSound" src="send.mp3" preload="auto"></audio>
  </div>

  <script>
    const chatContainer = document.getElementById('chat');
    const input = document.getElementById('input');
    const sound = document.getElementById('sendSound');

    function appendMessage(text, sender) {
      const msg = document.createElement('div');
      msg.className = 'message ' + sender;
      msg.textContent = text;
      chatContainer.appendChild(msg);
      chatContainer.scrollTop = chatContainer.scrollHeight;

      // Simpan ke localStorage
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
        appendMessage(" Terjadi kesalahan saat menghubungi server.", 'bot');
        console.error(err);
      }
    }

    // Enter key support
    input.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        sendMessage();
      }
    });

    // Load chat dari localStorage saat halaman dibuka
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
