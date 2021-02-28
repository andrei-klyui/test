<script type="text/javascript">
    document.getElementById("the-span").addEventListener("click", function() {
        var json = JSON.stringify({
            name: this.dataset.name,
            html_url: this.dataset.html_url,
            description: this.dataset.description,
            owner_login: this.dataset.owner_login,
            stargazers_count: this.dataset.stargazers_count
        });
    });
</script>