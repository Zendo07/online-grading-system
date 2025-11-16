<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Layout Debug Tool</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #f5f5f5;
            padding: 20px;
        }

        .debug-container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .debug-header {
            background: linear-gradient(135deg, #7b2d26, #5a1f1a);
            color: white;
            padding: 24px;
            text-align: center;
        }

        .debug-header h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }

        .debug-header p {
            opacity: 0.9;
            font-size: 14px;
        }

        .debug-content {
            padding: 32px;
        }

        .section {
            margin-bottom: 32px;
            padding: 20px;
            background: #fafafa;
            border-radius: 8px;
            border-left: 4px solid #7b2d26;
        }

        .section h2 {
            color: #7b2d26;
            font-size: 20px;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .status-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-icon.good {
            background: #22c55e;
        }

        .status-icon.warning {
            background: #f59e0b;
        }

        .status-icon.error {
            background: #ef4444;
        }

        .check-item {
            padding: 12px;
            margin: 8px 0;
            background: white;
            border-radius: 6px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: 1px solid #e5e5e5;
        }

        .check-label {
            font-weight: 500;
            color: #374151;
        }

        .check-value {
            font-family: 'Courier New', monospace;
            background: #f3f4f6;
            padding: 4px 12px;
            border-radius: 4px;
            font-size: 13px;
            color: #7b2d26;
        }

        .code-block {
            background: #1f2937;
            color: #e5e7eb;
            padding: 16px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 12px;
        }

        .code-block .comment {
            color: #9ca3af;
        }

        .code-block .property {
            color: #60a5fa;
        }

        .code-block .value {
            color: #34d399;
        }

        .solution-box {
            background: #ecfdf5;
            border: 2px solid #10b981;
            border-radius: 8px;
            padding: 16px;
            margin-top: 12px;
        }

        .solution-box h3 {
            color: #047857;
            font-size: 16px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .solution-box ul {
            margin-left: 20px;
            color: #065f46;
        }

        .solution-box li {
            margin: 6px 0;
        }

        .visual-demo {
            margin-top: 20px;
            border: 2px dashed #d4d4d4;
            border-radius: 8px;
            overflow: hidden;
        }

        .demo-layout {
            display: flex;
            min-height: 300px;
            background: white;
        }

        .demo-sidebar {
            width: 280px;
            background: #374151;
            color: white;
            padding: 20px;
            font-size: 12px;
        }

        .demo-main {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .demo-nav {
            background: #7b2d26;
            color: white;
            padding: 16px 20px;
            font-size: 12px;
        }

        .demo-content {
            flex: 1;
            padding: 20px;
            background: #fafafa;
            font-size: 12px;
        }

        .demo-footer {
            background: #7b2d26;
            color: white;
            padding: 16px 20px;
            font-size: 11px;
        }

        .issue-badge {
            background: #fee2e2;
            color: #991b1b;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            margin-left: 8px;
        }

        .btn-copy {
            background: #7b2d26;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            margin-top: 8px;
            transition: all 0.2s;
        }

        .btn-copy:hover {
            background: #5a1f1a;
            transform: translateY(-1px);
        }

        .toggle-section {
            cursor: pointer;
            user-select: none;
        }

        .toggle-section:hover {
            opacity: 0.8;
        }

        .collapsible-content {
            margin-top: 12px;
        }

        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .metric-card {
            background: white;
            padding: 16px;
            border-radius: 6px;
            border: 1px solid #e5e5e5;
            text-align: center;
        }

        .metric-value {
            font-size: 24px;
            font-weight: 700;
            color: #7b2d26;
            margin-bottom: 4px;
        }

        .metric-label {
            font-size: 12px;
            color: #6b7280;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .demo-sidebar {
                width: 0;
                padding: 0;
                overflow: hidden;
            }
        }
    </style>
</head>
<body>
    <div class="debug-container">
        <div class="debug-header">
            <h1>🔧 Footer Layout Debugger</h1>
            <p>Diagnose and fix footer alignment issues in your My Professors page</p>
        </div>

        <div class="debug-content">
            <!-- Common Issues Section -->
            <div class="section">
                <h2 class="toggle-section" onclick="toggleSection('issues')">
                    <span class="status-icon error"></span>
                    Common Footer Issues
                    <span class="issue-badge">3 ISSUES FOUND</span>
                </h2>
                <div id="issues" class="collapsible-content">
                    <div class="check-item">
                        <span class="check-label">❌ Footer width not calculated correctly</span>
                        <span class="check-value">Missing calc(100% - 280px)</span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">❌ Footer margin-left not matching sidebar</span>
                        <span class="check-value">Should be 280px</span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">❌ Horizontal overflow detected</span>
                        <span class="check-value">overflow-x not hidden</span>
                    </div>
                </div>
            </div>

            <!-- Current Layout Analysis -->
            <div class="section">
                <h2 class="toggle-section" onclick="toggleSection('analysis')">
                    <span class="status-icon warning"></span>
                    Current Layout Analysis
                </h2>
                <div id="analysis" class="collapsible-content">
                    <div class="metrics-grid">
                        <div class="metric-card">
                            <div class="metric-value">280px</div>
                            <div class="metric-label">Sidebar Width</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value">70px</div>
                            <div class="metric-label">Header Height</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value">~60px</div>
                            <div class="metric-label">Footer Height</div>
                        </div>
                        <div class="metric-card">
                            <div class="metric-value">calc()</div>
                            <div class="metric-label">Width Formula</div>
                        </div>
                    </div>

                    <div class="code-block">
<span class="comment">/* Current Footer CSS (PROBLEMATIC) */</span>
<span class="property">.footer</span> {
    <span class="property">display</span>: <span class="value">flex</span>;
    <span class="property">padding</span>: <span class="value">20px 24px</span>;
    <span class="property">background-color</span>: <span class="value">#7b2d26</span>;
    <span class="comment">/* ❌ Missing width calculation */</span>
    <span class="comment">/* ❌ Missing margin-left alignment */</span>
    <span class="comment">/* ❌ Causes misalignment with content */</span>
}
                    </div>
                </div>
            </div>

            <!-- Solution Section -->
            <div class="section">
                <h2 class="toggle-section" onclick="toggleSection('solution')">
                    <span class="status-icon good"></span>
                    ✅ Complete Solution
                </h2>
                <div id="solution" class="collapsible-content">
                    <div class="solution-box">
                        <h3>
                            💡 Fix Steps
                        </h3>
                        <ul>
                            <li>Add width calculation to match main content area</li>
                            <li>Set margin-left to align with sidebar (280px)</li>
                            <li>Add overflow-x: hidden to prevent horizontal scroll</li>
                            <li>Make it responsive for mobile devices</li>
                        </ul>
                    </div>

                    <div class="code-block">
<span class="comment">/* Add this to your my-professors.css file */</span>

<span class="comment">/* 1. Prevent horizontal scrolling */</span>
<span class="property">html</span>, <span class="property">body</span> {
    <span class="property">overflow-x</span>: <span class="value">hidden</span>;
    <span class="property">width</span>: <span class="value">100%</span>;
    <span class="property">max-width</span>: <span class="value">100vw</span>;
}

<span class="comment">/* 2. Update main content */</span>
<span class="property">.main-content</span> {
    <span class="property">width</span>: <span class="value">calc(100% - 280px)</span>;
    <span class="property">margin-left</span>: <span class="value">280px</span>;
    <span class="property">overflow-x</span>: <span class="value">hidden</span>;
}

<span class="comment">/* 3. Fix footer alignment - ADD THIS TO footer.php styles */</span>
<span class="property">.footer</span> {
    <span class="property">display</span>: <span class="value">flex</span>;
    <span class="property">justify-content</span>: <span class="value">space-between</span>;
    <span class="property">align-items</span>: <span class="value">center</span>;
    <span class="property">padding</span>: <span class="value">20px 24px</span>;
    <span class="property">background-color</span>: <span class="value">#7b2d26</span>;
    <span class="property">color</span>: <span class="value">#ffffff</span>;
    <span class="property">font-size</span>: <span class="value">14px</span>;
    <span class="property">margin-top</span>: <span class="value">auto</span>;
    <span class="property">width</span>: <span class="value">calc(100% - 280px)</span>; <span class="comment">/* ✅ CRITICAL FIX */</span>
    <span class="property">margin-left</span>: <span class="value">280px</span>; <span class="comment">/* ✅ CRITICAL FIX */</span>
    <span class="property">box-sizing</span>: <span class="value">border-box</span>;
}

<span class="comment">/* 4. Mobile responsive */</span>
<span class="property">@media (max-width: 1024px)</span> {
    <span class="property">.main-content</span> {
        <span class="property">width</span>: <span class="value">100%</span>;
        <span class="property">margin-left</span>: <span class="value">0</span>;
    }
    
    <span class="property">.footer</span> {
        <span class="property">width</span>: <span class="value">100%</span>;
        <span class="property">margin-left</span>: <span class="value">0</span>;
    }
}
                    </div>
                    <button class="btn-copy" onclick="copyCode()">📋 Copy Fixed CSS</button>
                </div>
            </div>

            <!-- Visual Demo -->
            <div class="section">
                <h2 class="toggle-section" onclick="toggleSection('demo')">
                    <span class="status-icon good"></span>
                    Visual Layout Demo
                </h2>
                <div id="demo" class="collapsible-content">
                    <p style="margin-bottom: 12px; color: #6b7280;">This shows how your layout should look with the fix applied:</p>
                    <div class="visual-demo">
                        <div class="demo-layout">
                            <div class="demo-sidebar">
                                <strong>Sidebar</strong><br>
                                Width: 280px<br>
                                Fixed position
                            </div>
                            <div class="demo-main">
                                <div class="demo-nav">
                                    Navigation Bar (70px height)
                                </div>
                                <div class="demo-content">
                                    <strong>Main Content Area</strong><br>
                                    Width: calc(100% - 280px)<br>
                                    Margin-left: 280px<br><br>
                                    Your professors grid goes here...
                                </div>
                                <div class="demo-footer">
                                    <strong>✅ Footer (PROPERLY ALIGNED)</strong> - Width: calc(100% - 280px), Margin-left: 280px
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Checklist -->
            <div class="section">
                <h2 class="toggle-section" onclick="toggleSection('checklist')">
                    <span class="status-icon warning"></span>
                    Implementation Checklist
                </h2>
                <div id="checklist" class="collapsible-content">
                    <div class="check-item">
                        <span class="check-label">☐ Open footer.php file</span>
                        <span class="check-value">includes/footer.php</span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">☐ Update .footer styles with width and margin-left</span>
                        <span class="check-value">Add calc(100% - 280px)</span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">☐ Add responsive media queries</span>
                        <span class="check-value">@media (max-width: 1024px)</span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">☐ Test on desktop view</span>
                        <span class="check-value">Width > 1024px</span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">☐ Test on mobile view</span>
                        <span class="check-value">Width < 1024px</span>
                    </div>
                    <div class="check-item">
                        <span class="check-label">☐ Check for horizontal scroll</span>
                        <span class="check-value">Should be none</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSection(id) {
            const element = document.getElementById(id);
            if (element.style.display === 'none') {
                element.style.display = 'block';
            } else {
                element.style.display = 'none';
            }
        }

        function copyCode() {
            const code = `/* Footer Fix - Add to footer.php styles */
.footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px 24px;
    background-color: #7b2d26;
    color: #ffffff;
    font-size: 14px;
    margin-top: auto;
    width: calc(100% - 280px);
    margin-left: 280px;
    box-sizing: border-box;
    transition: margin-left 0.3s ease, width 0.3s ease;
}

@media (max-width: 1024px) {
    .footer {
        width: 100%;
        margin-left: 0;
    }
}`;

            navigator.clipboard.writeText(code).then(() => {
                const btn = event.target;
                const originalText = btn.textContent;
                btn.textContent = '✅ Copied!';
                btn.style.background = '#22c55e';
                setTimeout(() => {
                    btn.textContent = originalText;
                    btn.style.background = '#7b2d26';
                }, 2000);
            });
        }

        // Initialize all sections as open
        document.addEventListener('DOMContentLoaded', () => {
            const sections = ['issues', 'analysis', 'solution', 'demo', 'checklist'];
            sections.forEach(id => {
                document.getElementById(id).style.display = 'block';
            });
        });
    </script>
</body>
</html>