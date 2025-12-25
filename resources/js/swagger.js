import SwaggerUIBundle from 'swagger-ui-dist/swagger-ui-bundle.js';
import 'swagger-ui-dist/swagger-ui.css';

window.addEventListener('DOMContentLoaded', () => {
    SwaggerUIBundle({
        url: '/docs/openapi/openapi.yaml',
        dom_id: '#swagger-ui',
        deepLinking: true,
        presets: [
            SwaggerUIBundle.presets.apis,
            SwaggerUIBundle.SwaggerUIStandalonePreset
        ],
        layout: 'BaseLayout',
        defaultModelsExpandDepth: 1,
        defaultModelExpandDepth: 1,
        docExpansion: 'list',
        filter: true,
        showRequestHeaders: true,
        supportedSubmitMethods: ['get', 'post', 'put', 'delete', 'patch']
    });
});
