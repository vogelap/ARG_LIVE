</main>
        </div>
    </div>

    <script>
    $(document).ready(function() {
        $('.submenu-toggle').on('click', function(e) {
            e.preventDefault();
            var submenu = $(this).next('.submenu');
            
            // Close other open submenus
            $('.submenu').not(submenu).slideUp('fast');
            
            // Toggle the clicked submenu
            submenu.slideToggle('fast');
        });
    });
    </script>
</body>
</html>