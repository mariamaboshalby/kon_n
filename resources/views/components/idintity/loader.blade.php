<div id="loader-wrapper">
    <div class="spinner-border text-primary spinner-border-custom" role="status">
    </div>

  </div>

    <script>
    window.addEventListener('load', function () {
      const loader = document.getElementById('loader-wrapper');
      const content = document.getElementById('content');
      if (loader) {
        loader.style.display = 'none';
      }
      if (content) {
        content.style.visibility = 'visible';
      }
    });
  </script>