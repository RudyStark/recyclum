import { startStimulusApp } from '@symfony/stimulus-bridge';

const app = startStimulusApp(require.context(
    './controllers',
    true,
    /_controller\.(j|t)s$/
));
// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
