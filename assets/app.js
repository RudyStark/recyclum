/*
 * Imports essentiels en PREMIER
 */

// Bootstrap JS (DOIT être avant Stimulus)
import 'bootstrap';

// Stimulus Bootstrap
import './bootstrap.js';

// Styles
import './styles/app.scss';
import 'glightbox/dist/css/glightbox.css';

// React (si utilisé)
import { registerReactControllerComponents } from '@symfony/ux-react';

registerReactControllerComponents(
    require.context('./react/controllers', true, /\.(j|t)sx?$/)
);

console.log('✅ App JS loaded with Bootstrap!');
