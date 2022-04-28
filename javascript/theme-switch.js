(function() {
    if (
        window.matchMedia &&
        !window.matchMedia("(prefers-color-scheme: dark)").matches
    ) {
        switchMode(document.querySelector('#theme-switcher'));
    }

    document.querySelector('#theme-switcher').addEventListener('click', function () {
        switchMode(this);
    })

    function switchMode(el) {
        const bodyClass = document.body.classList;
        bodyClass.contains("light")
            ? ((el.innerHTML = "light") && bodyClass.remove("light"))
            : ((el.innerHTML = "dark") && bodyClass.add("light"));
    }
});
