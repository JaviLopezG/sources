const express = require('express');
const cors = require('cors');
const axios = require('axios');
const cheerio = require('cheerio');

const app = express();
app.use(cors());
app.use(express.json());

// Configuration via Environment Variables
const OLLAMA_URL = process.env.OLLAMA_URL || 'http://localhost:11434/api/generate';
const MODEL_NAME = process.env.MODEL_NAME || 'gemma3';
const PORT = process.env.PORT || 3000;

/**
 * Endpoint: /api/analyze-context
 * Receives: { url, context }
 * Returns: { success: boolean, data: { quote, type } }
 */
app.post('/api/analyze-context', async (req, res) => {
  const { url, context } = req.body;
  console.log(`[INFO] Analyzing: ${url}`);

  try {
    // 1. Scraping the target website content
    const pageRes = await axios.get(url, {
      timeout: 8000,
      headers: { 'User-Agent': 'SmartSourcesBot/1.0' }
    });
    
    const $ = cheerio.load(pageRes.data);
    
    // Clean up unnecessary elements to save tokens and reduce noise
    $('script, style, nav, footer, header, aside, iframe').remove();
    
    // normalize whitespace and limit length
    const cleanText = $('body').text().replace(/\s+/g, ' ').substring(0, 6000);

    // 2. Query Ollama (Gemma 3)
    const prompt = `
    <website>${cleanText}</website>.
    
    <context>${context}</context>.
    
    TASK: 
    1. Analyze <WEBSITE> and understand its subjects. For instance, website talks about A, B and C.
    2. Analyze <CONTEXT> and understand references to <WEBSITE>. For instance, context talks about X, B', Y, C' and Z, so the relevant references are B and C.
    3. Write a concise statement based exclusively on <WEBSITE> that addresses the intersecting topics (B and C). Constraint: Do not explicitly mention the <CONTEXT>, "the article", or "the user's text". Do not explain why it is relevant. Simply present the facts, data, or arguments found in the <WEBSITE> that relates to the <CONTEXT>.
    4. Don't be verbose. Keep your explanation or quote short (ideally less than 25 words). If you include a quote, you can use '[...]' to replace irrelevant parts.
    5. Always use the original language of the <WEBSITE>.

    Respond ONLY in JSON format:
    {
      "quote": "extracted text or summary",
      "type": "quote" or "summary"
    }

    Examples:
    {
      "quote": "The reference defines B and show its usages.",
      "type": "summary"
    }
    {
      "quote": "The website explains the creation of A and its importance to B",
      "type": "summary"
    }
    {
      "quote": "B and C",
      "type": "quote"
    }
    {
      "quote": "B [...] C",
      "type": "quote"
    }
    `;

    const response = await axios.post(OLLAMA_URL, {
      model: MODEL_NAME,
      prompt: prompt,
      format: "json", // Enforce JSON mode
      stream: false
    });

    let aiData;
    try {
        aiData = JSON.parse(response.data.response);
    } catch (e) {
        // Fallback if model returns malformed JSON
        aiData = { quote: "Content analyzed", type: "Info" };
    }

    res.json({ success: true, data: aiData });

  } catch (error) {
    console.error(`[ERROR] ${url}: ${error.message}`);
    res.json({ success: false, error: "Could not analyze content." });
  }
});

app.listen(PORT, () => console.log(`Middleware running on port ${PORT}`));
