import $ from 'jquery';
window.$ = window.jQuery = $;

console.log('jQuery version:', $.fn.jquery);

$(document).ready(function() {
    console.log("jQuery is ready");
});

import '@fortawesome/fontawesome-free/css/all.css';
import './bootstrap';
import 'flowbite';
import Alpine from 'alpinejs';
window.Alpine = Alpine;

Alpine.start();
