    <!-- jQuery library js (loaded sync — many inline scripts depend on $ at parse time) -->
    <script src="{{ asset('assets/js/lib/jquery-3.7.1.min.js') }}"></script>
    <!-- jQuery UI js (depends on jQuery; some inline init relies on it being ready before app.js) -->
    <script src="{{ asset('assets/js/lib/jquery-ui.min.js') }}"></script>
    <!-- Heavy/optional libs — deferred so HTML parsing isn't blocked. Order is preserved, DOMContentLoaded waits for all defer scripts. -->
    <script defer src="{{ asset('assets/js/lib/apexcharts.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/simple-datatables.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/iconify-icon.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/jquery-jvectormap-2.0.5.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/jquery-jvectormap-world-mill-en.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/magnifc-popup.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/slick.min.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/prism.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/file-upload.js') }}"></script>
    <script defer src="{{ asset('assets/js/lib/audioplayer.js') }}"></script>

    <script src="{{ asset('assets/js/flowbite.min.js') }}"></script>
    <!-- main js -->
    <script src="{{ asset('assets/js/app.js') }}"></script>

    <?php echo (isset($script) ? $script   : '')?>