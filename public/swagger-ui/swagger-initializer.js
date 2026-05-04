window.onload = function() {
  //<editor-fold desc="Changeable Configuration Block">

  // the following lines will be replaced by docker/configurator, when it runs in a docker-container
  window.ui = SwaggerUIBundle({
    url: "/smrp/api/doc.json",
    dom_id: '#swagger-ui',
    deepLinking: true,
    docExpansion: "list",
    presets: [
      SwaggerUIBundle.presets.apis,
      SwaggerUIStandalonePreset
    ],
    plugins: [
      SwaggerUIBundle.plugins.DownloadUrl
    ],
    syntaxHighlight: {activated:true,theme:"arta"},
    layout: "StandaloneLayout"
  });

  //</editor-fold>
};
