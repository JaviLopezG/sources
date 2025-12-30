<p align="center">  
<img src="logo.svg" alt="Smart Sources Logo" width="180" height="180">  
</p>

# **Smart Sources (Hybrid AI)**

**Smart Sources** is a WordPress plugin that automatically generates a "References" section at the end of your posts. It uses a **Hybrid Architecture**: local WordPress OEmbed for fast metadata (titles/domains) and a self-hosted AI middleware (Ollama \+ Gemma 3\) for deep context extraction.

## **✨ Features**

* **Context-Aware Citations**: Instead of generic descriptions, it reads your article's context and extracts specific quotes from the linked sources that support your writing.  
* **Privacy-First AI**: Designed to work with local LLMs (Gemma 3, Llama 3, etc.) via Ollama, kept private via Tailscale.  
* **Non-Destructive**: Generates a clean, static HTML block in the Gutenberg editor. You can edit the output manually before publishing.  
* **Clean UI**: Adds a minimalist, academic-style reference list that inherits your theme's fonts.

## **🏗 Architecture**

The system is composed of three parts to bypass browser CORS limitations and protect your AI endpoint:

graph LR  
    A\[User / WP Admin\] \--\>|1. Analysis Request| B(VPS Middleware)  
    A \--\>|2. Metadata Request| A(WP OEmbed)  
    B \--\>|3. Scrape Source| C\[External Website\]  
    B \--\>|4. AI Inference| D\[Local Ollama\]  
    D \-.-\>|Tailscale VPN| B

1. **WordPress Plugin (Frontend)**: Orchestrates the process in the Block Editor.  
2. **Node.js Middleware (Backend)**: Handles web scraping and communicates with the AI.  
3. **Ollama (AI Provider)**: Runs locally on your home server/machine, exposed to the VPS via Tailscale.

## **🚀 Installation**

### **1\. Backend (Middleware)**

This service runs on your VPS. It requires Docker and access to your Tailscale network.

1. Navigate to the back/ folder.  
2. Create a .env file:  
   \# Your local machine's Tailscale IP  
   OLLAMA\_URL=\[http://100.\](http://100.)x.y.z:11434/api/generate  
   MODEL\_NAME=gemma3  
   PORT=3000  
   CADDY\_HOST=sources.yups.me

3. Deploy with Docker Compose:  
   docker compose up \-d

### **2\. Frontend (WordPress Plugin)**

1. Zip the front/ folder or upload it to wp-content/plugins/smart-sources.  
2. Edit smart-sources.php to point to your middleware:  
   define('SMART\_SOURCES\_AI\_ENDPOINT', '\[https://sources.yups.me/api/analyze-context\](https://sources.yups.me/api/analyze-context)');

3. Activate the plugin in WordPress.

## **📖 Usage**

1. Open the **Gutenberg Editor** for any post.  
2. Look for the **"✨ Smart Sources AI"** panel in the right sidebar.  
3. Click **"🔍 Generate References"**.  
4. The plugin will:  
   * Scan your content for external links.  
   * Remove any previously generated reference block.  
   * Fetch metadata and AI context in parallel.  
   * Append a clean, formatted Reference section at the bottom of the editor.  
5. Save or Update your post.

## **🛠 Tech Stack**

* **Frontend**: PHP, JavaScript (Gutenberg Data API), CSS.  
* **Backend**: Node.js, Express, Cheerio (Scraping), Axios.  
* **AI**: Ollama running gemma3.  
* **Infra**: Docker, Caddy (Reverse Proxy), Tailscale (Networking).

## **License**

BSD 3
