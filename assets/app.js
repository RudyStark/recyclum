import './bootstrap.js';
import './styles/app.scss';

import 'glightbox/dist/css/glightbox.css';

import { registerReactControllerComponents } from '@symfony/ux-react';

// Enregistre automatiquement tous les composants React dans assets/react/**.
// Le nom du composant = nom du fichier (Hello.jsx => 'Hello').
registerReactControllerComponents(
    require.context('./react/controllers', true, /\.(j|t)sx?$/)
);

import 'bootstrap';

