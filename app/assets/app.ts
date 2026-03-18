import './stimulus_app';
/*
 * Main TypeScript entrypoint for the application.
 *
 * This file is included via encore_entry_script_tags('app') in Twig.
 */
import './styles/app.scss';
import initializeAppMenu from './services/mobile_menu';

initializeAppMenu();
