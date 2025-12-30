<?php
/**
 * Plugin Name: Smart Sources (Admin Editor)
 * Description: Adds a metabox to generate AI-powered smart citations. V3.3: CSS Enqueue.
 * Version: 3.3
 */

if (!defined('ABSPATH')) exit;

// CONFIGURATION: Middleware URL
define('SMART_SOURCES_AI_ENDPOINT', 'https://sources.yups.me/api/analyze-context');

// 0. Enqueue CSS Styles (Front & Back)
add_action('init', function() {
    // Registramos el estilo
    wp_register_style(
        'smart-sources-css',
        plugins_url('smart-sources.css', __FILE__),
        [],
        '1.0.0'
    );
});

// Cargar en el Frontend (Web pública)
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('smart-sources-css');
});

// Cargar en el Admin (Para que se vea bien en el editor)
add_action('admin_enqueue_scripts', function() {
    wp_enqueue_style('smart-sources-css');
});


// 1. Create Meta Box
add_action('add_meta_boxes', function() {
    add_meta_box(
        'smart_sources_box',
        '✨ Smart Sources AI',
        'ss_render_meta_box',
        'post',
        'side',
        'high'
    );
});

// 2. Render Meta Box Content
function ss_render_meta_box($post) {
    ?>
    <div id="ss-admin-wrapper" style="padding: 10px 0;">
        <p style="margin-bottom:15px; color:#666; font-size:13px;">
            Generates a clean "References" list at the end of the post.
        </p>
        
        <button type="button" id="ss-generate-btn" class="button button-primary button-large" style="width:100%; justify-content:center; display:flex; align-items:center; gap:5px;">
            <span>🔍 Generate References</span>
        </button>
        
        <div id="ss-status" style="margin-top: 10px; font-size: 12px; color: #444;"></div>
    </div>

    <!-- JS LOGIC -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const btn = document.getElementById('ss-generate-btn');
        const status = document.getElementById('ss-status');
        const aiEndpoint = '<?php echo SMART_SOURCES_AI_ENDPOINT; ?>';
        
        // Block ID identifier to find/replace existing blocks
        const SOURCES_BLOCK_ID = 'ss-generated-block-v3';

        // 1. Helper: Get fast metadata from WP OEmbed (Title only)
        const getMeta = async (url) => {
            try {
                const res = await fetch('/wp-json/oembed/1.0/proxy?url=' + encodeURIComponent(url));
                if (!res.ok) throw new Error('No oembed');
                const data = await res.json();
                return { title: data.title, domain: new URL(url).hostname.replace('www.','') };
            } catch (e) {
                return { title: url, domain: new URL(url).hostname.replace('www.','') };
            }
        };

        if (!btn) return;

        btn.addEventListener('click', async () => {
            if (typeof wp === 'undefined' || !wp.data) {
                status.innerHTML = '⚠️ Error: Gutenberg editor required.';
                return;
            }

            // --- A. PREPARATION ---
            btn.disabled = true;
            btn.innerText = '⏳ Scanning...';
            status.innerHTML = 'Analyzing blocks...';

            const { select, dispatch } = wp.data;
            const { createBlock } = wp.blocks;
            const { getBlocks } = select('core/block-editor');
            const { removeBlock, insertBlocks } = dispatch('core/block-editor');

            // Get all current blocks
            const allBlocks = getBlocks();
            let previousBlockId = null;

            // 1. Filter out the existing Smart Sources block
            const contentBlocks = allBlocks.filter(block => {
                if (block.name === 'core/html' && block.attributes.content && block.attributes.content.includes(SOURCES_BLOCK_ID)) {
                    previousBlockId = block.clientId; 
                    return false; 
                }
                return true;
            });

            // 2. Serialize ONLY the content blocks to find links
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = wp.blocks.serialize(contentBlocks);

            // 3. Extract Links
            const links = Array.from(tempDiv.querySelectorAll('a'))
                .filter(a => a.href.startsWith('http') && !a.href.includes(window.location.hostname));

            // Deduplicate
            const uniqueLinks = [...new Set(links.map(a => ({ 
                url: a.href, 
                context: a.closest('p')?.innerText || 'Article context'
            })))];

            if (uniqueLinks.length === 0) {
                status.innerHTML = '❌ No external links found.';
                if (previousBlockId) removeBlock(previousBlockId);
                btn.disabled = false;
                btn.innerText = '🔍 Generate References';
                return;
            }

            status.innerHTML = `Found ${uniqueLinks.length} links. Querying AI...`;

            // --- B. GENERATION ---
            let listHTML = '';

            for (const item of uniqueLinks) {
                try {
                    // Parallel fetch: Meta + AI
                    const [meta, aiRes] = await Promise.all([
                        getMeta(item.url),
                        fetch(aiEndpoint, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json' },
                            body: JSON.stringify({ url: item.url, context: item.context })
                        }).then(r => r.json()).catch(() => ({ success: false }))
                    ]);

                    const aiText = (aiRes.success && aiRes.data) ? aiRes.data.quote : "Source referenced in article.";
                    
                    // Generate Clean List Item
                    listHTML += `
                    <li class="ss-clean-item">
                        <span class="ss-clean-meta">${meta.domain}</span>
                        <a href="${item.url}" target="_blank" class="ss-clean-title">${meta.title}</a>
                        <p class="ss-clean-text">${aiText}</p>
                    </li>`;

                } catch (err) {
                    console.error(err);
                }
            }

            // Wrapper HTML
            const finalHTML = `
            <div id="${SOURCES_BLOCK_ID}" class="ss-clean-container">
                <div class="ss-clean-header">Sources & References</div>
                <ul class="ss-clean-list">
                    ${listHTML}
                </ul>
            </div>`;

            // --- C. UPDATE EDITOR ---
            
            // 1. Remove old block
            if (previousBlockId) {
                removeBlock(previousBlockId);
            }

            // 2. Insert new block at the end
            const newBlock = createBlock('core/html', { content: finalHTML });
            const currentBlockCount = select('core/block-editor').getBlockCount();
            insertBlocks(newBlock, currentBlockCount);

            // Reset UI
            btn.disabled = false;
            btn.innerText = '✅ Done!';
            status.innerHTML = 'Sources updated successfully.';
            
            setTimeout(() => {
                btn.innerText = '🔍 Generate References';
                status.innerHTML = '';
            }, 3000);
        });
    });
    </script>
    <?php
}
