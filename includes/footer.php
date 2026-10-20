<footer class="pie">
    <p><strong>ExpoFuturo</strong> — Orientación universitaria en Colombia · &copy; <?= date("Y") ?></p>
</footer>
<script>
(function () {
    var btn = document.getElementById("navToggle");
    var links = document.getElementById("navLinks");
    if (!btn || !links) return;
    var label = btn.querySelector(".nav-toggle-text");
    btn.addEventListener("click", function () {
        var abierto = links.classList.toggle("abierto");
        btn.setAttribute("aria-expanded", abierto ? "true" : "false");
        btn.setAttribute("aria-label", abierto ? "Cerrar menú" : "Abrir menú");
        if (label) label.textContent = abierto ? "Cerrar" : "Menú";
    });
})();
</script>
</body>
</html>
