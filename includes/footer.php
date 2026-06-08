        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Set active page title
        document.addEventListener('DOMContentLoaded', () => {
            const activeLink = document.querySelector('nav a.bg-indigo-600');
            if (activeLink) {
                const title = activeLink.textContent.trim();
                document.getElementById('pageTitle').textContent = title;
                document.title = `${title} - JC Pro Admin`;
            }
        });
    </script>
</body>
</html>
