import './global-shim.js';

import * as bootstrap from 'bootstrap';
import SimpleLightbox from 'simplelightbox/dist/simple-lightbox.esm.js';

import 'simplelightbox/dist/simple-lightbox.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import '@fontsource/poppins/400.css';
import '@fontsource/poppins/500.css';
import '@fontsource/poppins/600.css';
import '@fontsource/poppins/700.css';
import '@fontsource/poppins/800.css';
import '@fontsource/inter/400.css';
import '@fontsource/inter/500.css';
import '@fontsource/inter/600.css';

import './scripts.js';
import './nutritrace.js';

window.bootstrap = bootstrap;
window.SimpleLightbox = SimpleLightbox;

// Images et vidéos référencées via Vite::asset() dans les vues Blade
import.meta.glob(['../img/**', '../video/**']);
