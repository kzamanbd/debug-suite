<?php
/**
 * Swagger UI documentation template.
 *
 * @var string $title
 * @var string $docs_path
 * @var string $schemas_json
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
    </style>
  </head>
  <body>
    <div id="docs-header">
      <div class="docs-header-left">
        <h1>
          <?php
			$logo_url = get_site_icon_url() ?: ( function_exists( 'has_custom_logo' ) && has_custom_logo() ? wp_get_attachment_image_url( get_theme_mod( 'custom_logo' ), 'full' ) : '' );
			if ( $logo_url ) {
				echo '<img src="' . esc_url( $logo_url ) . '" width="24" alt="Logo">';
			}
			?>
          <?php echo esc_html( $title ); ?> <!-- API Docs -->
        </h1>
      </div>
      <div class="docs-header-center">
        <span style="font-weight: 500;">Select an Example</span>
        <select id="schema-selector"></select>
      </div>
      <div class="docs-header-right">
        <span>Powered by Debug Suite</span>
      </div>
    </div>
    <div id="docs-container"></div>
    <script>
      (function () {
        // Parse schemas provided by PHP
        const schemas = <?php echo $schemas_json; ?>;
        const schemaKeys = Object.keys(schemas);
        
        // Dynamically calculate the base path to gracefully handle reverse proxies
        let currentPath = window.location.pathname;
        let targetPath = '<?php echo esc_js( $docs_path ); ?>';
        let matchIndex = currentPath.indexOf(targetPath);
        let basePath = matchIndex !== -1 ? currentPath.substring(0, matchIndex + targetPath.length) : currentPath;

        const selector = document.getElementById('schema-selector');
        const container = document.getElementById('docs-container');

        // Populate dropdown
        schemaKeys.forEach(key => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = schemas[key].name;
            selector.appendChild(option);
        });

        // Function to render the elements-api instance for a specific schema
        function renderDocs(schemaKey) {
            const schemaData = schemas[schemaKey].data;
            container.innerHTML = '<elements-api router="history" layout="sidebar" basePath="' + basePath + '"></elements-api>';
            const apiElement = container.querySelector('elements-api');
            apiElement.apiDescriptionDocument = schemaData;
        }

        // Render default (first available)
        if (schemaKeys.length > 0) {
            renderDocs(schemaKeys[0]);
        } else {
            // Fallback in case the array is empty
            container.innerHTML = '<p style="padding: 24px; font-family: sans-serif;">No API Schemas available.</p>';
            selector.disabled = true;
        }

        // Listen for dropdown changes
        selector.addEventListener('change', function(e) {
            renderDocs(e.target.value);
            // Optionally update URL/history if desired
        });
      })();
    </script>
  </body>
</html>
