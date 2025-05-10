document.addEventListener("DOMContentLoaded", function () {
    const sidebar = document.getElementById("sidebar");
    const toggleSidebar = document.getElementById("toggleSidebar");
    const toggleSidebarMobile = document.getElementById("toggleSidebarMobile");

    if (toggleSidebar) {
        toggleSidebar.addEventListener("click", function () {
            sidebar.classList.toggle("-translate-x-full");
        });
    }

    if (toggleSidebarMobile) {
        toggleSidebarMobile.addEventListener("click", function () {
            sidebar.classList.toggle("-translate-x-full");
        });
    }
});
