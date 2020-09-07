(function() {
    (function() {
        var sideMenu = document.querySelector('.sidemenu.header');
        if (sideMenu) {
            sideMenu.style.display = 'none';
        }
        var toggle = document.querySelector('.sidebarIconToggle');
        if (toggle) {
            toggle.style.display = 'none';
        }
        
        
        function movePortalMessage() {
            if (window.innerWidth <= 768) {
                return;
            }
            var portalMessage = document.querySelector('.portalMessage');
            if (portalMessage) {
                
                portalMessage.parentNode.removeChild(portalMessage);
                var loginWindow = document.querySelector('#login-window');
                if (loginWindow) {
                    loginWindow.insertBefore(portalMessage, loginWindow.firstChild);
                }
                
            }
        }
        
        window.addEventListener('DOMContentLoaded', movePortalMessage);
    })();
})();