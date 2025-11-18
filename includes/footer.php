</main>

<footer style="background:#f7faf7;border-top:1px solid #e6e6e6;padding:15px 0;margin-top:30px;">
    <div style="max-width:1000px;margin:0 auto;text-align:center;color:#666;">
        <p style="margin:5px 0;">&copy; <?php echo date('Y'); ?> Toxic Control - Para Fazendas</p>
        <p style="margin:0;font-size:0.9rem;">Desenvolvido por Eduardo de Vasconcelos Silva & Gustavo Casanova</p>
    </div>
</footer>

<!-- scripts (se quiser adicionar JS depois) -->
<script>
// Exemplo simples: confirmar logout (opcional)
const logoutLink = document.querySelector('a[href$="logout.php"]');
if (logoutLink) {
    logoutLink.addEventListener('click', function(e){
        if (!confirm('Deseja realmente sair?')) e.preventDefault();
    });
}
</script>

</body>
</html>
