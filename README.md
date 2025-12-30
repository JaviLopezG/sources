# **Smart Sources with Ollama (Gemma 3\)**

A hybrid WordPress plugin and Node.js middleware that generates "Smart Cards" for external links in articles. It uses a local WordPress REST endpoint for fast metadata fetching (OpenGraph) and a private Ollama instance (via Tailscale) for AI-powered context extraction.

## **Project Structure**

* **back/**: Node.js Middleware (Express) running on a VPS via Docker. Handles scraping and AI inference.  
* **front/**: WordPress Plugin. Handles UI injection and orchestration.

## **1\. Backend Setup (VPS)**

### **Prerequisites**

* Docker & Docker Compose  
* Caddy (running caddy-docker-proxy on caddy\_net network)  
* Tailscale (connecting VPS to your local machine running Ollama)

### **Deployment**

1. Navigate to back/:  
   cd back

2. Create a .env file based on the example:  
   cp .env.example .env

3. Edit .env with your specific configuration:  
   * OLLAMA\_URL: Your local machine's Tailscale IP (e.g., http://100.x.y.z:11434/api/generate).  
   * CADDY\_HOST: The domain for the middleware (e.g., sources.yups.me).  
4. Deploy with Docker Compose:  
   docker compose up \-d

## **2\. Frontend Setup (WordPress)**

1. Zip the front/ folder or upload the smart-sources.php file directly to your WordPress plugins directory: wp-content/plugins/smart-sources/.  
2. Open smart-sources.php and update the SMART\_SOURCES\_AI\_ENDPOINT constant to match your backend URL (e.g., https://sources.yups.me/api/analyze-context).  
3. Activate the plugin in WordPress Admin.

### **Usage**

* Go to any single post.  
* Scroll to the bottom.  
* Click **"🔍 Generate Smart Sources"**.
