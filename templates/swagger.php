<?php
/**
 * Swagger UI documentation template.
 *
 * @var string $title
 * @var string $docs_path
 * @var string $schema_url
 * @var string $logo_url
 * @var array $namespaces
 * @var string $current_namespace
 * @var string $debug_suite_favicon_url
 *
 * @phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedScript
 * @phpcs:disable WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
 */
?>
<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title><?php echo esc_html( $title ); ?> API Docs</title>
    <?php if ( $debug_suite_favicon_url ) : ?>
      <link rel="icon" href="<?php echo esc_url( $debug_suite_favicon_url ); ?>" sizes="32x32">
      <link rel="apple-touch-icon" href="<?php echo esc_url( $debug_suite_favicon_url ); ?>">
    <?php endif; ?>
    <!-- We do not use wp_head() or wp_footer() here to intentionally strip out any theme/plugin CSS and JS -->
    <script src="https://unpkg.com/@stoplight/elements/web-components.min.js"></script>
    <link rel="stylesheet" href="https://unpkg.com/@stoplight/elements/styles.min.css">
    <style>
      body,
      html {
        height: 100vh;
        margin: 0;
        display: flex;
        flex-direction: column;
      }
      #docs-header {
        background-color: #111727;
        height: 56px;
        padding: 0 24px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
        color: #ffffff;
      }
      .docs-header-left, .docs-header-right, .docs-header-center {
        display: flex;
        align-items: center;
      }
      .docs-header-left {
        width: 25%;
      }
      .docs-header-center {
        width: 50%;
        justify-content: center;
        gap: 12px;
        font-size: 14px;
        color: #9cb1c6;
      }
      .docs-header-right {
        width: 25%;
        justify-content: flex-end;
        font-size: 13px;
        color: #9cb1c6;
      }
      #docs-header h1 {
        font-size: 16px;
        font-weight: 600;
        margin: 0;
        display: flex;
        align-items: center;
        gap: 12px;
      }
      #docs-header img {
        border-radius: 4px;
      }
      #docs-header select {
        padding: 6px 32px 6px 12px;
        font-size: 13px;
        color: #ffffff;
        border: 1px solid #333d4d;
        border-radius: 4px;
        background-color: #1a2234;
        cursor: pointer;
        appearance: none;
        -webkit-appearance: none;
        background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20width%3D%2212%22%20height%3D%2212%22%20viewBox%3D%220%200%2012%22%20fill%3D%22none%22%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%3E%3Cpath%20d%3D%22M6%208.825L1.175%204L2.35%202.825L6%206.475L9.65%202.825L10.825%204L6%208.825Z%22%20fill%3D%22%239cb1c6%22%2F%3E%3C%2Fsvg%3E");
        background-repeat: no-repeat;
        background-position: right 10px center;
        outline: none;
        transition: border-color 0.2s;
      }
      #docs-header select:hover,
      #docs-header select:focus {
        border-color: #556885;
      }
      #docs-container {
        flex: 1;
        overflow: hidden;
      }
      .debug-suite-loader-wrapper {
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        height: 100%;
        color: #9cb1c6;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      }
      .debug-suite-spinner {
        animation: rotate 2s linear infinite;
        width: 40px;
        height: 40px;
        margin-bottom: 16px;
      }
      .debug-suite-spinner .path {
        stroke: #6366f1;
        stroke-linecap: round;
        animation: dash 1.5s ease-in-out infinite;
      }
      @keyframes rotate {
        100% { transform: rotate(360deg); }
      }
      @keyframes dash {
        0% { stroke-dasharray: 1, 150; stroke-dashoffset: 0; }
        50% { stroke-dasharray: 90, 150; stroke-dashoffset: -35; }
        100% { stroke-dasharray: 90, 150; stroke-dashoffset: -124; }
      }
    </style>
  </head>
  <body>
    <div id="docs-header">
      <div class="docs-header-left">
        <h1>
          <?php echo '<img src="' . esc_url( $logo_url ) . '" width="24" alt="Logo">'; ?>
          <?php echo esc_html( $title ); ?> <!-- API Docs -->
        </h1>
      </div>
      <div class="docs-header-center">
        <span style="font-weight: 500;">Select an Namespace</span>
        <select id="schema-selector">
          <?php foreach ( $namespaces as $debug_suite_item ) : ?>
            <option value="<?php echo esc_attr( $debug_suite_item ); ?>" <?php selected( $current_namespace, $debug_suite_item ); ?>>
				      <?php echo esc_html( $debug_suite_item ); ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="docs-header-right">
        <span>Powered by Debug Suite</span>
      </div>
    </div>
    <div id="docs-container">
      <div class="debug-suite-loader-wrapper">
        <svg class="debug-suite-spinner" viewBox="0 0 50 50">
          <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
        </svg>
        <p>Loading API Schema...</p>
      </div>
    </div>
    <script>
      (function () {
        
        const base_schema_url = '<?php echo esc_js( $schema_url ); ?>';
        // Dynamically calculate the base path to gracefully handle reverse proxies
        let currentPath = window.location.pathname;
        let targetPath = '<?php echo esc_js( $docs_path ); ?>';
        let matchIndex = currentPath.indexOf(targetPath);
        let basePath = matchIndex !== -1 ? currentPath.substring(0, matchIndex + targetPath.length) : currentPath;

        const selector = document.getElementById('schema-selector');
        const container = document.getElementById('docs-container');
        
        // Function to render the elements-api instance for a specific schema
        async function renderDocs(schemaUrl) {
            container.innerHTML = `
              <div class="debug-suite-loader-wrapper">
                <svg class="debug-suite-spinner" viewBox="0 0 50 50">
                  <circle class="path" cx="25" cy="25" r="20" fill="none" stroke-width="5"></circle>
                </svg>
                <p>Loading API Schema...</p>
              </div>
            `;

            try {
                const response = await fetch(schemaUrl);
                if (!response.ok) throw new Error('Network response was not ok');
                const schema = await response.json();
                
                container.innerHTML = '<elements-api router="history" layout="sidebar" basePath="' + basePath + '"></elements-api>';
                const apiElement = container.querySelector('elements-api');
                apiElement.apiDescriptionDocument = schema;
            } catch (error) {
                container.innerHTML = `
                  <div class="debug-suite-loader-wrapper">
                    <p style="color: #ef4444;">Error loading API schema. Please try again.</p>
                  </div>
                `;
                console.error('Error fetching schema:', error);
            }
        }

        function loadSchemaFromSelector(isInit = false) {
            let namespace = selector.value;
            
            if (isInit) {
                const storedNamespace = localStorage.getItem('debug_suite_swagger_namespace');
                if (storedNamespace && Array.from(selector.options).some(opt => opt.value === storedNamespace)) {
                    namespace = storedNamespace;
                    selector.value = namespace;
                }
            } else {
                localStorage.setItem('debug_suite_swagger_namespace', namespace);
            }

            const separator = base_schema_url.indexOf('?') !== -1 ? '&' : '?';
            const dynamicSchemaUrl = `${base_schema_url}${separator}namespace=${encodeURIComponent(namespace)}`;
            
            renderDocs(dynamicSchemaUrl);
        }

        // Initialize on load
        loadSchemaFromSelector(true);

        // Re-render on change
        selector.addEventListener('change', () => loadSchemaFromSelector(false));
      })();
    </script>
  </body>
</html>
