const express = require('express');
const app = express();
const ChatBot = require('../chatbot/ChatBot');

const chatbot = new ChatBot();

app.post('/api/chat', async (req, res) => {
    const { message } = req.body;
    const response = await chatbot.processMessage(message);
    res.json({ response });
});
